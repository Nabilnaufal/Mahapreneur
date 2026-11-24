<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use App\Support\AuthToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WishlistFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_add_list_and_remove_wishlist_item(): void
    {
        $customer = User::create([
            'name' => 'Pembeli Wishlist',
            'email' => 'customer-wishlist@example.test',
            'password' => 'Rahasia123',
            'role' => 'CUSTOMER',
            'email_verified_at' => now(),
        ]);
        $seller = User::create([
            'name' => 'Penjual Wishlist',
            'email' => 'seller-wishlist@example.test',
            'password' => 'Rahasia123',
            'role' => 'SELLER',
            'email_verified_at' => now(),
        ]);
        $store = Store::create([
            'user_id' => $seller->id,
            'name' => 'Toko Wishlist',
            'slug' => 'toko-wishlist',
            'status' => 'ACTIVE',
        ]);
        $category = Category::create(['name' => 'Kategori Wishlist', 'slug' => 'kategori-wishlist']);
        $product = Product::create([
            'store_id' => $store->id,
            'category_id' => $category->id,
            'name' => 'Produk Wishlist',
            'slug' => 'produk-wishlist',
            'description' => 'Produk untuk pengujian wishlist.',
            'price' => 25000,
            'stock' => 5,
            'status' => 'ACTIVE',
        ]);
        $request = $this->withToken(AuthToken::issue($customer));

        $request->postJson('/api/customer/wishlist', ['productId' => $product->id])
            ->assertOk()
            ->assertJsonPath('data.productId', $product->id);

        $request->getJson('/api/customer/wishlist')
            ->assertOk()
            ->assertJsonPath('data.items.0.product.id', $product->id);

        $request->deleteJson("/api/customer/wishlist/{$product->id}")
            ->assertOk()
            ->assertJsonPath('data.productId', $product->id);

        $request->getJson('/api/customer/wishlist')
            ->assertOk()
            ->assertJsonCount(0, 'data.items');
    }
}
