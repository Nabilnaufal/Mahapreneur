<?php

namespace App\Http\Controllers\Customer;

use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\User;
use App\Services\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CartController extends Controller
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

    private function cartSummary(User $user)
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
}
