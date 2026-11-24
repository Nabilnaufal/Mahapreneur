<?php

namespace App\Http\Resources;

use App\Support\FileUrl;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'orderNumber' => $this->order_number,
            'customerId' => $this->customer_id,
            'storeId' => $this->store_id,
            'status' => $this->status,
            'paymentStatus' => $this->payment_status,
            'paymentMethod' => $this->payment_method,
            'subtotal' => $this->subtotal,
            'total' => $this->total,
            'recipientName' => $this->recipient_name,
            'recipientPhone' => $this->recipient_phone,
            'address' => $this->address,
            'campus' => $this->campus,
            'room' => $this->room,
            'note' => $this->note,
            'createdAt' => $this->created_at?->toISOString(),
            'updatedAt' => $this->updated_at?->toISOString(),
            'confirmedAt' => $this->confirmed_at?->toISOString(),
            'completedAt' => $this->completed_at?->toISOString(),
            'cancelledAt' => $this->cancelled_at?->toISOString(),
            'store' => $this->whenLoaded('store', fn () => $this->store ? StoreResource::make($this->store) : null),
            'customer' => $this->whenLoaded('customer', fn () => $this->customer ? [
                'id' => $this->customer->id,
                'name' => $this->customer->name,
                'email' => $this->customer->email,
                'phone' => $this->customer->phone,
            ] : null),
            'items' => $this->whenLoaded('items', fn () => $this->items->map(fn ($item) => [
                'id' => $item->id,
                'productId' => $item->product_id,
                'productName' => $item->product_name,
                'productPrice' => $item->product_price,
                'quantity' => $item->quantity,
                'subtotal' => $item->subtotal,
                'image' => FileUrl::make($item->product?->images->first()?->url),
            ])),
            'payment' => $this->whenLoaded('payment', fn () => $this->payment ? PaymentResource::make($this->payment) : null),
            'histories' => $this->whenLoaded('histories', fn () => $this->histories->map(fn ($history) => [
                'id' => $history->id,
                'status' => $history->status,
                'note' => $history->note,
                'createdBy' => $history->created_by,
                'createdAt' => $history->created_at?->toISOString(),
            ])),
        ];
    }
}
