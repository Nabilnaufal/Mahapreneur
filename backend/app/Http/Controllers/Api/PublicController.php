<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Resources\BannerResource;
use App\Http\Resources\CategoryResource;
use App\Http\Resources\ProductResource;
use App\Http\Resources\StoreResource;
use App\Models\Banner;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class PublicController extends Controller
{
    /** Data homepage dari data nyata. */
    public function home()
    {
        $products = Product::query()->visible()->withCard();

        $banners = Banner::where('status', 'ACTIVE')->orderBy('sort_order')->get();
        $categories = Category::where('is_active', true)
            ->withCount(['products' => fn (Builder $query) => $query->visible()])
            ->orderBy('sort_order')
            ->get();
        $latest = (clone $products)->latest('created_at')->take(8)->get();
        $bestsellers = (clone $products)->orderByDesc('sold')->take(8)->get()->where('sold', '>', 0)->values();
        $recommended = (clone $products)->where('sold', 0)->where('stock', '>', 0)->latest('created_at')->take(8)->get();
        $topStores = Store::active()->withCount('products')->latest('created_at')->take(6)->get();

        $categorySections = $categories->take(4)
            ->map(fn (Category $category) => [
                'category' => CategoryResource::make($category),
                'products' => ProductResource::collection(
                    (clone $products)->where('category_id', $category->id)->latest('created_at')->take(4)->get(),
                ),
            ])
            ->filter(fn (array $section) => count($section['products']) > 0)
            ->values();

        return $this->ok([
            'banners' => BannerResource::collection($banners),
            'categories' => CategoryResource::collection($categories),
            'latest' => ProductResource::collection($latest),
            'bestsellers' => ProductResource::collection($bestsellers),
            'recommended' => ProductResource::collection($recommended),
            'categorySections' => $categorySections,
            'topStores' => StoreResource::collection($topStores),
            'stats' => [
                'sellers' => User::where('role', 'SELLER')->count(),
                'stores' => Store::count(),
                'products' => Product::query()->visible()->count(),
                'orders' => Order::count(),
            ],
        ]);
    }

    public function categories()
    {
        $categories = Category::where('is_active', true)
            ->withCount(['products' => fn (Builder $query) => $query->visible()])
            ->orderBy('sort_order')
            ->get();

        return $this->ok(['categories' => CategoryResource::collection($categories)]);
    }

    public function products(Request $request)
    {
        $query = Product::query()->visible()->withCard()->when(
            $request->query('q'),
            fn (Builder $query, string $keyword) => $query->where(
                fn (Builder $search) => $search->where('name', 'like', "%{$keyword}%")
                    ->orWhere('description', 'like', "%{$keyword}%"),
            ),
        );

        $query->when($request->query('category'), fn (Builder $query, string $slug) => $query->whereHas('category', fn (Builder $category) => $category->where('slug', $slug)))
            ->when($request->query('store'), fn (Builder $query, string $slug) => $query->whereHas('store', fn (Builder $store) => $store->where('slug', $slug)))
            ->when($request->query('type'), fn (Builder $query, string $type) => $query->where('type', $type === 'SERVICE' ? 'SERVICE' : 'GOODS'))
            ->when($request->filled('min_price'), fn (Builder $query) => $query->where('price', '>=', (int) $request->query('min_price')))
            ->when($request->filled('max_price'), fn (Builder $query) => $query->where('price', '<=', (int) $request->query('max_price')))
            ->when($request->query('in_stock') === 'true', fn (Builder $query) => $query->where('stock', '>', 0));

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

    public function productDetail(string $slug)
    {
        $product = Product::query()->visible()->withCard()->where('slug', $slug)->first();
        if (! $product) {
            throw ApiException::notFound('Produk tidak ditemukan atau sudah tidak aktif');
        }

        $fromStore = Product::query()->visible()->withCard()
            ->where('store_id', $product->store_id)->whereKeyNot($product->id)
            ->latest('created_at')->take(4)->get();
        $related = Product::query()->visible()->withCard()
            ->where('category_id', $product->category_id)->whereKeyNot($product->id)
            ->orderByDesc('sold')->take(4)->get();

        return $this->ok([
            'product' => ProductResource::make($product),
            'store' => StoreResource::make($product->store),
            'fromStore' => ProductResource::collection($fromStore),
            'related' => ProductResource::collection($related),
        ]);
    }

    public function stores(Request $request)
    {
        $stores = Store::active()
            ->withCount(['products' => fn (Builder $query) => $query->visible()])
            ->when($request->query('q'), fn (Builder $query, string $keyword) => $query->where(
                fn (Builder $search) => $search->where('name', 'like', "%{$keyword}%")
                    ->orWhere('description', 'like', "%{$keyword}%"),
            ))
            ->latest('created_at')
            ->paginate($this->perPage($request));

        return $this->ok([
            'stores' => StoreResource::collection($stores),
            'meta' => $this->meta($stores),
        ]);
    }

    public function storeDetail(Request $request, string $slug)
    {
        $store = Store::active()->with('user:id,name,created_at')
            ->withCount(['products' => fn (Builder $query) => $query->visible()])
            ->where('slug', $slug)
            ->first();
        if (! $store) {
            throw ApiException::notFound('Toko tidak ditemukan');
        }

        $products = Product::query()->visible()->withCard()
            ->where('store_id', $store->id)
            ->when($request->query('category'), fn (Builder $query, string $category) => $query->whereHas('category', fn (Builder $category) => $category->where('slug', $category)))
            ->when($request->query('q'), fn (Builder $query, string $keyword) => $query->where('name', 'like', "%{$keyword}%"));

        match ($request->query('sort')) {
            'price_asc' => $products->orderBy('price'),
            'price_desc' => $products->orderByDesc('price'),
            'popular' => $products->orderByDesc('sold'),
            'oldest' => $products->orderBy('created_at'),
            default => $products->orderByDesc('created_at'),
        };

        $paged = $products->paginate($this->perPage($request));
        $categories = Category::where('is_active', true)
            ->whereHas('products', fn (Builder $query) => $query->where('store_id', $store->id)->where('status', 'ACTIVE'))
            ->orderBy('sort_order')
            ->get();

        return $this->ok([
            'store' => StoreResource::make($store),
            'owner' => $store->user ? [
                'name' => $store->user->name,
                'createdAt' => $store->user->created_at?->toISOString(),
            ] : null,
            'products' => ProductResource::collection($paged),
            'categories' => CategoryResource::collection($categories),
            'meta' => $this->meta($paged),
        ]);
    }
}
