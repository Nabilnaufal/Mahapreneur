<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ChatMessageResource extends JsonResource
{
    public function toArray(Request $request)
    {
        return [
            'id' => $this->id,
            'conversationId' => $this->conversation_id,
            'body' => $this->body,
            'senderId' => $this->sender_id,
            'senderName' => $this->whenLoaded('sender', fn () => $this->sender?->name),
            'readAt' => $this->read_at?->toISOString(),
            'createdAt' => $this->created_at?->toISOString(),
        ];
    }
}
