<?php

namespace App\Http\Controllers\Auth;

use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Models\PasswordResetToken;
use App\Models\User;
use App\Support\Activity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class NewPasswordController extends Controller
{
    public function store(Request $request)
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
}
