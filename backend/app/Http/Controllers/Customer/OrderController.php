<?php

namespace App\Http\Controllers\Customer;

use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Http\Resources\PaymentResource;
use App\Http\Resources\StoreResource;
use App\Models\Order;
use App\Models\Product;
use App\Services\DokuService;
use App\Services\PaymentService;
use App\Support\Activity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    public function __construct(private PaymentService $payments) {}

    public function index(Request $request)
    {
        $orders = Order::with(['store', 'items.product.images', 'payment'])
            ->where('customer_id', $request->user()->id)
            ->when($request->query('status'), fn ($query, string $status) => $query->where('status', $status))
            ->latest('created_at')
            ->paginate($this->perPage($request));

        return $this->ok([
            'orders' => OrderResource::collection($orders),
            'meta' => $this->meta($orders),
        ]);
    }

    public function show(Request $request, int $id)
    {
        $order = Order::with(['store', 'items.product.images', 'payment', 'histories'])
            ->where('customer_id', $request->user()->id)
            ->find($id);
        if (! $order) {
            throw ApiException::notFound('Pesanan tidak ditemukan');
        }

        return $this->ok([
            'order' => OrderResource::make($order),
            'store' => StoreResource::make($order->store),
        ]);
    }

    public function cancel(Request $request, int $id)
    {
        $data = $request->validate(['reason' => ['nullable', 'string', 'max:300']]);
        $order = Order::with(['items', 'store', 'payment'])
            ->where('customer_id', $request->user()->id)
            ->find($id);
        if (! $order) {
            throw ApiException::notFound('Pesanan tidak ditemukan');
        }
        if (! in_array($order->status, ['WAITING_PAYMENT', 'CONFIRMED'], true)) {
            throw ApiException::badRequest('Pesanan yang sudah diproses tidak dapat dibatalkan. Hubungi seller.');
        }
        if ($order->payment_status === 'PAID') {
            throw ApiException::badRequest('Pesanan sudah dibayar. Ajukan pembatalan ke seller untuk proses refund.');
        }

        DB::transaction(function () use ($order, $data, $request) {
            $order->update([
                'status' => 'CANCELLED',
                'cancelled_at' => now(),
                'payment_status' => $order->payment_status === 'PENDING' ? 'FAILED' : $order->payment_status,
            ]);
            foreach ($order->items as $item) {
                if ($item->product_id) {
                    Product::whereKey($item->product_id)->increment('stock', $item->quantity);
                    Product::whereKey($item->product_id)->decrement('sold', $item->quantity);
                }
            }
            $order->histories()->create([
                'status' => 'CANCELLED',
                'note' => $data['reason'] ?? 'Dibatalkan oleh customer',
                'created_by' => $request->user()->id,
            ]);
            if ($order->payment?->status === 'PENDING') {
                $order->payment->update(['status' => 'FAILED']);
            }
        });

        Activity::notify(
            $order->store->user_id,
            'Pesanan dibatalkan customer',
            "Pesanan {$order->order_number} dibatalkan oleh customer.".($data['reason'] ?? null ? " Alasan: {$data['reason']}" : ''),
            'warning',
            "/seller/orders/{$order->id}",
        );
        Activity::log($request->user()->id, 'ORDER_CANCELLED', 'Order', $order->id, "Customer membatalkan pesanan {$order->order_number}");

        return $this->ok(['orderId' => $order->id], 'Pesanan dibatalkan');
    }

    public function payment(Request $request, int $id)
    {
        $order = Order::with('payment')->where('customer_id', $request->user()->id)->find($id);
        if (! $order) {
            throw ApiException::notFound('Pesanan tidak ditemukan');
        }
        if ($order->payment_method === 'COD') {
            throw ApiException::badRequest('Pesanan ini memakai metode COD');
        }

        if (! $order->payment) {
            $this->payments->prepareOnlinePayment($order->id);
            $order->load('payment');
        }
        if ($order->payment?->status === 'PENDING' && $order->payment->expires_at?->isPast()) {
            $order->payment->update(['status' => 'EXPIRED']);
            $order->update(['payment_status' => 'EXPIRED']);
            $order->payment->status = 'EXPIRED';
        }

        return $this->ok([
            'order' => [
                'id' => $order->id,
                'orderNumber' => $order->order_number,
                'total' => $order->total,
                'status' => $order->status,
                'paymentStatus' => $order->payment_status,
                'paymentMethod' => $order->payment_method,
            ],
            'payment' => $order->payment ? PaymentResource::make($order->payment) : null,
            'channels' => $this->payments->availableChannels($order->payment_method),
            'simulated' => ! DokuService::configured(),
        ]);
    }

    public function chooseChannel(Request $request, int $id)
    {
        $data = $request->validate(['channel_code' => ['required', 'string', 'min:2']]);
        $order = Order::with('payment')->where('customer_id', $request->user()->id)->find($id);
        if (! $order?->payment) {
            throw ApiException::notFound('Data pembayaran belum tersedia');
        }
        if ($order->payment->status === 'PAID') {
            throw ApiException::badRequest('Pesanan ini sudah dibayar');
        }
        if ($order->payment->status === 'EXPIRED') {
            throw ApiException::badRequest('Pembayaran sudah kedaluwarsa. Buat pembayaran baru.');
        }

        if (! DokuService::configured() || ! $order->payment->doku_token_id) {
            $order->payment->update([
                'doku_channel' => $data['channel_code'],
                'doku_qr_string' => $order->payment_method === 'QRIS' ? "SIMULASI-QRIS-{$order->order_number}" : null,
                'doku_va_number' => $order->payment_method === 'VIRTUAL_ACCOUNT' ? '88'.str_pad((string) $order->id, 10, '0', STR_PAD_LEFT) : null,
            ]);

            return $this->ok(['payment' => PaymentResource::make($order->payment->fresh()), 'simulated' => true], 'Mode simulasi pembayaran aktif (kredensial DOKU belum diisi)');
        }

        $channel = app(DokuService::class)->requestChannel($order->payment->doku_token_id, $data['channel_code']);
        $order->payment->update([
            'doku_channel' => $data['channel_code'],
            'doku_qr_string' => $channel['qrString'],
            'doku_va_number' => $channel['vaNumber'],
            'doku_payment_url' => $channel['paymentUrl'] ?? $order->payment->doku_payment_url,
        ]);

        return $this->ok(['payment' => PaymentResource::make($order->payment->fresh()), 'simulated' => false], 'Instruksi pembayaran siap');
    }

    public function retryPayment(Request $request, int $id)
    {
        $order = Order::with('payment')->where('customer_id', $request->user()->id)->find($id);
        if (! $order) {
            throw ApiException::notFound('Pesanan tidak ditemukan');
        }
        if ($order->payment_status === 'PAID') {
            throw ApiException::badRequest('Pesanan ini sudah dibayar');
        }
        if ($order->payment_method === 'COD') {
            throw ApiException::badRequest('Pesanan COD tidak memerlukan pembayaran online');
        }

        $order->payment?->update(['status' => 'PENDING']);
        $prepared = $this->payments->prepareOnlinePayment($order->id);

        return $this->ok(['payment' => $prepared ? PaymentResource::make($prepared['payment']) : null], 'Instruksi pembayaran baru dibuat');
    }

    public function simulatePayment(Request $request, int $id)
    {
        if (DokuService::configured()) {
            throw ApiException::forbidden('Simulasi pembayaran tidak tersedia saat DOKU aktif');
        }

        $order = Order::with('payment')->where('customer_id', $request->user()->id)->find($id);
        if (! $order?->payment?->invoice_number) {
            throw ApiException::notFound('Data pembayaran tidak ditemukan');
        }

        $result = $this->payments->markPaid(
            $order->payment->invoice_number,
            $order->payment->amount,
            $order->payment->doku_channel,
            'SIMULATION',
        );

        return $this->ok($result, 'Pembayaran simulasi diproses');
    }
}
