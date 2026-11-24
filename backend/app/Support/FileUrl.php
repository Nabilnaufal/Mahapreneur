<?php

namespace App\Support;

use Illuminate\Support\Str;

class FileUrl
{
    /** Path file lokal menjadi URL absolut yang bisa dipakai frontend. */
    public static function make(?string $path): ?string
    {
        if (blank($path)) {
            return null;
        }

        if (Str::startsWith($path, ['http://', 'https://'])) {
            return $path;
        }

        return rtrim(config('mahapreneur.api_url'), '/').'/'.ltrim($path, '/');
    }
}
