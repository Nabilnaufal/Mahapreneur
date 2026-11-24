<?php

namespace App\Http\Middleware;

use App\Exceptions\ApiException;
use App\Support\AuthToken;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class Authenticate
{
    /** Muat ulang user dari database agar akun nonaktif langsung kehilangan akses. */
    public function handle(Request $request, Closure $next): Response
    {
        $user = AuthToken::user($request);
        if (! $user) {
            throw ApiException::unauthorized();
        }
        if (! $user->is_active) {
            throw ApiException::forbidden('Akun kamu dinonaktifkan. Hubungi admin Mahapreneur.');
        }

        $request->setUserResolver(fn () => $user);

        return $next($request);
    }
}
