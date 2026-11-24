<?php

namespace App\Support;

use Illuminate\Support\Str;

class Reference
{
    /** Nomor pesanan manusiawi: MP-YYMMDD-XXXX */
    public static function orderNumber(): string
    {
        return 'MP-'.now()->format('ymd').'-'.random_int(1000, 9999);
    }

    public static function invoiceNumber(string $orderNumber): string
    {
        return $orderNumber.'-'.Str::upper(base_convert((string) now()->valueOf(), 10, 36));
    }

    public static function slug(string $value, string $fallback = 'toko'): string
    {
        return Str::limit(Str::slug($value) ?: $fallback, 60, '');
    }
}
