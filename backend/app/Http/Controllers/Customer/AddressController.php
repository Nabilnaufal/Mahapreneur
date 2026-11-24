<?php

namespace App\Http\Controllers\Customer;

use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Models\Address;
use Illuminate\Http\Request;

class AddressController extends Controller
{
    public function listAddresses(Request $request)
    {
        $addresses = Address::where('user_id', $request->user()->id)
            ->orderByDesc('is_default')
            ->latest('created_at')
            ->get();

        return $this->ok(['addresses' => $addresses]);
    }

    public function createAddress(Request $request)
    {
        $data = $request->validate($this->addressRules());
        $userId = $request->user()->id;

        if ($data['is_default'] ?? false) {
            Address::where('user_id', $userId)->update(['is_default' => false]);
        }

        $address = Address::create($data + ['user_id' => $userId]);

        return $this->ok(['address' => $address], 'Alamat ditambahkan');
    }

    public function updateAddress(Request $request, int $id)
    {
        $address = Address::where('user_id', $request->user()->id)->find($id);
        if (! $address) {
            throw ApiException::notFound('Alamat tidak ditemukan');
        }

        $data = $request->validate($this->addressRules(partial: true));
        if ($data['is_default'] ?? false) {
            Address::where('user_id', $request->user()->id)->update(['is_default' => false]);
        }

        $address->update($data);

        return $this->ok(['address' => $address], 'Alamat diperbarui');
    }

    public function deleteAddress(Request $request, int $id)
    {
        $address = Address::where('user_id', $request->user()->id)->find($id);
        if (! $address) {
            throw ApiException::notFound('Alamat tidak ditemukan');
        }
        $address->delete();

        return $this->ok(null, 'Alamat dihapus');
    }

    private function addressRules(bool $partial = false)
    {
        $presence = $partial ? 'sometimes' : 'required';

        return [
            'label' => [$presence, 'string', 'min:2', 'max:40'],
            'recipient_name' => [$presence, 'string', 'min:3', 'max:80'],
            'phone' => [$presence, 'string', 'min:8', 'max:20'],
            'address' => [$presence, 'string', 'min:10'],
            'campus' => ['nullable', 'string', 'max:120'],
            'room' => ['nullable', 'string', 'max:60'],
            'is_default' => ['sometimes', 'boolean'],
        ];
    }
}
