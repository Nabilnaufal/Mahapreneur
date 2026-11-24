<?php

namespace App\Http\Controllers\Auth;

use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Models\EmailVerificationToken;
use App\Support\Activity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VerifyEmailController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'token' => ['required', 'string', 'min:10'],
        ]);

        $record = EmailVerificationToken::with('user')->where('token', $data['token'])->first();

        if (! $record) {
            throw ApiException::badRequest('Token verifikasi tidak valid');
        }

        if ($record->used_at) {
            throw ApiException::badRequest('Email sudah diverifikasi sebelumnya');
        }

        if ($record->expires_at->isPast()) {
            throw ApiException::badRequest('Token verifikasi sudah kedaluwarsa. Minta email baru.');
        }

        DB::transaction(function () use ($record) {
            $record->user->update(['email_verified_at' => now()]);
            $record->update(['used_at' => now()]);
        });

        Activity::log($record->user_id, 'VERIFY_EMAIL', 'User', $record->user_id, "{$record->user->name} memverifikasi email");
        Activity::notify($record->user_id, 'Email terverifikasi', 'Akunmu sudah aktif. Selamat berbelanja di Mahapreneur!', 'success');

        return $this->ok(['email' => $record->user->email], 'Email berhasil diverifikasi. Silakan masuk.');
    }
}
