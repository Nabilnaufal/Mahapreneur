<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicCatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_returns_camel_case_sections(): void
    {
        $seller = User::create([
            'name' => 'Rani',
            'email' => 'rani@example.test',
            'password' => 'Rahasia123',
            'role' => 'SELLER',
            'email_verified_at' => now(),
        ]);
        $store = Store::create([
            'user_id' => $seller->id,
            'name' => 'Dapur Nusantara',
            'slug' => 'dapur-nusantara',
            'status' => 'ACTIVE',
        ]);
        $category = Category::create(['name' => 'Makanan', 'slug' => 'makanan']);
        $product = Product::create([
            'store_id' => $store->id,
            'category_id' => $category->id,
            'name' => 'Nasi Geprek',
            'slug' => 'nasi-geprek',
            'description' => 'Ayam geprek pedas dengan nasi hangat.',
            'price' => 18000,
            'stock' => 20,
            'status' => 'ACTIVE',
        ]);
        $product->images()->create(['url' => 'uploads/products/geprek.jpg', 'sort_order' => 0]);

        $this->getJson('/api/public/home')
            ->assertOk()
            ->assertJsonPath('data.latest.0.name', 'Nasi Geprek')
            ->assertJsonPath('data.latest.0.price', 18000)
            ->assertJsonStructure([
                'data' => [
                    'banners', 'categories', 'latest', 'bestsellers', 'recommended',
                    'categorySections', 'topStores', 'stats',
                ],
            ]);
    }

    public function test_product_list_supports_search_and_pagination_meta(): void
    {
        $this->getJson('/api/public/products?q=nasi&limit=5')
            ->assertOk()
            ->assertJsonStructure([
                'data' => ['products', 'meta' => ['total', 'page', 'limit', 'totalPages']],
            ]);
    }
}
