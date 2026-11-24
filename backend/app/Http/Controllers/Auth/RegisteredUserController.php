<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\EmailVerificationService;
use App\Support\Activity;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class RegisteredUserController extends Controller
{
    public function store(Request $request)
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

        $token = EmailVerificationService::send($user);
        Activity::log($user->id, 'REGISTER', 'User', $user->id, "{$user->name} mendaftar sebagai {$data['role']}");

        return $this->ok([
            'user' => UserResource::make($user),
            // Tanpa SMTP, token dikembalikan agar alur bisa diuji.
            'verificationToken' => config('mahapreneur.mail_enabled') ? null : $token,
        ], 'Registrasi berhasil. Cek email untuk verifikasi.');
    }
}
