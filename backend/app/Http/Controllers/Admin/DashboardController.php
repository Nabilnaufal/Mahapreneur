<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\StoreResource;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;

class DashboardController extends Controller
{
    public function index()
    {
        $recent = Order::where('created_at', '>=', now()->subDays(6)->startOfDay())
            ->get(['total', 'created_at', 'payment_status']);

        $chart = collect(range(6, 0))->map(function (int $daysAgo) use ($recent) {
            $day = now()->subDays($daysAgo);
            $key = $day->toDateString();
            $orders = $recent->filter(fn (Order $order) => $order->created_at->toDateString() === $key);

            return [
                'date' => $key,
                'label' => $day->locale('id')->isoFormat('ddd'),
                'orders' => $orders->count(),
                'revenue' => $orders->where('payment_status', 'PAID')->sum('total'),
            ];
        });

        $topStores = Store::withCount(['products', 'orders'])
            ->orderByDesc('orders_count')
            ->take(5)
            ->get();
        $categories = Category::withCount('products')->orderBy('sort_order')->get();

        return $this->ok([
            'stats' => [
                'totalUsers' => User::count(),
                'totalSellers' => User::where('role', 'SELLER')->count(),
                'totalCustomers' => User::where('role', 'CUSTOMER')->count(),
                'totalStores' => Store::count(),
                'totalProducts' => Product::count(),
                'totalOrders' => Order::count(),
                'paidOrders' => Order::where('payment_status', 'PAID')->count(),
                'pendingPayment' => Order::where('status', 'WAITING_PAYMENT')->count(),
                'revenue' => (int) Order::where('payment_status', 'PAID')->sum('total'),
            ],
            'chart' => $chart,
            'categoryBreakdown' => $categories->map(fn (Category $category) => [
                'name' => $category->name,
                'products' => $category->products_count,
            ]),
            'recentOrders' => Order::with(['store:id,name,slug', 'customer:id,name'])
                ->latest('created_at')
                ->take(8)
                ->get(),
            'topStores' => StoreResource::collection($topStores),
        ]);
    }
}
