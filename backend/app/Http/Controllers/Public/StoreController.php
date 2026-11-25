<?php

namespace App\Http\Controllers\Public;

use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryResource;
use App\Http\Resources\ProductResource;
use App\Http\Resources\StoreResource;
use App\Models\Category;
use App\Models\Product;
use App\Models\Store;
use Illuminate\Http\Request;

class StoreController extends Controller
{
    public function index(Request $request)
    {
        $query = Store::active()
            ->withAvg('reviews', 'rating')
            ->withCount('reviews')
            ->withCount(['products' => function ($productQuery) {
                $productQuery->visible();
            }]);

        $keyword = $request->query('q');

        if ($keyword) {
            $query->where(function ($search) use ($keyword) {
                $search->where('name', 'like', "%{$keyword}%")
                    ->orWhere('description', 'like', "%{$keyword}%");
            });
        }

        $stores = $query->latest('created_at')->paginate($this->perPage($request));

        return $this->ok([
            'stores' => StoreResource::collection($stores),
            'meta' => $this->meta($stores),
        ]);
    }

    public function show(Request $request, string $slug)
    {
        $store = Store::active()
            ->with('user:id,name,created_at')
            ->withAvg('reviews', 'rating')
            ->withCount('reviews')
            ->withCount(['products' => function ($productQuery) {
                $productQuery->visible();
            }])
            ->where('slug', $slug)
            ->first();

        if (! $store) {
            throw ApiException::notFound('Toko tidak ditemukan');
        }

        $productQuery = Product::query()->visible()->withCard()->where('store_id', $store->id);

        $category = $request->query('category');

        if ($category) {
            $productQuery->whereHas('category', function ($categoryQuery) use ($category) {
                $categoryQuery->where('slug', $category);
            });
        }

        $keyword = $request->query('q');

        if ($keyword) {
            $productQuery->where('name', 'like', "%{$keyword}%");
        }

        match ($request->query('sort')) {
            'price_asc' => $productQuery->orderBy('price'),
            'price_desc' => $productQuery->orderByDesc('price'),
            'popular' => $productQuery->orderByDesc('sold'),
            'oldest' => $productQuery->orderBy('created_at'),
            default => $productQuery->orderByDesc('created_at'),
        };

        $products = $productQuery->paginate($this->perPage($request));

        $categories = Category::where('is_active', true)
            ->whereHas('products', function ($productQuery) use ($store) {
                $productQuery->where('store_id', $store->id)->where('status', 'ACTIVE');
            })
            ->orderBy('sort_order')
            ->get();

        return $this->ok([
            'store' => StoreResource::make($store),
            'owner' => $store->user ? [
                'name' => $store->user->name,
                'createdAt' => $store->user->created_at?->toISOString(),
            ] : null,
            'products' => ProductResource::collection($products),
            'categories' => CategoryResource::collection($categories),
            'meta' => $this->meta($products),
        ]);
    }
}
