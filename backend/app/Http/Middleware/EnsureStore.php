<?php

namespace App\Http\Middleware;

use App\Exceptions\ApiException;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureStore
{
    /** Seller harus sudah punya toko sebelum mengelola produk/pesanan. */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()?->store) {
            throw ApiException::badRequest('Buat toko terlebih dahulu sebelum melanjutkan');
        }

        return $next($request);
    }
}
