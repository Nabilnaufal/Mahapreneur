<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Mail\ActionMail;
use App\Models\EmailVerificationToken;
use App\Models\PasswordResetToken;
use App\Models\User;
use App\Support\Activity;
use App\Support\AuthToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{
    /** Registrasi publik. Role superadmin tidak pernah bisa dihasilkan dari sini. */
    public function register(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'min:3', 'max:80'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'password' => ['required', 'string', Password::min(8)->letters()->numbers()],
            'confirm_password' => ['required', 'same:password'],
            'role' => ['required', Rule::in(['CUSTOMER', 'SELLER'])],
        ]);
        $data['email'] = Str::lower($data['email']);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'role' => $data['role'],
        ]);

        $token = $this->issueVerification($user);
        Activity::log($user->id, 'REGISTER', 'User', $user->id, "{$user->name} mendaftar sebagai {$data['role']}");

        return $this->ok([
            'user' => UserResource::make($user),
            // Tanpa SMTP, token dikembalikan agar alur bisa diuji.
            'verificationToken' => config('mahapreneur.mail_enabled') ? null : $token,
        ], 'Registrasi berhasil. Cek email untuk verifikasi.');
    }

    public function verifyEmail(Request $request)
    {
        $data = $request->validate(['token' => ['required', 'string', 'min:10']]);
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

    public function resendVerification(Request $request)
    {
        $data = $request->validate(['email' => ['required', 'email']]);
        $user = User::where('email', Str::lower($data['email']))->first();
        $message = 'Jika email terdaftar dan belum diverifikasi, kami sudah mengirim tautan baru.';

        if (! $user || $user->email_verified_at) {
            return $this->ok(null, $message);
        }

        $token = $this->issueVerification($user);

        return $this->ok(
            ['verificationToken' => config('mahapreneur.mail_enabled') ? null : $token],
            $message,
        );
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::with('store')->where('email', Str::lower($data['email']))->first();
        if (! $user || ! Hash::check($data['password'], $user->password)) {
            throw ApiException::unauthorized('Email atau password salah');
        }
        if (! $user->is_active) {
            throw ApiException::forbidden('Akun kamu dinonaktifkan. Hubungi admin Mahapreneur.');
        }
        if (! $user->email_verified_at) {
            throw new ApiException(403, 'Email belum diverifikasi. Cek inbox atau minta kirim ulang.', ['code' => 'EMAIL_NOT_VERIFIED']);
        }

        Activity::log($user->id, 'LOGIN', 'User', $user->id, "{$user->name} masuk ke aplikasi");

        return $this->ok(['user' => UserResource::make($user)], 'Berhasil masuk')
            ->cookie(AuthToken::cookie(AuthToken::issue($user)));
    }

    public function logout()
    {
        return $this->ok(null, 'Berhasil keluar')->cookie(AuthToken::forget());
    }

    public function me(Request $request)
    {
        return $this->ok(['user' => UserResource::make($request->user()->load('store'))]);
    }

    public function forgotPassword(Request $request)
    {
        $data = $request->validate(['email' => ['required', 'email']]);
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
        $this->sendMail(
            $user,
            'Reset password Mahapreneur',
            "Kami menerima permintaan reset password untuk {$user->name}. Klik tombol di bawah untuk membuat password baru.",
            'Buat Password Baru',
            config('mahapreneur.app_url')."/auth/reset-password?token={$token}",
        );

        return $this->ok(
            ['resetToken' => config('mahapreneur.mail_enabled') ? null : $token],
            $message,
        );
    }

    public function resetPassword(Request $request)
    {
        $data = $request->validate([
            'token' => ['required', 'string', 'min:10'],
            'password' => ['required', 'string', Password::min(8)->letters()->numbers()],
            'confirm_password' => ['required', 'same:password'],
        ]);
        $record = PasswordResetToken::where('token', $data['token'])->first();

        if (! $record || $record->used_at) {
            throw ApiException::badRequest('Token reset tidak valid atau sudah dipakai');
        }
        if ($record->expires_at->isPast()) {
            throw ApiException::badRequest('Token reset sudah kedaluwarsa. Minta tautan baru.');
        }

        DB::transaction(function () use ($record, $data) {
            User::whereKey($record->user_id)->update(['password' => Hash::make($data['password'])]);
            $record->update(['used_at' => now()]);
        });
        Activity::log($record->user_id, 'RESET_PASSWORD', 'User', $record->user_id, 'Password diubah melalui tautan reset');

        return $this->ok(null, 'Password berhasil diubah. Silakan masuk dengan password baru.');
    }

    private function issueVerification(User $user): string
    {
        $token = Str::random(64);
        EmailVerificationToken::updateOrCreate(
            ['user_id' => $user->id],
            ['token' => $token, 'expires_at' => now()->addHours(config('mahapreneur.email_verification_ttl_hours')), 'used_at' => null],
        );
        $this->sendMail(
            $user,
            'Verifikasi email Mahapreneur',
            "Halo {$user->name}, satu langkah lagi sebelum kamu bisa masuk. Klik tombol di bawah untuk memverifikasi alamat email ini.",
            'Verifikasi Email',
            config('mahapreneur.app_url')."/auth/verify-email?token={$token}",
        );

        return $token;
    }

    private function sendMail(User $user, string $title, string $body, string $ctaLabel, string $ctaUrl): void
    {
        Mail::to($user->email)->send(new ActionMail($title, $body, $ctaLabel, $ctaUrl));
    }
}
