<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Product extends Model
{
    protected $fillable = [
        'store_id',
        'category_id',
        'name',
        'slug',
        'description',
        'price',
        'stock',
        'type',
        'status',
        'service_terms',
        'service_duration',
        'sold',
    ];

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
        ])->withAvg('reviews', 'rating')->withCount('reviews');
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

    public function reviews(): HasManyThrough
    {
        return $this->hasManyThrough(OrderReview::class, OrderItem::class, 'product_id', 'order_id', 'id', 'order_id');
    }

    public function views(): HasMany
    {
        return $this->hasMany(ProductView::class);
    }
}
