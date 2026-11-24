<?php

namespace App\Models;

class PaymentNotification extends Model
{
    protected $guarded = [];

    const UPDATED_AT = null;

    protected $casts = [
        'amount' => 'integer',
        'signature_valid' => 'boolean',
        'processed' => 'boolean',
        'raw_payload' => 'array',
    ];
}
