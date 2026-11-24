<?php

namespace App\Models;

class PaymentNotification extends Model
{
    protected $fillable = [
        'notification_key',
        'invoice_number',
        'order_number',
        'transaction_status',
        'amount',
        'signature_valid',
        'processed',
        'note',
        'raw_payload',
    ];

    const UPDATED_AT = null;

    protected $casts = [
        'amount' => 'integer',
        'signature_valid' => 'boolean',
        'processed' => 'boolean',
        'raw_payload' => 'array',
    ];
}
