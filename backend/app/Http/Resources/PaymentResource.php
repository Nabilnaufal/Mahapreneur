<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'orderId' => $this->order_id,
            'method' => $this->method,
            'status' => $this->status,
            'amount' => $this->amount,
            'invoiceNumber' => $this->invoice_number,
            'dokuTokenId' => $this->doku_token_id,
            'dokuPaymentUrl' => $this->doku_payment_url,
            'dokuQrString' => $this->doku_qr_string,
            'dokuVaNumber' => $this->doku_va_number,
            'dokuChannel' => $this->doku_channel,
            'expiresAt' => $this->expires_at?->toISOString(),
            'paidAt' => $this->paid_at?->toISOString(),
            'createdAt' => $this->created_at?->toISOString(),
            'order' => $this->whenLoaded('order', fn () => $this->order ? [
                'id' => $this->order->id,
                'orderNumber' => $this->order->order_number,
                'store' => $this->order->relationLoaded('store') && $this->order->store ? [
                    'name' => $this->order->store->name,
                ] : null,
                'customer' => $this->order->relationLoaded('customer') && $this->order->customer ? [
                    'name' => $this->order->customer->name,
                    'email' => $this->order->customer->email,
                ] : null,
            ] : null),
        ];
    }
}
