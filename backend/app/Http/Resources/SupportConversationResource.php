<?php

namespace App\Http\Resources;

use App\Support\FileUrl;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SupportConversationResource extends JsonResource
{
    public function toArray(Request $request)
    {
        return [
            'id' => $this->id,
            'customerId' => $this->customer_id,
            'storeId' => null,
            'store' => null,
            'customer' => $this->whenLoaded('customer', fn () => $this->customer ? [
                'id' => $this->customer->id,
                'name' => $this->customer->name,
                'avatar' => FileUrl::make($this->customer->avatar),
            ] : null),
            'lastMessage' => $this->whenLoaded('latestMessage', fn () => $this->latestMessage
                ? SupportMessageResource::make($this->latestMessage)->resolve()
                : null),
            'unreadCount' => (int) ($this->unread_count ?? 0),
            'lastMessageAt' => $this->last_message_at?->toISOString(),
        ];
    }
}
