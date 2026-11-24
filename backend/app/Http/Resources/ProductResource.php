<?php

namespace App\Http\Resources;

use App\Support\FileUrl;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $images = $this->images ?? collect();
        $urls = $images->map(fn ($image) => FileUrl::make($image->url))->filter()->values();

        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'price' => $this->price,
            'stock' => $this->stock,
            'type' => $this->type,
            'status' => $this->status,
            'sold' => $this->sold,
            'serviceTerms' => $this->service_terms,
            'serviceDuration' => $this->service_duration,
            'createdAt' => $this->created_at?->toISOString(),
            'image' => $urls->first(),
            'images' => $urls,
            'imageRecords' => $images->map(fn ($image) => [
                'id' => $image->id,
                'url' => FileUrl::make($image->url),
            ]),
            'category' => $this->whenLoaded('category', fn () => $this->category ? [
                'id' => $this->category->id,
                'name' => $this->category->name,
                'slug' => $this->category->slug,
                'icon' => $this->category->icon,
            ] : null),
            'store' => $this->whenLoaded('store', fn () => $this->store ? [
                'id' => $this->store->id,
                'name' => $this->store->name,
                'slug' => $this->store->slug,
                'logo' => FileUrl::make($this->store->logo),
                'status' => $this->store->status,
            ] : null),
        ];
    }
}
