<?php

namespace App\Http\Controllers\Seller;

use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Models\Product;
use App\Support\Activity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class OrderController extends Controller
{
    private const NEXT_STATUS = [
        'CONFIRMED' => ['PROCESSING', 'CANCELLED'],
        'PROCESSING' => ['READY', 'CANCELLED'],
        'READY' => ['ON_DELIVERY'],
        'ON_DELIVERY' => ['COMPLETED'],
    ];

    private const STATUS_LABEL = [
        'PROCESSING' => 'sedang diproses',
        'READY' => 'siap diserahkan',
        'ON_DELIVERY' => 'sedang diantarkan',
        'COMPLETED' => 'selesai',
        'CANCELLED' => 'dibatalkan',
    ];

    public function index(Request $request)
    {
        $orders = Order::with(['customer', 'items.product.images', 'payment'])
            ->where('store_id', $this->storeId($request))
            ->when($request->query('status'), fn ($query, string $status) => $query->where('status', $status))
            ->when($request->query('q'), fn ($query, string $keyword) => $query->where(
                fn ($search) => $search->where('order_number', 'like', "%{$keyword}%")
                    ->orWhere('recipient_name', 'like', "%{$keyword}%"),
            ))
            ->latest('created_at')
            ->paginate($this->perPage($request));

        return $this->ok([
            'orders' => OrderResource::collection($orders),
            'meta' => $this->meta($orders),
        ]);
    }

    public function show(Request $request, int $id)
    {
        $order = Order::with(['customer', 'items.product.images', 'payment', 'histories'])
            ->where('store_id', $this->storeId($request))
            ->find($id);
        if (! $order) {
            throw ApiException::notFound('Pesanan tidak ditemukan');
        }

        return $this->ok(['order' => OrderResource::make($order)]);
    }

    public function updateStatus(Request $request, int $id)
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(['PROCESSING', 'READY', 'ON_DELIVERY', 'COMPLETED', 'CANCELLED'])],
            'note' => ['nullable', 'string', 'max:300'],
        ]);
        $order = Order::with(['items', 'payment'])->where('store_id', $this->storeId($request))->find($id);
        if (! $order) {
            throw ApiException::notFound('Pesanan tidak ditemukan');
        }
        if (! in_array($data['status'], self::NEXT_STATUS[$order->status] ?? [], true)) {
            throw ApiException::badRequest("Status tidak bisa diubah dari {$order->status} ke {$data['status']}");
        }
        if (! in_array($order->payment_status, ['PAID', 'UNPAID_COD'], true)) {
            throw ApiException::badRequest('Pesanan belum dibayar');
        }

        $codCompleted = $data['status'] === 'COMPLETED'
            && $order->payment_method === 'COD'
            && $order->payment_status === 'UNPAID_COD';

        DB::transaction(function () use ($order, $data, $request, $codCompleted) {
            $order->update([
                'status' => $data['status'],
                'payment_status' => $codCompleted ? 'PAID' : $order->payment_status,
                'completed_at' => $data['status'] === 'COMPLETED' ? now() : $order->completed_at,
                'cancelled_at' => $data['status'] === 'CANCELLED' ? now() : $order->cancelled_at,
            ]);
            if ($codCompleted) {
                $order->payment?->update(['status' => 'PAID', 'paid_at' => now()]);
            }
            $order->histories()->create([
                'status' => $data['status'],
                'note' => $data['note'] ?? null,
                'created_by' => $request->user()->id,
            ]);
            if ($data['status'] === 'CANCELLED') {
                foreach ($order->items->whereNotNull('product_id') as $item) {
                    Product::whereKey($item->product_id)->increment('stock', $item->quantity);
                    Product::whereKey($item->product_id)->decrement('sold', $item->quantity);
                }
            }
        });

        Activity::notify(
            $order->customer_id,
            'Pesanan '.(self::STATUS_LABEL[$data['status']] ?? $data['status']),
            "Pesanan {$order->order_number} ".(self::STATUS_LABEL[$data['status']] ?? 'diperbarui').'.'.(! empty($data['note']) ? " Catatan seller: {$data['note']}" : ''),
            $data['status'] === 'CANCELLED' ? 'warning' : 'info',
            "/customer/orders/{$order->id}",
        );
        Activity::log(
            $request->user()->id,
            'ORDER_STATUS_UPDATED',
            'Order',
            $order->id,
            "Status {$order->order_number} diubah ke {$data['status']}".($codCompleted ? ' dan pembayaran COD otomatis ditandai lunas' : ''),
        );

        return $this->ok(['orderId' => $order->id, 'status' => $data['status']], "Status pesanan diperbarui menjadi {$data['status']}");
    }

    private function storeId(Request $request): int
    {
        return $request->user()->store->id;
    }
}
