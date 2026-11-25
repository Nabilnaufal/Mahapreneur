<?php

namespace App\Http\Controllers\Public;

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

class HomeController extends Controller
{
    public function index()
    {
        $productQuery = Product::query()->visible()->withCard();

        $banners = Banner::where('status', 'ACTIVE')->orderBy('sort_order')->get();

        $categories = Category::where('is_active', true)
            ->withCount(['products' => function ($query) {
                $query->visible();
            }])
            ->orderBy('sort_order')
            ->get();

        $latest = (clone $productQuery)->latest('created_at')->take(8)->get();
        $bestsellers = (clone $productQuery)->orderByDesc('sold')->take(8)->get()->where('sold', '>', 0)->values();
        $recommended = (clone $productQuery)->where('sold', 0)->where('stock', '>', 0)->latest('created_at')->take(8)->get();
        $topStores = Store::active()
            ->withAvg('reviews', 'rating')
            ->withCount(['reviews', 'products'])
            ->latest('created_at')
            ->take(6)
            ->get();

        $categorySections = $categories->take(4)
            ->map(function (Category $category) use ($productQuery) {
                $products = (clone $productQuery)
                    ->where('category_id', $category->id)
                    ->latest('created_at')
                    ->take(4)
                    ->get();

                return [
                    'category' => CategoryResource::make($category),
                    'products' => ProductResource::collection($products),
                ];
            })
            ->filter(function (array $section) {
                return count($section['products']) > 0;
            })
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
}
