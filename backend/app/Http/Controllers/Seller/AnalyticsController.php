<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductView;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class AnalyticsController extends Controller
{
    public function index(Request $request)
    {
        $storeId = $request->user()->store->id;
        $periodDays = 30;
        $periodStart = CarbonImmutable::now()->subDays($periodDays - 1)->startOfDay();

        $successfulOrders = fn (Builder $query) => $query
            ->where('store_id', $storeId)
            ->where('status', '!=', 'CANCELLED')
            ->where(function (Builder $payment) {
                $payment->where('payment_status', 'PAID')
                    ->orWhere('status', 'COMPLETED');
            });

        $totalViews = ProductView::query()->where('store_id', $storeId)->count();
        $totalOrders = $successfulOrders(Order::query())->count();
        $revenue = (int) $successfulOrders(Order::query())->sum('total');

        $viewCounts = ProductView::query()
            ->where('store_id', $storeId)
            ->select('product_id')
            ->selectRaw('COUNT(*) as views')
            ->groupBy('product_id')
            ->orderByDesc('views')
            ->limit(5)
            ->get();

        $viewedProducts = Product::query()
            ->where('store_id', $storeId)
            ->whereIn('id', $viewCounts->pluck('product_id'))
            ->withCard()
            ->get()
            ->keyBy('id');

        $topViewed = $viewCounts->map(function ($viewCount) use ($viewedProducts, $request) {
            $product = $viewedProducts->get($viewCount->product_id);

            if (! $product) {
                return null;
            }

            return [
                'views' => (int) $viewCount->views,
                'product' => ProductResource::make($product)->resolve($request),
            ];
        })->filter()->values();

        $viewsByDay = ProductView::query()
            ->where('store_id', $storeId)
            ->where('created_at', '>=', $periodStart)
            ->selectRaw('DATE(created_at) as date, COUNT(*) as total')
            ->groupByRaw('DATE(created_at)')
            ->get()
            ->keyBy('date');

        $ordersByDay = $successfulOrders(Order::query())
            ->where('created_at', '>=', $periodStart)
            ->selectRaw('DATE(created_at) as date, COUNT(*) as total')
            ->groupByRaw('DATE(created_at)')
            ->get()
            ->keyBy('date');

        $chart = collect(range($periodDays - 1, 0))->map(function (int $daysAgo) use ($viewsByDay, $ordersByDay) {
            $date = CarbonImmutable::now()->subDays($daysAgo);
            $key = $date->toDateString();

            return [
                'date' => $key,
                'label' => $date->format('d M'),
                'views' => (int) ($viewsByDay->get($key)->total ?? 0),
                'orders' => (int) ($ordersByDay->get($key)->total ?? 0),
            ];
        })->values();

        return $this->ok([
            'periodDays' => $periodDays,
            'stats' => [
                'productCount' => Product::query()->where('store_id', $storeId)->count(),
                'totalViews' => $totalViews,
                'totalOrders' => $totalOrders,
                'conversionRate' => $totalViews > 0 ? round(($totalOrders / $totalViews) * 100, 1) : 0,
                'revenue' => $revenue,
            ],
            'chart' => $chart,
            'topSold' => ProductResource::collection(
                Product::query()->where('store_id', $storeId)->withCard()->orderByDesc('sold')->take(5)->get(),
            ),
            'topViewed' => $topViewed,
        ]);
    }
}
