<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Support\Activity;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $users = User::with('store')->withCount('orders')
            ->when($request->query('q'), fn ($query, string $keyword) => $query->where(
                fn ($search) => $search->where('name', 'like', "%{$keyword}%")
                    ->orWhere('email', 'like', "%{$keyword}%"),
            ))
            ->when($request->query('role'), fn ($query, string $role) => $query->where('role', $role))
            ->when($request->query('status') === 'active', fn ($query) => $query->where('is_active', true))
            ->when($request->query('status') === 'inactive', fn ($query) => $query->where('is_active', false))
            ->when($request->query('verified') === 'true', fn ($query) => $query->whereNotNull('email_verified_at'))
            ->when($request->query('verified') === 'false', fn ($query) => $query->whereNull('email_verified_at'))
            ->latest('created_at')
            ->paginate($this->perPage($request));

        return $this->ok([
            'users' => UserResource::collection($users),
            'meta' => $this->meta($users),
        ]);
    }

    public function show(int $id)
    {
        $user = User::with(['store' => fn ($query) => $query->withCount(['products', 'orders'])])
            ->withCount(['orders', 'addresses', 'wishlists'])
            ->find($id);
        if (! $user) {
            throw ApiException::notFound('Pengguna tidak ditemukan');
        }

        return $this->ok(['user' => UserResource::make($user)]);
    }

    public function updateStatus(Request $request, int $id)
    {
        $data = $request->validate([
            'is_active' => ['required', 'boolean'],
            'reason' => ['nullable', 'string', 'max:300'],
        ]);
        $target = User::find($id);
        if (! $target) {
            throw ApiException::notFound('Pengguna tidak ditemukan');
        }
        if ($target->id === $request->user()->id) {
            throw ApiException::badRequest('Kamu tidak dapat menonaktifkan akunmu sendiri');
        }
        if (! $data['is_active'] && $target->role === 'SUPERADMIN') {
            throw ApiException::forbidden('Akun superadmin lain tidak dapat dinonaktifkan');
        }

        $target->update(['is_active' => $data['is_active']]);
        Activity::log(
            $request->user()->id,
            $data['is_active'] ? 'USER_ACTIVATED' : 'USER_DEACTIVATED',
            'User',
            $target->id,
            "Akun {$target->name} ({$target->email}) ".($data['is_active'] ? 'diaktifkan' : 'dinonaktifkan'),
            isset($data['reason']) ? ['reason' => $data['reason']] : null,
        );

        return $this->ok(['user' => UserResource::make($target)], 'Akun berhasil '.($data['is_active'] ? 'diaktifkan' : 'dinonaktifkan'));
    }
}
