<?php

namespace App\Models;

class PaymentNotification extends Model
{
    const UPDATED_AT = null;

    protected function casts()
    {
        return [
            'amount' => 'integer',
            'signature_valid' => 'boolean',
            'processed' => 'boolean',
            'raw_payload' => 'array',
        ];
    }
}
