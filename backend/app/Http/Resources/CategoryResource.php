<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CategoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'icon' => $this->icon,
            'sortOrder' => $this->sort_order,
            'isActive' => $this->is_active,
            'createdAt' => $this->created_at?->toISOString(),
            'productCount' => $this->when(isset($this->products_count), fn () => $this->products_count),
        ];
    }
}
