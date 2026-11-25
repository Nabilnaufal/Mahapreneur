<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Store extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'slug',
        'description',
        'logo',
        'banner',
        'phone',
        'email',
        'address',
        'campus',
        'open_time',
        'close_time',
        'status',
    ];

    public function scopeActive(Builder $query): void
    {
        $query->where('status', 'ACTIVE');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function reviews(): HasManyThrough
    {
        return $this->hasManyThrough(OrderReview::class, Order::class, 'store_id', 'order_id', 'id', 'id');
    }

    public function conversations(): HasMany
    {
        return $this->hasMany(Conversation::class);
    }
}
