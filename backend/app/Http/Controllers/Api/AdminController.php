<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Resources\BannerResource;
use App\Http\Resources\CategoryResource;
use App\Http\Resources\OrderResource;
use App\Http\Resources\PaymentResource;
use App\Http\Resources\ProductResource;
use App\Http\Resources\StoreResource;
use App\Http\Resources\UserResource;
use App\Models\ActivityLog;
use App\Models\Banner;
use App\Models\Category;
use App\Models\Notification;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use App\Support\Activity;
use App\Support\Reference;
use App\Support\Uploads;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdminController extends Controller
{
    public function dashboard()
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

    public function listUsers(Request $request)
    {
        $users = User::with('store')->withCount('orders')
            ->when($request->query('q'), fn ($query, string $keyword) => $query->where(
                fn ($search) => $search->where('name', 'like', "%{$keyword}%")
                    ->orWhere('email', 'like', "%{$keyword}%"),
            ))
            ->when($request->query('role'), fn ($query, string $role) => $query->where('role', $role))
            ->when($request->query('status') === 'active', fn ($query) => $query->where('is_active', true))
            ->when($request->query('status') === 'inactive', fn ($query) => $query->where('is_active', false))
            ->when($request->query('verified') === 'true', fn ($query) => $query->whereNotNull('email_verified_at'))
            ->when($request->query('verified') === 'false', fn ($query) => $query->whereNull('email_verified_at'))
            ->latest('created_at')
            ->paginate($this->perPage($request));

        return $this->ok([
            'users' => UserResource::collection($users),
            'meta' => $this->meta($users),
        ]);
    }

    public function getUser(int $id)
    {
        $user = User::with(['store' => fn ($query) => $query->withCount(['products', 'orders'])])
            ->withCount(['orders', 'addresses', 'wishlists'])
            ->find($id);
        if (! $user) {
            throw ApiException::notFound('Pengguna tidak ditemukan');
        }

        return $this->ok(['user' => UserResource::make($user)]);
    }

    /** Aktif/nonaktifkan akun. Superadmin tidak boleh menonaktifkan dirinya sendiri. */
    public function setUserStatus(Request $request, int $id)
    {
        $data = $request->validate([
            'is_active' => ['required', 'boolean'],
            'reason' => ['nullable', 'string', 'max:300'],
        ]);
        $target = User::find($id);
        if (! $target) {
            throw ApiException::notFound('Pengguna tidak ditemukan');
        }
        if ($target->id === $request->user()->id) {
            throw ApiException::badRequest('Kamu tidak dapat menonaktifkan akunmu sendiri');
        }
        if (! $data['is_active'] && $target->role === 'SUPERADMIN') {
            throw ApiException::forbidden('Akun superadmin lain tidak dapat dinonaktifkan');
        }

        $target->update(['is_active' => $data['is_active']]);
        Activity::log(
            $request->user()->id,
            $data['is_active'] ? 'USER_ACTIVATED' : 'USER_DEACTIVATED',
            'User',
            $target->id,
            "Akun {$target->name} ({$target->email}) ".($data['is_active'] ? 'diaktifkan' : 'dinonaktifkan'),
            isset($data['reason']) ? ['reason' => $data['reason']] : null,
        );

        return $this->ok(['user' => UserResource::make($target)], 'Akun berhasil '.($data['is_active'] ? 'diaktifkan' : 'dinonaktifkan'));
    }

    public function listCategories()
    {
        $categories = Category::withCount('products')->orderBy('sort_order')->get();

        return $this->ok(['categories' => CategoryResource::collection($categories)]);
    }

    public function createCategory(Request $request)
    {
        $data = $request->validate($this->categoryRules());
        $category = Category::create([
            ...$data,
            'slug' => Reference::slug($data['slug'] ?? $data['name'], 'kategori'),
        ]);
        Activity::log($request->user()->id, 'CATEGORY_CREATED', 'Category', $category->id, "Kategori {$category->name} dibuat");

        return $this->ok(['category' => CategoryResource::make($category)], 'Kategori ditambahkan');
    }

    public function updateCategory(Request $request, int $id)
    {
        $category = Category::find($id);
        if (! $category) {
            throw ApiException::notFound('Kategori tidak ditemukan');
        }

        $data = $request->validate($this->categoryRules(partial: true, ignoreId: $category->id));
        if (array_key_exists('slug', $data)) {
            $data['slug'] = blank($data['slug']) ? $category->slug : Reference::slug($data['slug'], 'kategori');
        }
        $category->update($data);

        return $this->ok(['category' => CategoryResource::make($category)], 'Kategori diperbarui');
    }

    public function deleteCategory(Request $request, int $id)
    {
        $category = Category::find($id);
        if (! $category) {
            throw ApiException::notFound('Kategori tidak ditemukan');
        }
        $count = $category->products()->count();
        if ($count > 0) {
            throw ApiException::badRequest("Kategori masih dipakai {$count} produk. Pindahkan produk terlebih dahulu.");
        }
        $category->delete();
        Activity::log($request->user()->id, 'CATEGORY_DELETED', 'Category', $id, 'Kategori dihapus');

        return $this->ok(null, 'Kategori dihapus');
    }

    public function listStores(Request $request)
    {
        $stores = Store::with('user')
            ->withCount(['products', 'orders'])
            ->when($request->query('q'), fn ($query, string $keyword) => $query->where(
                fn ($search) => $search->where('name', 'like', "%{$keyword}%")
                    ->orWhere('slug', 'like', "%{$keyword}%"),
            ))
            ->when($request->query('status'), fn ($query, string $status) => $query->where('status', $status))
            ->latest('created_at')
            ->paginate($this->perPage($request));

        return $this->ok([
            'stores' => StoreResource::collection($stores),
            'meta' => $this->meta($stores),
        ]);
    }

    public function setStoreStatus(Request $request, int $id)
    {
        $data = $request->validate(['status' => ['required', Rule::in(['ACTIVE', 'INACTIVE', 'SUSPENDED'])]]);
        $store = Store::find($id);
        if (! $store) {
            throw ApiException::notFound('Toko tidak ditemukan');
        }

        $store->update(['status' => $data['status']]);
        Activity::log($request->user()->id, 'STORE_STATUS_UPDATED', 'Store', $store->id, "Status toko {$store->name} menjadi {$data['status']}");

        return $this->ok(['store' => StoreResource::make($store)], 'Status toko diperbarui');
    }

    public function listProducts(Request $request)
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

    /** Hapus produk yang melanggar ketentuan. */
    public function deleteProduct(Request $request, int $id)
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

    public function listOrders(Request $request)
    {
        $orders = Order::with(['store', 'customer', 'items.product.images', 'payment'])
            ->when($request->query('status'), fn ($query, string $status) => $query->where('status', $status))
            ->when($request->query('payment_status'), fn ($query, string $status) => $query->where('payment_status', $status))
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

    public function getOrder(int $id)
    {
        $order = Order::with(['store', 'customer', 'items.product.images', 'payment', 'histories'])->find($id);
        if (! $order) {
            throw ApiException::notFound('Pesanan tidak ditemukan');
        }

        return $this->ok(['order' => OrderResource::make($order)]);
    }

    public function listPayments(Request $request)
    {
        $payments = Payment::with(['order.store', 'order.customer'])
            ->when($request->query('status'), fn ($query, string $status) => $query->where('status', $status))
            ->when($request->query('method'), fn ($query, string $method) => $query->where('method', $method))
            ->latest('created_at')
            ->paginate($this->perPage($request));

        return $this->ok([
            'payments' => PaymentResource::collection($payments),
            'meta' => $this->meta($payments),
        ]);
    }

    public function listBanners()
    {
        return $this->ok([
            'banners' => BannerResource::collection(Banner::orderBy('sort_order')->get()),
        ]);
    }

    public function createBanner(Request $request)
    {
        $data = $request->validate($this->bannerRules());
        $banner = Banner::create($data);
        Activity::log($request->user()->id, 'BANNER_CREATED', 'Banner', $banner->id, "Banner {$banner->title} dibuat");

        return $this->ok(['banner' => BannerResource::make($banner)], 'Banner ditambahkan');
    }

    public function updateBanner(Request $request, int $id)
    {
        $banner = Banner::find($id);
        if (! $banner) {
            throw ApiException::notFound('Banner tidak ditemukan');
        }

        $banner->update($request->validate($this->bannerRules(partial: true)));

        return $this->ok(['banner' => BannerResource::make($banner)], 'Banner diperbarui');
    }

    public function deleteBanner(int $id)
    {
        Banner::whereKey($id)->delete();

        return $this->ok(null, 'Banner dihapus');
    }

    public function uploadBannerImage(Request $request)
    {
        $request->validate(['image' => ['required', 'image', 'mimes:jpeg,png,webp,gif', 'max:'.config('mahapreneur.max_upload_kb')]]);

        return $this->ok(['url' => Uploads::image($request->file('image'), 'banners')], 'Gambar diunggah');
    }

    public function activity(Request $request)
    {
        $logs = ActivityLog::with('user:id,name,email,role')
            ->when($request->query('entity'), fn ($query, string $entity) => $query->where('entity', $entity))
            ->when($request->query('q'), fn ($query, string $keyword) => $query->where('description', 'like', "%{$keyword}%"))
            ->latest('created_at')
            ->paginate($this->perPage($request, 20));

        return $this->ok([
            'logs' => $logs->items(),
            'meta' => $this->meta($logs),
        ]);
    }

    public function notifications(Request $request)
    {
        return $this->ok([
            'notifications' => Notification::where('user_id', $request->user()->id)
                ->latest('created_at')
                ->take(30)
                ->get(),
        ]);
    }

    private function categoryRules(bool $partial = false, ?int $ignoreId = null): array
    {
        $presence = $partial ? 'sometimes' : 'required';

        return [
            'name' => [$presence, 'string', 'min:2', 'max:40'],
            'slug' => ['sometimes', 'nullable', 'string', 'max:40', Rule::unique('categories', 'slug')->ignore($ignoreId)],
            'icon' => ['nullable', 'string', 'max:40'],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    private function bannerRules(bool $partial = false): array
    {
        $presence = $partial ? 'sometimes' : 'required';

        return [
            'title' => [$presence, 'string', 'min:3', 'max:80'],
            'description' => ['nullable', 'string', 'max:300'],
            'image' => [$presence, 'string', 'max:255'],
            'cta_label' => ['nullable', 'string', 'max:40'],
            'cta_url' => ['nullable', 'string', 'max:200'],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
            'status' => ['sometimes', Rule::in(['ACTIVE', 'INACTIVE'])],
        ];
    }
}
