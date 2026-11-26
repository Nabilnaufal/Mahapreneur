<?php

namespace App\Http\Controllers\Customer;

use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Models\Address;
use App\Models\CartItem;
use App\Models\Order;
use App\Models\Product;
use App\Services\PaymentService;
use App\Services\DokuService;
use App\Support\Activity;
use App\Support\Reference;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class CheckoutController extends Controller
{
    public function __construct(private PaymentService $payments) {}

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
            'payment_method' => ['required', Rule::in(['OVO', 'DANA', 'SHOPEEPAY', 'COD'])],
            'save_address' => ['boolean'],
        ]);

        $user = $request->user();
        if ($data['payment_method'] !== 'COD' && ! DokuService::configured()) {
            throw ApiException::badRequest('Pembayaran online DOKU belum tersedia. Silakan pilih COD atau hubungi admin.');
        }

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
}
