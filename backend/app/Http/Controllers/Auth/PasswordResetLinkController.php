<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\ActionMail;
use App\Models\PasswordResetToken;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class PasswordResetLinkController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
        ]);

        $user = User::where('email', Str::lower($data['email']))->first();
        $message = 'Jika email terdaftar, kami sudah mengirim tautan reset password.';

        if (! $user) {
            return $this->ok(null, $message);
        }

        $token = Str::random(64);

        PasswordResetToken::create([
            'user_id' => $user->id,
            'token' => $token,
            'expires_at' => now()->addMinutes(config('mahapreneur.password_reset_ttl_minutes')),
        ]);

        if (config('mahapreneur.mail_enabled')) {
            Mail::to($user->email)->send(new ActionMail(
                'Reset password Mahapreneur',
                "Kami menerima permintaan reset password untuk {$user->name}. Klik tombol di bawah untuk membuat password baru.",
                'Buat Password Baru',
                config('mahapreneur.app_url')."/auth/reset-password?token={$token}",
            ));
        }

        return $this->ok(
            ['resetToken' => config('mahapreneur.mail_enabled') ? null : $token],
            $message,
        );
    }
}
