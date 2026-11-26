<?php

namespace Database\Seeders;

use App\Models\Banner;
use App\Models\Category;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $image = fn (string $id) => "https://images.unsplash.com/{$id}?auto=format&fit=crop&w=900&q=80";

        User::updateOrCreate(
            ['email' => 'seller@gmail.com'],
            [
                'name' => 'Tia Seller',
                'password' => Hash::make('password'),
                'role' => 'SELLER',
                'email_verified_at' => now(),
                'phone' => '081234567899',
            ],
        );
        User::updateOrCreate(
            ['email' => 'customer@gmail.com'],
            [
                'name' => 'Tia Customer',
                'password' => Hash::make('password'),
                'role' => 'CUSTOMER',
                'email_verified_at' => now(),
                'phone' => '081234567898',
            ],
        );
        User::updateOrCreate(
            ['email' => 'admin@gmail.com'],
            [
                'name' => 'Tia Admin',
                'password' => Hash::make('password'),
                'role' => 'SUPERADMIN',
                'email_verified_at' => now(),
                'phone' => '081234567897',
            ],
        );

        $categories = [
            ['name' => 'Makanan', 'slug' => 'makanan', 'icon' => 'UtensilsCrossed', 'sort_order' => 1],
            ['name' => 'Minuman', 'slug' => 'minuman', 'icon' => 'Coffee', 'sort_order' => 2],
            ['name' => 'Buah-buahan', 'slug' => 'buah-buahan', 'icon' => 'Apple', 'sort_order' => 3],
            ['name' => 'Fashion', 'slug' => 'fashion', 'icon' => 'Shirt', 'sort_order' => 4],
            ['name' => 'Aksesoris', 'slug' => 'aksesoris', 'icon' => 'Watch', 'sort_order' => 5],
            ['name' => 'Elektronik', 'slug' => 'elektronik', 'icon' => 'Laptop', 'sort_order' => 6],
            ['name' => 'Jasa', 'slug' => 'jasa', 'icon' => 'Briefcase', 'sort_order' => 7],
            ['name' => 'Lainnya', 'slug' => 'lainnya', 'icon' => 'Package', 'sort_order' => 8],
        ];
        foreach ($categories as $category) {
            Category::updateOrCreate(['slug' => $category['slug']], $category);
        }

        foreach ([
            ['title' => 'Belanja dari tetangga kampusmu.', 'description' => 'Makanan, minuman, dan jasa dari mahasiswa lain — diantar langsung oleh seller ke kelas atau kosmu.', 'image' => $image('photo-1504674900247-0877df9cc836'), 'cta_label' => 'Jelajahi Produk', 'cta_url' => '/catalog', 'sort_order' => 1],
            ['title' => 'Buka toko pertamamu di Mahapreneur.', 'description' => 'Daftar sebagai seller, unggah produk, dan mulai terima pesanan dari mahasiswa se-kampus.', 'image' => $image('photo-1521737604893-d14cc237f11d'), 'cta_label' => 'Mulai Berjualan', 'cta_url' => '/auth/register?role=SELLER', 'sort_order' => 2],
            ['title' => 'Bayar fleksibel: QRIS, VA, atau COD.', 'description' => 'Pilih pembayaran online lewat DOKU atau bayar langsung saat pesanan diterima.', 'image' => $image('photo-1556742049-0cfed4f6a45d'), 'cta_label' => 'Cara Berbelanja', 'cta_url' => '/catalog', 'sort_order' => 3],
        ] as $banner) {
            Banner::updateOrCreate(['sort_order' => $banner['sort_order']], $banner);
        }

        $this->command?->info('Seed selesai. Akun demo tersedia untuk role CUSTOMER, SELLER, dan SUPERADMIN.');
    }
}
