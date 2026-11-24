<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    protected $guarded = [];

    protected $casts = [
        'price' => 'integer',
        'stock' => 'integer',
        'sold' => 'integer',
    ];

    /** Produk yang boleh tampil di katalog publik. */
    public function scopeVisible(Builder $query): void
    {
        $query->where('status', 'ACTIVE')
            ->whereHas('store', fn (Builder $store) => $store->where('status', 'ACTIVE'));
    }

    /** Relasi yang selalu dibutuhkan kartu produk. */
    public function scopeWithCard(Builder $query): void
    {
        $query->with([
            'images' => fn ($images) => $images->orderBy('sort_order'),
            'category:id,name,slug,icon',
            'store:id,name,slug,logo,status',
        ]);
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class);
    }

    public function wishlists(): HasMany
    {
        return $this->hasMany(Wishlist::class);
    }
}
