<?php

namespace App\Http\Controllers\Auth;

use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Support\Activity;
use App\Support\AuthToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AuthenticatedSessionController extends Controller
{
    public function store(Request $request)
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

    public function destroy()
    {
        return $this->ok(null, 'Berhasil keluar')->cookie(AuthToken::forget());
    }

    public function show(Request $request)
    {
        return $this->ok(['user' => UserResource::make($request->user()->load('store'))]);
    }
}
