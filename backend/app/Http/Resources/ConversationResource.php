<?php

namespace App\Http\Resources;

use App\Support\FileUrl;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ConversationResource extends JsonResource
{
    public function toArray(Request $request)
    {
        return [
            'id' => $this->id,
            'customerId' => $this->customer_id,
            'storeId' => $this->store_id,
            'store' => $this->whenLoaded('store', fn () => $this->store ? [
                'id' => $this->store->id,
                'name' => $this->store->name,
                'slug' => $this->store->slug,
                'logo' => FileUrl::make($this->store->logo),
            ] : null),
            'customer' => $this->whenLoaded('customer', fn () => $this->customer ? [
                'id' => $this->customer->id,
                'name' => $this->customer->name,
                'avatar' => FileUrl::make($this->customer->avatar),
            ] : null),
            'lastMessage' => $this->whenLoaded('latestMessage', fn () => $this->latestMessage
                ? ChatMessageResource::make($this->latestMessage)->resolve()
                : null),
            'unreadCount' => (int) ($this->unread_count ?? 0),
            'lastMessageAt' => $this->last_message_at?->toISOString(),
        ];
    }
}
