<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Http\Resources\ProductResource;
use App\Http\Resources\StoreResource;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\Store;
use App\Support\Activity;
use App\Support\Reference;
use App\Support\Uploads;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class SellerController extends Controller
{
    /** Urutan status yang boleh ditempuh seller pada satu langkah. */
    private const NEXT_STATUS = [
        'CONFIRMED' => ['PROCESSING', 'CANCELLED'],
        'PROCESSING' => ['READY', 'CANCELLED'],
        'READY' => ['ON_DELIVERY'],
        'ON_DELIVERY' => ['COMPLETED'],
    ];

    private const STATUS_LABEL = [
        'PROCESSING' => 'sedang diproses',
        'READY' => 'siap diserahkan',
        'ON_DELIVERY' => 'sedang diantarkan',
        'COMPLETED' => 'selesai',
        'CANCELLED' => 'dibatalkan',
    ];

    public function dashboard(Request $request)
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

    public function getStore(Request $request)
    {
        $store = Store::withCount('products')->find($this->storeId($request));

        return $this->ok(['store' => $store ? StoreResource::make($store) : null]);
    }

    public function createStore(Request $request)
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

        $store = Store::create(['user_id' => $user->id, ...$data, 'slug' => $slug]);
        Activity::log($user->id, 'STORE_CREATED', 'Store', $store->id, "Toko {$store->name} dibuat");

        return $this->ok(['store' => StoreResource::make($store)], 'Toko berhasil dibuat');
    }

    public function updateStore(Request $request)
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

    public function uploadStoreImage(Request $request, string $kind)
    {
        $request->validate(['image' => ['required', 'image', 'mimes:jpeg,png,webp,gif', 'max:'.config('mahapreneur.max_upload_kb')]]);
        $store = $request->user()->store;
        $store->update([$kind => Uploads::image($request->file('image'), 'stores')]);

        return $this->ok(['store' => StoreResource::make($store)], ($kind === 'logo' ? 'Logo' : 'Banner').' toko diperbarui');
    }

    public function listProducts(Request $request)
    {
        $products = Product::where('store_id', $this->storeId($request))
            ->withCard()
            ->when($request->query('q'), fn ($query, string $keyword) => $query->where('name', 'like', "%{$keyword}%"))
            ->when($request->query('status'), fn ($query, string $status) => $query->where('status', $status))
            ->when($request->query('type'), fn ($query, string $type) => $query->where('type', $type))
            ->latest('created_at')
            ->paginate($this->perPage($request));

        return $this->ok([
            'products' => ProductResource::collection($products),
            'meta' => $this->meta($products),
        ]);
    }

    public function getProduct(Request $request, int $id)
    {
        $product = $this->findProduct($request, $id);

        return $this->ok(['product' => ProductResource::make($product)]);
    }

    public function createProduct(Request $request)
    {
        $data = $request->validate($this->productRules());
        $category = Category::whereKey($data['category_id'])->where('is_active', true)->first();
        if (! $category) {
            throw ApiException::badRequest('Kategori tidak valid');
        }

        $slug = $this->uniqueProductSlug($data['name']);
        $product = Product::create([
            ...$data,
            'store_id' => $this->storeId($request),
            'slug' => $slug,
            'service_terms' => $data['service_terms'] ?? null,
            'service_duration' => $data['service_duration'] ?? null,
        ])->load(['images', 'category', 'store']);

        Activity::log($request->user()->id, 'PRODUCT_CREATED', 'Product', $product->id, "Produk {$product->name} ditambahkan");

        return $this->ok(['product' => ProductResource::make($product)], 'Produk berhasil ditambahkan');
    }

    public function updateProduct(Request $request, int $id)
    {
        $product = $this->findProduct($request, $id);
        $data = $request->validate($this->productRules(partial: true));

        if (isset($data['category_id'])) {
            $category = Category::whereKey($data['category_id'])->where('is_active', true)->first();
            if (! $category) {
                throw ApiException::badRequest('Kategori tidak valid');
            }
        }

        $product->update($data);
        $product->load(['images', 'category', 'store']);

        return $this->ok(['product' => ProductResource::make($product)], 'Produk diperbarui');
    }

    public function deleteProduct(Request $request, int $id)
    {
        $product = $this->findProduct($request, $id);
        $product->delete();
        Activity::log($request->user()->id, 'PRODUCT_DELETED', 'Product', $product->id, "Produk {$product->name} dihapus");

        return $this->ok(null, 'Produk dihapus');
    }

    public function addProductImages(Request $request, int $id)
    {
        $product = $this->findProduct($request, $id);
        $request->validate([
            'images' => ['required', 'array', 'min:1'],
            'images.*' => ['image', 'mimes:jpeg,png,webp,gif', 'max:'.config('mahapreneur.max_upload_kb')],
        ]);

        $files = $request->file('images');
        if ($product->images()->count() + count($files) > 5) {
            throw ApiException::badRequest('Maksimal 5 gambar per produk');
        }

        $start = $product->images()->count();
        foreach ($files as $index => $file) {
            $product->images()->create([
                'url' => Uploads::image($file, 'products'),
                'sort_order' => $start + $index,
            ]);
        }

        $product->load(['images', 'category', 'store']);

        return $this->ok(['product' => ProductResource::make($product)], 'Gambar produk diunggah');
    }

    public function deleteProductImage(Request $request, int $id, int $imageId)
    {
        $product = $this->findProduct($request, $id);
        $product->images()->whereKey($imageId)->delete();
        $product->load(['images', 'category', 'store']);

        return $this->ok(['product' => ProductResource::make($product)], 'Gambar dihapus');
    }

    public function listOrders(Request $request)
    {
        $orders = Order::with(['customer', 'items.product.images', 'payment'])
            ->where('store_id', $this->storeId($request))
            ->when($request->query('status'), fn ($query, string $status) => $query->where('status', $status))
            ->when($request->query('q'), fn ($query, string $keyword) => $query->where(
                fn ($search) => $search->where('order_number', 'like', "%{$keyword}%")
                    ->orWhere('recipient_name', 'like', "%{$keyword}%"),
            ))
            ->latest('created_at')
            ->paginate($this->perPage($request));

        return $this->ok([
            'orders' => OrderResource::collection($orders),
            'meta' => $this->meta($orders),
        ]);
    }

    public function getOrder(Request $request, int $id)
    {
        $order = Order::with(['customer', 'items.product.images', 'payment', 'histories'])
            ->where('store_id', $this->storeId($request))
            ->find($id);
        if (! $order) {
            throw ApiException::notFound('Pesanan tidak ditemukan');
        }

        return $this->ok(['order' => OrderResource::make($order)]);
    }

    /** Seller memperbarui status pesanan sesuai alur pengantaran mandiri. */
    public function updateOrderStatus(Request $request, int $id)
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(['PROCESSING', 'READY', 'ON_DELIVERY', 'COMPLETED', 'CANCELLED'])],
            'note' => ['nullable', 'string', 'max:300'],
        ]);
        $order = Order::with('items')->where('store_id', $this->storeId($request))->find($id);
        if (! $order) {
            throw ApiException::notFound('Pesanan tidak ditemukan');
        }
        if (! in_array($data['status'], self::NEXT_STATUS[$order->status] ?? [], true)) {
            throw ApiException::badRequest("Status tidak bisa diubah dari {$order->status} ke {$data['status']}");
        }
        if (! in_array($order->payment_status, ['PAID', 'UNPAID_COD'], true)) {
            throw ApiException::badRequest('Pesanan belum dibayar');
        }

        DB::transaction(function () use ($order, $data, $request) {
            $order->update([
                'status' => $data['status'],
                'completed_at' => $data['status'] === 'COMPLETED' ? now() : $order->completed_at,
                'cancelled_at' => $data['status'] === 'CANCELLED' ? now() : $order->cancelled_at,
            ]);
            $order->histories()->create([
                'status' => $data['status'],
                'note' => $data['note'] ?? null,
                'created_by' => $request->user()->id,
            ]);
            if ($data['status'] === 'CANCELLED') {
                foreach ($order->items->whereNotNull('product_id') as $item) {
                    Product::whereKey($item->product_id)->increment('stock', $item->quantity);
                    Product::whereKey($item->product_id)->decrement('sold', $item->quantity);
                }
            }
        });

        Activity::notify(
            $order->customer_id,
            'Pesanan '.(self::STATUS_LABEL[$data['status']] ?? $data['status']),
            "Pesanan {$order->order_number} ".(self::STATUS_LABEL[$data['status']] ?? 'diperbarui').'.'.(! empty($data['note']) ? " Catatan seller: {$data['note']}" : ''),
            $data['status'] === 'CANCELLED' ? 'warning' : 'info',
            "/customer/orders/{$order->id}",
        );
        Activity::log(
            $request->user()->id,
            'ORDER_STATUS_UPDATED',
            'Order',
            $order->id,
            "Status {$order->order_number} diubah ke {$data['status']}",
        );

        return $this->ok(['orderId' => $order->id, 'status' => $data['status']], "Status pesanan diperbarui menjadi {$data['status']}");
    }

    /** Seller menandai pembayaran COD sudah diterima langsung dari customer. */
    public function markCodPaid(Request $request, int $id)
    {
        $order = Order::with('payment')->where('store_id', $this->storeId($request))->find($id);
        if (! $order) {
            throw ApiException::notFound('Pesanan tidak ditemukan');
        }
        if ($order->payment_method !== 'COD') {
            throw ApiException::badRequest('Pesanan ini bukan pesanan COD');
        }
        if ($order->payment_status === 'PAID') {
            throw ApiException::badRequest('Pembayaran COD sudah ditandai lunas');
        }

        DB::transaction(function () use ($order) {
            $order->update(['payment_status' => 'PAID']);
            $order->payment?->update(['status' => 'PAID', 'paid_at' => now()]);
        });

        Activity::notify(
            $order->customer_id,
            'Pembayaran COD diterima',
            "Seller sudah menerima pembayaran untuk pesanan {$order->order_number}. Terima kasih!",
            'success',
            "/customer/orders/{$order->id}",
        );
        Activity::log($request->user()->id, 'COD_PAID', 'Order', $order->id, "Pembayaran COD {$order->order_number} ditandai lunas");

        return $this->ok(['orderId' => $order->id], 'Pembayaran COD ditandai lunas');
    }

    private function storeId(Request $request): int
    {
        return $request->user()->store->id;
    }

    private function findProduct(Request $request, int $id): Product
    {
        $product = Product::where('store_id', $this->storeId($request))->withCard()->find($id);
        if (! $product) {
            throw ApiException::notFound('Produk tidak ditemukan');
        }

        return $product;
    }

    private function uniqueProductSlug(string $name): string
    {
        $slug = Reference::slug($name, 'produk');
        while (Product::where('slug', $slug)->exists()) {
            $slug = Reference::slug($name.'-'.Str::lower(Str::random(4)), 'produk');
        }

        return $slug;
    }

    private function storeRules(bool $partial = false, ?int $ignoreId = null): array
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

    private function productRules(bool $partial = false): array
    {
        $presence = $partial ? 'sometimes' : 'required';

        return [
            'name' => [$presence, 'string', 'min:3', 'max:120'],
            'description' => [$presence, 'string', 'min:10'],
            'price' => [$presence, 'integer', 'min:0', 'max:2000000000'],
            'stock' => [$presence, 'integer', 'min:0', 'max:1000000'],
            'category_id' => [$presence, 'integer', Rule::exists('categories', 'id')],
            'type' => ['sometimes', Rule::in(['GOODS', 'SERVICE'])],
            'status' => ['sometimes', Rule::in(['ACTIVE', 'INACTIVE'])],
            'service_terms' => ['nullable', 'string', 'max:1000'],
            'service_duration' => ['nullable', 'string', 'max:80'],
        ];
    }
}
