<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Support\Activity;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $products = Product::with([
            'images' => fn ($query) => $query->orderBy('sort_order')->take(1),
            'category:id,name,slug,icon',
            'store:id,name,slug,logo',
        ])
            ->when($request->query('q'), fn ($query, string $keyword) => $query->where('name', 'like', "%{$keyword}%"))
            ->when($request->query('status'), fn ($query, string $status) => $query->where('status', $status))
            ->when($request->query('store_id'), fn ($query, int $storeId) => $query->where('store_id', $storeId))
            ->latest('created_at')
            ->paginate($this->perPage($request));

        return $this->ok([
            'products' => ProductResource::collection($products),
            'meta' => $this->meta($products),
        ]);
    }

    public function destroy(Request $request, int $id)
    {
        $product = Product::with('store')->find($id);
        if (! $product) {
            throw ApiException::notFound('Produk tidak ditemukan');
        }

        $data = $request->validate(['reason' => ['nullable', 'string', 'max:300']]);
        $product->delete();
        Activity::log(
            $request->user()->id,
            'PRODUCT_REMOVED_BY_ADMIN',
            'Product',
            $product->id,
            "Produk {$product->name} dari toko {$product->store->name} dihapus admin",
            isset($data['reason']) ? ['reason' => $data['reason']] : null,
        );

        return $this->ok(null, 'Produk dihapus');
    }
}
