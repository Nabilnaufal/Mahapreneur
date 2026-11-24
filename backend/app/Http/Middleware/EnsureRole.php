<?php

namespace App\Http\Middleware;

use App\Exceptions\ApiException;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();
        if (! $user) {
            throw ApiException::unauthorized();
        }
        if (! in_array($user->role, $roles, true)) {
            throw ApiException::forbidden();
        }

        return $next($request);
    }
}
