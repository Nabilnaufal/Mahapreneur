<?php

namespace App\Http\Controllers\Customer;

use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Support\Activity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class ProfileController extends Controller
{
    public function show(Request $request)
    {
        return $this->ok(['user' => UserResource::make($request->user())]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'name' => ['sometimes', 'string', 'min:3', 'max:80'],
            'phone' => ['sometimes', 'nullable', 'string', 'max:20'],
            'avatar' => ['sometimes', 'nullable', 'string', 'max:300'],
        ]);
        if (array_key_exists('phone', $data)) {
            $data['phone'] = $data['phone'] ?: null;
        }
        if (array_key_exists('avatar', $data)) {
            $data['avatar'] = $data['avatar'] ?: null;
        }

        $request->user()->update($data);

        return $this->ok(['user' => UserResource::make($request->user())], 'Profil diperbarui');
    }

    public function changePassword(Request $request)
    {
        $data = $request->validate([
            'current_password' => ['required', 'string'],
            'new_password' => ['required', 'string', Password::min(8)],
            'confirm_password' => ['required', 'same:new_password'],
        ]);

        $user = $request->user();
        if (! Hash::check($data['current_password'], $user->password)) {
            throw ApiException::badRequest('Password saat ini salah');
        }

        $user->update(['password' => $data['new_password']]);
        Activity::log($user->id, 'CHANGE_PASSWORD', 'User', $user->id, 'Password diubah dari halaman profil');

        return $this->ok(null, 'Password berhasil diubah');
    }
}
