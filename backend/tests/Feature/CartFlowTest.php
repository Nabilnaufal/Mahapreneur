<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use App\Support\AuthToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CartFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_add_product_to_cart(): void
    {
        $customer = User::create([
            'name' => 'Pembeli Test',
            'email' => 'customer-cart@example.test',
            'password' => 'Rahasia123',
            'role' => 'CUSTOMER',
            'email_verified_at' => now(),
        ]);
        $seller = User::create([
            'name' => 'Penjual Test',
            'email' => 'seller-cart@example.test',
            'password' => 'Rahasia123',
            'role' => 'SELLER',
            'email_verified_at' => now(),
        ]);
        $store = Store::create([
            'user_id' => $seller->id,
            'name' => 'Toko Test',
            'slug' => 'toko-test',
            'status' => 'ACTIVE',
        ]);
        $category = Category::create(['name' => 'Kategori Test', 'slug' => 'kategori-test']);
        $product = Product::create([
            'store_id' => $store->id,
            'category_id' => $category->id,
            'name' => 'Produk Test',
            'slug' => 'produk-test',
            'description' => 'Produk untuk pengujian keranjang.',
            'price' => 15000,
            'stock' => 10,
            'status' => 'ACTIVE',
        ]);

        $this->withToken(AuthToken::issue($customer))->postJson('/api/customer/cart', [
            'productId' => $product->id,
            'quantity' => 2,
        ])->assertOk()
            ->assertJsonPath('data.summary.itemCount', 2)
            ->assertJsonPath('data.items.0.product.id', $product->id)
            ->assertJsonPath('data.items.0.quantity', 2);
    }
}
