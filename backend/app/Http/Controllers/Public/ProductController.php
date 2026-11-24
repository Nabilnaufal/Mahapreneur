<?php

namespace App\Http\Controllers\Public;

use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Http\Resources\StoreResource;
use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::query()->visible()->withCard();

        $keyword = $request->query('q');

        if ($keyword) {
            $query->where(function ($search) use ($keyword) {
                $search->where('name', 'like', "%{$keyword}%")
                    ->orWhere('description', 'like', "%{$keyword}%");
            });
        }

        $category = $request->query('category');

        if ($category) {
            $query->whereHas('category', function ($categoryQuery) use ($category) {
                $categoryQuery->where('slug', $category);
            });
        }

        $store = $request->query('store');

        if ($store) {
            $query->whereHas('store', function ($storeQuery) use ($store) {
                $storeQuery->where('slug', $store);
            });
        }

        $type = $request->query('type');

        if ($type) {
            $query->where('type', $type === 'SERVICE' ? 'SERVICE' : 'GOODS');
        }

        if ($request->filled('min_price')) {
            $query->where('price', '>=', (int) $request->query('min_price'));
        }

        if ($request->filled('max_price')) {
            $query->where('price', '<=', (int) $request->query('max_price'));
        }

        if ($request->query('in_stock') === 'true') {
            $query->where('stock', '>', 0);
        }

        match ($request->query('sort')) {
            'price_asc' => $query->orderBy('price'),
            'price_desc' => $query->orderByDesc('price'),
            'popular' => $query->orderByDesc('sold'),
            'oldest' => $query->orderBy('created_at'),
            default => $query->orderByDesc('created_at'),
        };

        $products = $query->paginate($this->perPage($request));

        return $this->ok([
            'products' => ProductResource::collection($products),
            'meta' => $this->meta($products),
        ]);
    }

    public function show(string $slug)
    {
        $product = Product::query()->visible()->withCard()->where('slug', $slug)->first();

        if (! $product) {
            throw ApiException::notFound('Produk tidak ditemukan atau sudah tidak aktif');
        }

        $fromStore = Product::query()->visible()->withCard()
            ->where('store_id', $product->store_id)
            ->whereKeyNot($product->id)
            ->latest('created_at')
            ->take(4)
            ->get();

        $related = Product::query()->visible()->withCard()
            ->where('category_id', $product->category_id)
            ->whereKeyNot($product->id)
            ->orderByDesc('sold')
            ->take(4)
            ->get();

        return $this->ok([
            'product' => ProductResource::make($product),
            'store' => StoreResource::make($product->store),
            'fromStore' => ProductResource::collection($fromStore),
            'related' => ProductResource::collection($related),
        ]);
    }
}
