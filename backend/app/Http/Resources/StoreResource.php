<?php

namespace App\Http\Resources;

use App\Support\FileUrl;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StoreResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'userId' => $this->user_id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'logo' => FileUrl::make($this->logo),
            'banner' => FileUrl::make($this->banner),
            'phone' => $this->phone,
            'email' => $this->email,
            'address' => $this->address,
            'campus' => $this->campus,
            'openTime' => $this->open_time,
            'closeTime' => $this->close_time,
            'status' => $this->status,
            'createdAt' => $this->created_at?->toISOString(),
            'updatedAt' => $this->updated_at?->toISOString(),
            'productCount' => $this->when(isset($this->products_count), fn () => $this->products_count),
            'orderCount' => $this->when(isset($this->orders_count), fn () => $this->orders_count),
            '_count' => $this->when(isset($this->products_count) || isset($this->orders_count), fn () => [
                'products' => $this->products_count ?? 0,
                'orders' => $this->orders_count ?? 0,
            ]),
            'user' => $this->whenLoaded('user', fn () => $this->user ? [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'createdAt' => $this->user->created_at?->toISOString(),
            ] : null),
            'owner' => $this->whenLoaded('user', fn () => $this->user ? [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'email' => $this->user->email,
                'isActive' => $this->user->is_active,
            ] : null),
        ];
    }
}
