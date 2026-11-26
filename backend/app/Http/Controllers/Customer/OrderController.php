<?php

namespace App\Http\Controllers\Customer;

use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Http\Resources\PaymentResource;
use App\Http\Resources\StoreResource;
use App\Models\Order;
use App\Models\Product;
use App\Services\PaymentService;
use App\Support\Activity;
use App\Support\Reference;
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
        $order = Order::with(['store', 'items.product.images', 'payment', 'histories', 'review'])
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

    public function review(Request $request, int $id)
    {
        $data = $request->validate([
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'comment' => ['nullable', 'string', 'max:1000'],
        ]);

        $order = Order::with('review')
            ->where('customer_id', $request->user()->id)
            ->find($id);
        if (! $order) {
            throw ApiException::notFound('Pesanan tidak ditemukan');
        }
        if ($order->status !== 'COMPLETED') {
            throw ApiException::badRequest('Ulasan baru bisa diberikan setelah pesanan selesai');
        }

        $review = $order->review()->updateOrCreate(
            [],
            [
                'customer_id' => $request->user()->id,
                'rating' => $data['rating'],
                'comment' => $data['comment'] ?? null,
            ],
        );

        Activity::log(
            $request->user()->id,
            $review->wasRecentlyCreated ? 'ORDER_REVIEW_CREATED' : 'ORDER_REVIEW_UPDATED',
            'Order',
            $order->id,
            "Ulasan pesanan {$order->order_number} diberi rating {$review->rating}/5",
        );

        return $this->ok([
            'review' => [
                'id' => $review->id,
                'rating' => $review->rating,
                'comment' => $review->comment,
                'createdAt' => $review->created_at?->toISOString(),
                'updatedAt' => $review->updated_at?->toISOString(),
            ],
        ], 'Ulasan berhasil disimpan');
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
        ]);
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

        $order->payment?->update([
            'status' => 'PENDING',
            'invoice_number' => Reference::invoiceNumber($order->order_number),
            'doku_token_id' => null,
            'doku_payment_url' => null,
            'doku_qr_string' => null,
            'doku_va_number' => null,
        ]);
        $prepared = $this->payments->prepareOnlinePayment($order->id);

        return $this->ok(['payment' => $prepared ? PaymentResource::make($prepared['payment']) : null], 'Instruksi pembayaran baru dibuat');
    }
}
