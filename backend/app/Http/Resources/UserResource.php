<?php

namespace App\Http\Resources;

use App\Support\FileUrl;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'role' => $this->role,
            'isActive' => $this->is_active,
            'emailVerifiedAt' => $this->email_verified_at?->toISOString(),
            'avatar' => FileUrl::make($this->avatar),
            'phone' => $this->phone,
            'createdAt' => $this->created_at?->toISOString(),
            'updatedAt' => $this->updated_at?->toISOString(),
            'store' => $this->whenLoaded('store', fn () => $this->store ? [
                'id' => $this->store->id,
                'name' => $this->store->name,
                'slug' => $this->store->slug,
                'status' => $this->store->status,
                'logo' => FileUrl::make($this->store->logo),
                '_count' => isset($this->store->products_count) || isset($this->store->orders_count) ? [
                    'products' => $this->store->products_count ?? 0,
                    'orders' => $this->store->orders_count ?? 0,
                ] : null,
            ] : null),
            '_count' => $this->when(
                isset($this->orders_count) || isset($this->addresses_count) || isset($this->wishlists_count),
                fn () => [
                    'orders' => $this->orders_count ?? 0,
                    'addresses' => $this->addresses_count ?? 0,
                    'wishlists' => $this->wishlists_count ?? 0,
                ],
            ),
        ];
    }
}
