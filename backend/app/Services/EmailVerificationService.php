<?php

namespace App\Services;

use App\Mail\ActionMail;
use App\Models\EmailVerificationToken;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class EmailVerificationService
{
    public static function send(User $user): string
    {
        $token = Str::random(64);

        EmailVerificationToken::updateOrCreate(
            ['user_id' => $user->id],
            [
                'token' => $token,
                'expires_at' => now()->addHours(config('mahapreneur.email_verification_ttl_hours')),
                'used_at' => null,
            ],
        );

        Mail::to($user->email)->send(new ActionMail(
            'Verifikasi email Mahapreneur',
            "Halo {$user->name}, satu langkah lagi sebelum kamu bisa masuk. Klik tombol di bawah untuk memverifikasi alamat email ini.",
            'Verifikasi Email',
            config('mahapreneur.app_url')."/auth/verify-email?token={$token}",
        ));

        return $token;
    }
}
