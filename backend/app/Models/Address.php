<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Address extends Model
{
    protected function casts()
    {
        return ['is_default' => 'boolean'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
