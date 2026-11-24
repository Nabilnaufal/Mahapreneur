<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Http\Resources\PaymentResource;
use App\Http\Resources\ProductResource;
use App\Http\Resources\StoreResource;
use App\Http\Resources\UserResource;
use App\Models\Address;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Models\Wishlist;
use App\Services\DokuService;
use App\Services\PaymentService;
use App\Support\Activity;
use App\Support\Reference;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class CustomerController extends Controller
{
    public function __construct(private PaymentService $payments) {}

    public function getCart(Request $request)
    {
        return $this->ok($this->cartSummary($request->user()));
    }

    public function addToCart(Request $request)
    {
        $data = $request->validate([
            'product_id' => ['required', 'integer', Rule::exists('products', 'id')],
            'quantity' => ['required', 'integer', 'min:1', 'max:99'],
        ]);

        $product = Product::with('store')->find($data['product_id']);
        if (! $product || $product->status !== 'ACTIVE' || $product->store->status !== 'ACTIVE') {
            throw ApiException::notFound('Produk tidak tersedia');
        }

        $cart = Cart::firstOrCreate(['user_id' => $request->user()->id]);
        $existing = CartItem::where('cart_id', $cart->id)->where('product_id', $product->id)->first();
        $quantity = ($existing?->quantity ?? 0) + $data['quantity'];
        if ($quantity > $product->stock) {
            throw ApiException::badRequest("Stok {$product->name} tinggal {$product->stock}");
        }

        CartItem::updateOrCreate(
            ['cart_id' => $cart->id, 'product_id' => $product->id],
            ['quantity' => $quantity],
        );

        return $this->ok($this->cartSummary($request->user()), 'Produk ditambahkan ke keranjang');
    }

    public function updateCartItem(Request $request, int $itemId)
    {
        $data = $request->validate(['quantity' => ['required', 'integer', 'min:1', 'max:99']]);
        $item = CartItem::with('product')->whereHas('cart', fn ($query) => $query->where('user_id', $request->user()->id))->find($itemId);
        if (! $item) {
            throw ApiException::notFound('Item keranjang tidak ditemukan');
        }
        if ($item->product->stock < $data['quantity']) {
            throw ApiException::badRequest("Stok {$item->product->name} tinggal {$item->product->stock}");
        }

        $item->update(['quantity' => $data['quantity']]);

        return $this->ok($this->cartSummary($request->user()), 'Jumlah diperbarui');
    }

    public function removeCartItem(Request $request, int $itemId)
    {
        $item = CartItem::whereHas('cart', fn ($query) => $query->where('user_id', $request->user()->id))->find($itemId);
        if (! $item) {
            throw ApiException::notFound('Item keranjang tidak ditemukan');
        }
        $item->delete();

        return $this->ok($this->cartSummary($request->user()), 'Produk dihapus dari keranjang');
    }

    public function getWishlist(Request $request)
    {
        $items = Wishlist::with(['product' => fn ($query) => $query->withCard()])
            ->where('user_id', $request->user()->id)
            ->latest('created_at')
            ->get()
            ->map(fn (Wishlist $wishlist) => [
                'id' => $wishlist->id,
                'product' => ProductResource::make($wishlist->product),
            ]);

        return $this->ok(['items' => $items]);
    }

    public function addWishlist(Request $request)
    {
        $data = $request->validate(['product_id' => ['required', 'integer', Rule::exists('products', 'id')]]);
        Wishlist::firstOrCreate(['user_id' => $request->user()->id, 'product_id' => $data['product_id']]);

        return $this->ok(['productId' => $data['product_id']], 'Ditambahkan ke wishlist');
    }

    public function removeWishlist(Request $request, int $productId)
    {
        Wishlist::where('user_id', $request->user()->id)->where('product_id', $productId)->delete();

        return $this->ok(['productId' => $productId], 'Dihapus dari wishlist');
    }

    public function listAddresses(Request $request)
    {
        $addresses = Address::where('user_id', $request->user()->id)
            ->orderByDesc('is_default')
            ->latest('created_at')
            ->get();

        return $this->ok(['addresses' => $addresses]);
    }

    public function createAddress(Request $request)
    {
        $data = $request->validate($this->addressRules());
        $userId = $request->user()->id;

        if ($data['is_default'] ?? false) {
            Address::where('user_id', $userId)->update(['is_default' => false]);
        }

        $address = Address::create($data + ['user_id' => $userId]);

        return $this->ok(['address' => $address], 'Alamat ditambahkan');
    }

    public function updateAddress(Request $request, int $id)
    {
        $address = Address::where('user_id', $request->user()->id)->find($id);
        if (! $address) {
            throw ApiException::notFound('Alamat tidak ditemukan');
        }

        $data = $request->validate($this->addressRules(partial: true));
        if ($data['is_default'] ?? false) {
            Address::where('user_id', $request->user()->id)->update(['is_default' => false]);
        }

        $address->update($data);

        return $this->ok(['address' => $address], 'Alamat diperbarui');
    }

    public function deleteAddress(Request $request, int $id)
    {
        $address = Address::where('user_id', $request->user()->id)->find($id);
        if (! $address) {
            throw ApiException::notFound('Alamat tidak ditemukan');
        }
        $address->delete();

        return $this->ok(null, 'Alamat dihapus');
    }

    /**
     * Satu checkout dapat menghasilkan beberapa pesanan (dipisah per seller).
     * Harga, stok, dan total dihitung ulang di backend di dalam satu transaksi.
     */
    public function checkout(Request $request)
    {
        $data = $request->validate([
            'cart_item_ids' => ['required', 'array', 'min:1'],
            'cart_item_ids.*' => ['integer'],
            'recipient_name' => ['required', 'string', 'min:3', 'max:80'],
            'recipient_phone' => ['required', 'string', 'min:8', 'max:20'],
            'address' => ['required', 'string', 'min:10'],
            'campus' => ['nullable', 'string', 'max:120'],
            'room' => ['nullable', 'string', 'max:60'],
            'note' => ['nullable', 'string', 'max:500'],
            'payment_method' => ['required', Rule::in(['QRIS', 'VIRTUAL_ACCOUNT', 'EWALLET', 'COD'])],
            'save_address' => ['boolean'],
        ]);

        $user = $request->user();
        $cartItems = CartItem::whereIn('id', $data['cart_item_ids'])
            ->whereHas('cart', fn ($query) => $query->where('user_id', $user->id))
            ->with('product.store')
            ->get();

        if ($cartItems->count() !== count(array_unique($data['cart_item_ids']))) {
            throw ApiException::badRequest('Sebagian item keranjang tidak valid');
        }
        foreach ($cartItems as $item) {
            if ($item->product->status !== 'ACTIVE' || $item->product->store->status !== 'ACTIVE') {
                throw ApiException::badRequest("{$item->product->name} sudah tidak dijual lagi");
            }
            if ($item->product->stock < $item->quantity) {
                throw ApiException::badRequest("Stok {$item->product->name} tinggal {$item->product->stock}");
            }
        }

        $isCod = $data['payment_method'] === 'COD';
        $created = DB::transaction(function () use ($cartItems, $data, $user, $isCod) {
            $orders = [];

            foreach ($cartItems->groupBy(fn (CartItem $item) => $item->product->store_id) as $storeId => $items) {
                $subtotal = $items->sum(fn (CartItem $item) => $item->product->price * $item->quantity);
                $order = Order::create([
                    'order_number' => Reference::orderNumber(),
                    'customer_id' => $user->id,
                    'store_id' => $storeId,
                    'status' => $isCod ? 'CONFIRMED' : 'WAITING_PAYMENT',
                    'payment_status' => $isCod ? 'UNPAID_COD' : 'PENDING',
                    'payment_method' => $data['payment_method'],
                    'subtotal' => $subtotal,
                    'total' => $subtotal,
                    'recipient_name' => $data['recipient_name'],
                    'recipient_phone' => $data['recipient_phone'],
                    'address' => $data['address'],
                    'campus' => $data['campus'] ?? null,
                    'room' => $data['room'] ?? null,
                    'note' => $data['note'] ?? null,
                    'confirmed_at' => $isCod ? now() : null,
                ]);

                // Kunci baris produk agar stok tidak bisa terjual melebihi jumlahnya.
                foreach ($items as $item) {
                    $product = Product::with('store')->whereKey($item->product_id)->lockForUpdate()->first();
                    if (! $product || $product->status !== 'ACTIVE' || $product->store->status !== 'ACTIVE' || $product->stock < $item->quantity) {
                        throw ApiException::conflict("Stok {$item->product->name} sudah habis. Silakan perbarui keranjang.");
                    }

                    $product->decrement('stock', $item->quantity);
                    $product->increment('sold', $item->quantity);
                    $order->items()->create([
                        'product_id' => $product->id,
                        'product_name' => $product->name,
                        'product_price' => $product->price,
                        'quantity' => $item->quantity,
                        'subtotal' => $product->price * $item->quantity,
                    ]);
                }

                $order->histories()->create([
                    'status' => $order->status,
                    'note' => $isCod ? 'Pesanan COD dibuat, menunggu diproses seller' : 'Menunggu pembayaran online',
                    'created_by' => $user->id,
                ]);
                CartItem::whereKey($items->pluck('id'))->delete();
                $orders[] = $order;
            }

            if ($data['save_address'] ?? false) {
                Address::create([
                    'user_id' => $user->id,
                    'label' => filled($data['campus'] ?? null) ? "Kampus {$data['campus']}" : 'Alamat utama',
                    'recipient_name' => $data['recipient_name'],
                    'phone' => $data['recipient_phone'],
                    'address' => $data['address'],
                    'campus' => $data['campus'] ?? null,
                    'room' => $data['room'] ?? null,
                ]);
            }

            return $orders;
        });

        foreach ($created as $order) {
            $order->load('store');
            if ($isCod) {
                $order->payment()->create(['method' => 'COD', 'status' => 'UNPAID_COD', 'amount' => $order->total]);
                Activity::notify(
                    $order->store->user_id,
                    'Pesanan COD baru',
                    "Pesanan {$order->order_number} masuk dengan metode COD. Segera proses dan antarkan sendiri ke penerima.",
                    'info',
                    "/seller/orders/{$order->id}",
                );
            } else {
                $this->payments->prepareOnlinePayment($order->id);
            }
        }

        $orders = Order::with(['store', 'items.product.images', 'payment'])
            ->whereIn('id', collect($created)->pluck('id'))
            ->get();

        Activity::log(
            $user->id,
            'CHECKOUT',
            'Order',
            collect($created)->pluck('id')->implode(','),
            'Checkout '.count($created)." pesanan dengan metode {$data['payment_method']}",
            ['orders' => collect($created)->pluck('order_number')],
        );

        return $this->ok([
            'orders' => OrderResource::collection($orders),
            'ordersCount' => $orders->count(),
        ], 'Pesanan berhasil dibuat');
    }

    public function listOrders(Request $request)
    {
        $orders = Order::with(['store', 'items.product.images', 'payment'])
            ->where('customer_id', $request->user()->id)
            ->when($request->query('status'), fn ($query, string $status) => $query->where('status', $status))
            ->latest('created_at')
            ->paginate($this->perPage($request));

        return $this->ok([
            'orders' => OrderResource::collection($orders),
            'meta' => $this->meta($orders),
        ]);
    }

    public function getOrder(Request $request, int $id)
    {
        $order = Order::with(['store', 'items.product.images', 'payment', 'histories'])
            ->where('customer_id', $request->user()->id)
            ->find($id);
        if (! $order) {
            throw ApiException::notFound('Pesanan tidak ditemukan');
        }

        return $this->ok([
            'order' => OrderResource::make($order),
            'store' => StoreResource::make($order->store),
        ]);
    }

    public function cancelOrder(Request $request, int $id)
    {
        $data = $request->validate(['reason' => ['nullable', 'string', 'max:300']]);
        $order = Order::with(['items', 'store', 'payment'])
            ->where('customer_id', $request->user()->id)
            ->find($id);
        if (! $order) {
            throw ApiException::notFound('Pesanan tidak ditemukan');
        }
        if (! in_array($order->status, ['WAITING_PAYMENT', 'CONFIRMED'], true)) {
            throw ApiException::badRequest('Pesanan yang sudah diproses tidak dapat dibatalkan. Hubungi seller.');
        }
        if ($order->payment_status === 'PAID') {
            throw ApiException::badRequest('Pesanan sudah dibayar. Ajukan pembatalan ke seller untuk proses refund.');
        }

        DB::transaction(function () use ($order, $data, $request) {
            $order->update([
                'status' => 'CANCELLED',
                'cancelled_at' => now(),
                'payment_status' => $order->payment_status === 'PENDING' ? 'FAILED' : $order->payment_status,
            ]);
            foreach ($order->items as $item) {
                if ($item->product_id) {
                    Product::whereKey($item->product_id)->increment('stock', $item->quantity);
                    Product::whereKey($item->product_id)->decrement('sold', $item->quantity);
                }
            }
            $order->histories()->create([
                'status' => 'CANCELLED',
                'note' => $data['reason'] ?? 'Dibatalkan oleh customer',
                'created_by' => $request->user()->id,
            ]);
            if ($order->payment?->status === 'PENDING') {
                $order->payment->update(['status' => 'FAILED']);
            }
        });

        Activity::notify(
            $order->store->user_id,
            'Pesanan dibatalkan customer',
            "Pesanan {$order->order_number} dibatalkan oleh customer.".($data['reason'] ?? null ? " Alasan: {$data['reason']}" : ''),
            'warning',
            "/seller/orders/{$order->id}",
        );
        Activity::log($request->user()->id, 'ORDER_CANCELLED', 'Order', $order->id, "Customer membatalkan pesanan {$order->order_number}");

        return $this->ok(['orderId' => $order->id], 'Pesanan dibatalkan');
    }

    public function getPayment(Request $request, int $id)
    {
        $order = Order::with('payment')->where('customer_id', $request->user()->id)->find($id);
        if (! $order) {
            throw ApiException::notFound('Pesanan tidak ditemukan');
        }
        if ($order->payment_method === 'COD') {
            throw ApiException::badRequest('Pesanan ini memakai metode COD');
        }

        if (! $order->payment) {
            $this->payments->prepareOnlinePayment($order->id);
            $order->load('payment');
        }
        if ($order->payment?->status === 'PENDING' && $order->payment->expires_at?->isPast()) {
            $order->payment->update(['status' => 'EXPIRED']);
            $order->update(['payment_status' => 'EXPIRED']);
            $order->payment->status = 'EXPIRED';
        }

        return $this->ok([
            'order' => [
                'id' => $order->id,
                'orderNumber' => $order->order_number,
                'total' => $order->total,
                'status' => $order->status,
                'paymentStatus' => $order->payment_status,
                'paymentMethod' => $order->payment_method,
            ],
            'payment' => $order->payment ? PaymentResource::make($order->payment) : null,
            'channels' => $this->payments->availableChannels($order->payment_method),
            'simulated' => ! DokuService::configured(),
        ]);
    }

    public function choosePaymentChannel(Request $request, int $id)
    {
        $data = $request->validate(['channel_code' => ['required', 'string', 'min:2']]);
        $order = Order::with('payment')->where('customer_id', $request->user()->id)->find($id);
        if (! $order?->payment) {
            throw ApiException::notFound('Data pembayaran belum tersedia');
        }
        if ($order->payment->status === 'PAID') {
            throw ApiException::badRequest('Pesanan ini sudah dibayar');
        }
        if ($order->payment->status === 'EXPIRED') {
            throw ApiException::badRequest('Pembayaran sudah kedaluwarsa. Buat pembayaran baru.');
        }

        if (! DokuService::configured() || ! $order->payment->doku_token_id) {
            $order->payment->update([
                'doku_channel' => $data['channel_code'],
                'doku_qr_string' => $order->payment_method === 'QRIS' ? "SIMULASI-QRIS-{$order->order_number}" : null,
                'doku_va_number' => $order->payment_method === 'VIRTUAL_ACCOUNT' ? '88'.str_pad((string) $order->id, 10, '0', STR_PAD_LEFT) : null,
            ]);

            return $this->ok(['payment' => PaymentResource::make($order->payment->fresh()), 'simulated' => true], 'Mode simulasi pembayaran aktif (kredensial DOKU belum diisi)');
        }

        $channel = app(DokuService::class)->requestChannel($order->payment->doku_token_id, $data['channel_code']);
        $order->payment->update([
            'doku_channel' => $data['channel_code'],
            'doku_qr_string' => $channel['qrString'],
            'doku_va_number' => $channel['vaNumber'],
            'doku_payment_url' => $channel['paymentUrl'] ?? $order->payment->doku_payment_url,
        ]);

        return $this->ok(['payment' => PaymentResource::make($order->payment->fresh()), 'simulated' => false], 'Instruksi pembayaran siap');
    }

    public function retryPayment(Request $request, int $id)
    {
        $order = Order::with('payment')->where('customer_id', $request->user()->id)->find($id);
        if (! $order) {
            throw ApiException::notFound('Pesanan tidak ditemukan');
        }
        if ($order->payment_status === 'PAID') {
            throw ApiException::badRequest('Pesanan ini sudah dibayar');
        }
        if ($order->payment_method === 'COD') {
            throw ApiException::badRequest('Pesanan COD tidak memerlukan pembayaran online');
        }

        $order->payment?->update(['status' => 'PENDING']);
        $prepared = $this->payments->prepareOnlinePayment($order->id);

        return $this->ok(['payment' => $prepared ? PaymentResource::make($prepared['payment']) : null], 'Instruksi pembayaran baru dibuat');
    }

    /** Simulasi pembayaran hanya aktif bila kredensial DOKU belum dikonfigurasi. */
    public function simulatePayment(Request $request, int $id)
    {
        if (DokuService::configured()) {
            throw ApiException::forbidden('Simulasi pembayaran tidak tersedia saat DOKU aktif');
        }

        $order = Order::with('payment')->where('customer_id', $request->user()->id)->find($id);
        if (! $order?->payment?->invoice_number) {
            throw ApiException::notFound('Data pembayaran tidak ditemukan');
        }

        $result = $this->payments->markPaid(
            $order->payment->invoice_number,
            $order->payment->amount,
            $order->payment->doku_channel,
            'SIMULATION',
        );

        return $this->ok($result, 'Pembayaran simulasi diproses');
    }

    public function getProfile(Request $request)
    {
        return $this->ok(['user' => UserResource::make($request->user())]);
    }

    public function updateProfile(Request $request)
    {
        $data = $request->validate([
            'name' => ['sometimes', 'string', 'min:3', 'max:80'],
            'phone' => ['sometimes', 'nullable', 'string', 'max:20'],
            'avatar' => ['sometimes', 'nullable', 'string', 'max:300'],
        ]);
        if (array_key_exists('phone', $data)) {
            $data['phone'] = $data['phone'] ?: null;
        }
        if (array_key_exists('avatar', $data)) {
            $data['avatar'] = $data['avatar'] ?: null;
        }

        $request->user()->update($data);

        return $this->ok(['user' => UserResource::make($request->user())], 'Profil diperbarui');
    }

    public function changePassword(Request $request)
    {
        $data = $request->validate([
            'current_password' => ['required', 'string'],
            'new_password' => ['required', 'string', Password::min(8)],
            'confirm_password' => ['required', 'same:new_password'],
        ]);

        $user = $request->user();
        if (! Hash::check($data['current_password'], $user->password)) {
            throw ApiException::badRequest('Password saat ini salah');
        }

        $user->update(['password' => $data['new_password']]);
        Activity::log($user->id, 'CHANGE_PASSWORD', 'User', $user->id, 'Password diubah dari halaman profil');

        return $this->ok(null, 'Password berhasil diubah');
    }

    private function cartSummary(User $user): array
    {
        $records = CartItem::whereHas('cart', fn ($query) => $query->where('user_id', $user->id))
            ->with(['product' => fn ($query) => $query->withCard()])
            ->orderBy('created_at')
            ->get();
        $items = $records->map(fn (CartItem $item) => [
            'id' => $item->id,
            'quantity' => $item->quantity,
            'subtotal' => $item->product->price * $item->quantity,
            'product' => ProductResource::make($item->product),
        ]);

        return [
            'items' => $items,
            'summary' => [
                'itemCount' => $records->sum('quantity'),
                'storeCount' => $records->pluck('product.store_id')->unique()->count(),
                'subtotal' => $items->sum('subtotal'),
            ],
        ];
    }

    private function addressRules(bool $partial = false): array
    {
        $presence = $partial ? 'sometimes' : 'required';

        return [
            'label' => [$presence, 'string', 'min:2', 'max:40'],
            'recipient_name' => [$presence, 'string', 'min:3', 'max:80'],
            'phone' => [$presence, 'string', 'min:8', 'max:20'],
            'address' => [$presence, 'string', 'min:10'],
            'campus' => ['nullable', 'string', 'max:120'],
            'room' => ['nullable', 'string', 'max:60'],
            'is_default' => ['sometimes', 'boolean'],
        ];
    }
}
