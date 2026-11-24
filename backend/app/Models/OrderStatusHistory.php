<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderStatusHistory extends Model
{
    const UPDATED_AT = null;

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
