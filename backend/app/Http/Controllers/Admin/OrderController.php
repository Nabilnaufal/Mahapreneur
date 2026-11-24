<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $orders = Order::with(['store', 'customer', 'items.product.images', 'payment'])
            ->when($request->query('status'), fn ($query, string $status) => $query->where('status', $status))
            ->when($request->query('payment_status'), fn ($query, string $status) => $query->where('payment_status', $status))
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

    public function show(int $id)
    {
        $order = Order::with(['store', 'customer', 'items.product.images', 'payment', 'histories'])->find($id);
        if (! $order) {
            throw ApiException::notFound('Pesanan tidak ditemukan');
        }

        return $this->ok(['order' => OrderResource::make($order)]);
    }
}
