<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\EmailVerificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class EmailVerificationNotificationController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
        ]);

        $user = User::where('email', Str::lower($data['email']))->first();
        $message = 'Jika email terdaftar dan belum diverifikasi, kami sudah mengirim tautan baru.';

        if (! $user || $user->email_verified_at) {
            return $this->ok(null, $message);
        }

        $token = EmailVerificationService::send($user);

        return $this->ok(
            ['verificationToken' => config('mahapreneur.mail_enabled') ? null : $token],
            $message,
        );
    }
}
