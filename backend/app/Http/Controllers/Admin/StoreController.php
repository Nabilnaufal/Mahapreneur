<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Resources\StoreResource;
use App\Models\Store;
use App\Support\Activity;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class StoreController extends Controller
{
    public function index(Request $request)
    {
        $stores = Store::with('user')
            ->withCount(['products', 'orders'])
            ->when($request->query('q'), fn ($query, string $keyword) => $query->where(
                fn ($search) => $search->where('name', 'like', "%{$keyword}%")
                    ->orWhere('slug', 'like', "%{$keyword}%"),
            ))
            ->when($request->query('status'), fn ($query, string $status) => $query->where('status', $status))
            ->latest('created_at')
            ->paginate($this->perPage($request));

        return $this->ok([
            'stores' => StoreResource::collection($stores),
            'meta' => $this->meta($stores),
        ]);
    }

    public function updateStatus(Request $request, int $id)
    {
        $data = $request->validate(['status' => ['required', Rule::in(['ACTIVE', 'INACTIVE', 'SUSPENDED'])]]);
        $store = Store::find($id);
        if (! $store) {
            throw ApiException::notFound('Toko tidak ditemukan');
        }

        $store->update(['status' => $data['status']]);
        Activity::log($request->user()->id, 'STORE_STATUS_UPDATED', 'Store', $store->id, "Status toko {$store->name} menjadi {$data['status']}");

        return $this->ok(['store' => StoreResource::make($store)], 'Status toko diperbarui');
    }
}
