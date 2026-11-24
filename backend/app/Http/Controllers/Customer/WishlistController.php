<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Models\Wishlist;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class WishlistController extends Controller
{
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
}
