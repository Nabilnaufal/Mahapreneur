<?php

namespace App\Http\Controllers\Seller;

use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Resources\StoreResource;
use App\Models\Store;
use App\Support\Activity;
use App\Support\Reference;
use App\Support\Uploads;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class StoreController extends Controller
{
    public function show(Request $request)
    {
        $store = Store::withCount('products')->find($this->storeId($request));

        return $this->ok(['store' => $store ? StoreResource::make($store) : null]);
    }

    public function store(Request $request)
    {
        $user = $request->user();
        if ($user->store) {
            throw ApiException::conflict('Kamu sudah memiliki toko. Satu seller hanya boleh punya satu toko.');
        }

        $data = $request->validate($this->storeRules());
        $slug = Reference::slug($data['slug'] ?? $data['name']);
        if (Store::where('slug', $slug)->exists()) {
            if (! empty($data['slug'])) {
                throw ApiException::conflict('Username toko sudah dipakai');
            }
            $slug .= '-'.Str::lower(Str::random(4));
        }

        if (isset($data['name'])) {
            $storeData['name'] = $data['name'];
        }
        $storeData['user_id'] = $user->id;
        $storeData['slug'] = $slug;
        foreach ($data as $key => $value) {
            if ($key !== 'name' && $key !== 'slug') {
                $storeData[$key] = $value;
            }
        }
        $store = Store::create($storeData);
        Activity::log($user->id, 'STORE_CREATED', 'Store', $store->id, "Toko {$store->name} dibuat");

        return $this->ok(['store' => StoreResource::make($store)], 'Toko berhasil dibuat');
    }

    public function update(Request $request)
    {
        $store = $request->user()->store;
        $data = $request->validate($this->storeRules(partial: true, ignoreId: $store->id));

        if (array_key_exists('slug', $data)) {
            if (blank($data['slug'])) {
                unset($data['slug']);
            } else {
                $data['slug'] = Reference::slug($data['slug']);
            }
        }
        $store->update($data);

        return $this->ok(['store' => StoreResource::make($store)], 'Toko diperbarui');
    }

    public function uploadImage(Request $request, string $kind)
    {
        $request->validate(['image' => ['required', 'image', 'mimes:jpeg,png,webp,gif', 'max:'.config('mahapreneur.max_upload_kb')]]);
        $store = $request->user()->store;
        $store->update([$kind => Uploads::image($request->file('image'), 'stores')]);

        return $this->ok(['store' => StoreResource::make($store)], ($kind === 'logo' ? 'Logo' : 'Banner').' toko diperbarui');
    }

    private function storeId(Request $request): int
    {
        return $request->user()->store->id;
    }

    private function storeRules(bool $partial = false, ?int $ignoreId = null)
    {
        $presence = $partial ? 'sometimes' : 'required';

        return [
            'name' => [$presence, 'string', 'min:3', 'max:60'],
            'slug' => ['sometimes', 'nullable', 'string', 'max:60', Rule::unique('stores', 'slug')->ignore($ignoreId)],
            'description' => ['nullable', 'string', 'max:1000'],
            'phone' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string', 'max:300'],
            'campus' => ['nullable', 'string', 'max:120'],
            'open_time' => ['nullable', 'string', 'max:10'],
            'close_time' => ['nullable', 'string', 'max:10'],
            'status' => ['sometimes', Rule::in(['ACTIVE', 'INACTIVE'])],
        ];
    }
}
