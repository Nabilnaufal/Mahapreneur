<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $storeId = $this->storeId($request);
        $orders = Order::where('store_id', $storeId)->latest('created_at')->get([
            'id', 'order_number', 'status', 'payment_status', 'total', 'created_at', 'recipient_name',
        ]);
        $paid = Order::where('store_id', $storeId)
            ->where('payment_status', 'PAID')
            ->where('created_at', '>=', now()->subDays(6)->startOfDay())
            ->get(['total', 'created_at']);

        $chart = collect(range(6, 0))->map(function (int $daysAgo) use ($paid) {
            $day = now()->subDays($daysAgo);
            $key = $day->toDateString();

            return [
                'date' => $key,
                'label' => $day->locale('id')->isoFormat('ddd'),
                'revenue' => $paid->filter(fn (Order $order) => $order->created_at->toDateString() === $key)->sum('total'),
            ];
        });

        $topProducts = Product::where('store_id', $storeId)->withCard()->orderByDesc('sold')->take(5)->get();

        return $this->ok([
            'stats' => [
                'productCount' => Product::where('store_id', $storeId)->count(),
                'totalOrders' => $orders->count(),
                'waitingPayment' => $orders->where('status', 'WAITING_PAYMENT')->count(),
                'needProcessing' => $orders->whereIn('status', ['CONFIRMED', 'PROCESSING', 'READY'])->count(),
                'completed' => $orders->where('status', 'COMPLETED')->count(),
                'revenue' => (int) Order::where('store_id', $storeId)->where('payment_status', 'PAID')->sum('total'),
            ],
            'chart' => $chart,
            'recentOrders' => $orders->take(5)->values(),
            'topProducts' => ProductResource::collection($topProducts),
        ]);
    }

    private function storeId(Request $request): int
    {
        return $request->user()->store->id;
    }
}
