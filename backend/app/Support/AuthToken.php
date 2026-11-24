<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Symfony\Component\HttpFoundation\Cookie;
use Throwable;

/** Token API dienkripsi Laravel lalu disimpan pada cookie httpOnly. */
class AuthToken
{
    public static function issue(User $user): string
    {
        return Crypt::encryptString(json_encode([
            'user_id' => $user->id,
            'expires_at' => now()->addDays(config('mahapreneur.auth_ttl_days'))->timestamp,
        ]));
    }

    public static function user(Request $request): ?User
    {
        $token = $request->cookie(config('mahapreneur.auth_cookie')) ?: $request->bearerToken();
        if (blank($token)) {
            return null;
        }

        try {
            $payload = json_decode(Crypt::decryptString($token), true);
        } catch (Throwable) {
            return null;
        }

        if (($payload['expires_at'] ?? 0) < now()->timestamp) {
            return null;
        }

        return User::with('store:id,user_id,name,slug,status,logo')->find($payload['user_id'] ?? 0);
    }

    public static function cookie(string $token): Cookie
    {
        $production = app()->isProduction();

        return cookie(
            config('mahapreneur.auth_cookie'),
            $token,
            config('mahapreneur.auth_ttl_days') * 24 * 60,
            '/',
            null,
            $production,
            true,
            false,
            $production ? 'none' : 'lax',
        );
    }

    public static function forget(): Cookie
    {
        return cookie()->forget(config('mahapreneur.auth_cookie'));
    }
}
