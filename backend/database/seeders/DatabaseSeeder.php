<?php

namespace Database\Seeders;

use App\Models\ActivityLog;
use App\Models\Banner;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $image = fn (string $id) => "https://images.unsplash.com/{$id}?auto=format&fit=crop&w=900&q=80";

        // Hapus akun demo lama dan relasi transaksinya agar seeder tidak meninggalkan
        // akun dummy ketika dijalankan pada database yang sudah pernah di-seed.
        $dummyUsers = User::whereIn('email', [
            'admin@mahapreneur.test',
            'dapur@mahapreneur.test',
            'kopi@mahapreneur.test',
            'jasa@mahapreneur.test',
            'pembeli@mahapreneur.test',
        ])->get();

        foreach ($dummyUsers as $dummyUser) {
            $storeIds = Store::where('user_id', $dummyUser->id)->pluck('id');
            Order::where('customer_id', $dummyUser->id)->get()->each->delete();
            Order::whereIn('store_id', $storeIds)->get()->each->delete();
            Store::whereIn('id', $storeIds)->get()->each->delete();
            $dummyUser->delete();
        }

        // Tiga akun seed terpisah untuk cross-check setiap role.
        $tiaSeller = User::updateOrCreate(
            ['email' => 'tia@mahapreneur.test'],
            [
                'name' => 'Tia Seller',
                'password' => Hash::make('TiaSeller1!'),
                'role' => 'SELLER',
                'email_verified_at' => now(),
                'phone' => '081234567899',
            ],
        );
        User::updateOrCreate(
            ['email' => 'tia.customer@mahapreneur.test'],
            [
                'name' => 'Tia Customer',
                'password' => Hash::make('TiaCustomer1!'),
                'role' => 'CUSTOMER',
                'email_verified_at' => now(),
                'phone' => '081234567898',
            ],
        );
        $tiaAdmin = User::updateOrCreate(
            ['email' => 'tia.admin@mahapreneur.test'],
            [
                'name' => 'Tia Admin',
                'password' => Hash::make('TiaAdmin1!'),
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

        $stores = [
            'tia-store' => Store::updateOrCreate(
                ['slug' => 'tia-store'],
                [
                    'user_id' => $tiaSeller->id,
                    'name' => 'Tia Kitchen & Creative',
                    'campus' => 'Gedung A - Fakultas Ekonomi',
                    'address' => 'Jl. Kampus Raya No. 12, Depok',
                    'phone' => '081234567899',
                    'open_time' => '08:00',
                    'close_time' => '20:00',
                    'description' => 'Produk makanan, minuman, dan jasa pilihan mahasiswa dari Tia.',
                    'logo' => $image('photo-1556910103-1c02745aae4d'),
                    'banner' => $image('photo-1504674900247-0877df9cc836'),
                ],
            ),
        ];

        $products = [
            ['store' => 'tia-store', 'category' => 'makanan', 'name' => 'Nasi Ayam Geprek Sambal Bawang', 'price' => 18000, 'stock' => 40, 'sold' => 26,
                'description' => 'Ayam goreng tepung renyah dengan sambal bawang pedas segar, nasi hangat, dan lalapan. Porsi mahasiswa, bisa minta level pedas 1–5.',
                'images' => [$image('photo-1565299624946-b28f40a0ae38'), $image('photo-1555939594-58d7cb561ad1')]],
            ['store' => 'tia-store', 'category' => 'makanan', 'name' => 'Rice Bowl Telur Sambal Matah', 'price' => 15000, 'stock' => 30, 'sold' => 18,
                'description' => 'Rice bowl isi telur mata sapi, sambal matah segar, dan sayuran tumis. Cocok untuk makan siang di sela kelas.',
                'images' => [$image('photo-1546069901-ba9599a7e63c')]],
            ['store' => 'tia-store', 'category' => 'makanan', 'name' => 'Paket Katering Mingguan (5 Hari)', 'price' => 165000, 'stock' => 8, 'sold' => 3,
                'description' => 'Lima kotak makan untuk Senin–Jumat, dikirim ke kelas atau kos setiap pagi. Menu berganti tiap hari dan bisa diatur sesuai selera.',
                'images' => [$image('photo-1512621776951-a57141f2eefd')]],
            ['store' => 'tia-store', 'category' => 'buah-buahan', 'name' => 'Salad Buah Segar 350ml', 'price' => 22000, 'stock' => 12, 'sold' => 9,
                'description' => 'Potongan melon, semangka, anggur, dan stroberi dengan yogurt dressing tanpa gula tambahan.',
                'images' => [$image('photo-1610832958506-aa56368176cf')]],
            ['store' => 'tia-store', 'category' => 'minuman', 'name' => 'Es Kopi Susu Senja', 'price' => 17000, 'stock' => 50, 'sold' => 41,
                'description' => 'Espresso double shot, susu segar, dan gula aren cair. Rasanya seimbang, tidak terlalu manis.',
                'images' => [$image('photo-1461023058943-07fcbe16d735')]],
            ['store' => 'tia-store', 'category' => 'minuman', 'name' => 'Manual Brew Gayo V60', 'price' => 24000, 'stock' => 20, 'sold' => 12,
                'description' => 'Single origin Gayo, medium roast, diseduh V60 saat dipesan. Notes: jeruk, gula palem, aftertaste bersih.',
                'images' => [$image('photo-1495474472287-4d71bcdd2085')]],
            ['store' => 'tia-store', 'category' => 'minuman', 'name' => 'Biji Kopi Toraja 200gr', 'price' => 78000, 'stock' => 15, 'sold' => 6,
                'description' => 'Biji kopi Toraja sangrai medium, dikemas 200 gram dengan valve. Bisa digiling sesuai permintaan.',
                'images' => [$image('photo-1447933601403-0c6688de566e')]],
            ['store' => 'tia-store', 'category' => 'minuman', 'name' => 'Es Teh Lemon Mint', 'price' => 12000, 'stock' => 35, 'sold' => 22,
                'description' => 'Teh hitam dingin dengan perasan lemon segar dan daun mint. Pilihan ringan di siang hari.',
                'images' => [$image('photo-1517701550927-30cf4ba1dba5')]],
            ['store' => 'tia-store', 'category' => 'jasa', 'name' => 'Desain Logo Usaha Mahasiswa', 'price' => 150000, 'stock' => 10, 'sold' => 7, 'type' => 'SERVICE',
                'description' => 'Desain logo custom untuk usaha kecil mahasiswa, termasuk 2 kali revisi dan file final (PNG, JPG, SVG).',
                'service_terms' => 'Pemesanan minimal H-3. Revisi maksimal 2 kali. File sumber dikirim setelah pembayaran diterima.',
                'service_duration' => '2–3 hari kerja', 'images' => [$image('photo-1561070791-2526d30994b5')]],
            ['store' => 'tia-store', 'category' => 'jasa', 'name' => 'Editing Video Presentasi 3 Menit', 'price' => 200000, 'stock' => 6, 'sold' => 2, 'type' => 'SERVICE',
                'description' => 'Editing video presentasi atau tugas kuliah hingga 3 menit: cut, transisi, subtitle, dan color grading ringan.',
                'service_terms' => 'Kirim materi mentah dalam format MP4. Revisi maksimal 2 kali. Durasi lebih dari 3 menit dikenakan biaya tambahan.',
                'service_duration' => '3–4 hari kerja', 'images' => [$image('photo-1521737604893-d14cc237f11d')]],
            ['store' => 'tia-store', 'category' => 'jasa', 'name' => 'Jasa Desain Feed Instagram (5 Post)', 'price' => 175000, 'stock' => 8, 'sold' => 4, 'type' => 'SERVICE',
                'description' => 'Lima desain feed Instagram dengan template konsisten, cocok untuk UMKM mahasiswa yang baru mulai.',
                'service_terms' => 'Konten dan foto disediakan pemesan. Revisi 1 kali per desain. File dikirim dalam PNG dan Canva template.',
                'service_duration' => '3 hari kerja', 'images' => [$image('photo-1561070791-2526d30994b5')]],
            ['store' => 'tia-store', 'category' => 'lainnya', 'name' => 'Paket CV ATS-Friendly', 'price' => 65000, 'stock' => 25, 'sold' => 11, 'type' => 'SERVICE',
                'description' => 'Penulisan ulang dan desain CV yang lolos sistem ATS, siap dipakai untuk magang dan pekerjaan pertama.',
                'service_terms' => 'Pemesan mengirim CV lama dan deskripsi posisi yang dituju. Revisi 1 kali.',
                'service_duration' => '1–2 hari kerja', 'images' => [$image('photo-1498049794561-7780e7231661')]],
        ];

        foreach ($products as $product) {
            $slug = Str::slug($product['name']);
            $row = Product::firstOrCreate(['slug' => $slug], [
                'store_id' => $stores[$product['store']]->id,
                'category_id' => Category::where('slug', $product['category'])->value('id'),
                'name' => $product['name'],
                'description' => $product['description'],
                'price' => $product['price'],
                'stock' => $product['stock'],
                'type' => $product['type'] ?? 'GOODS',
                'status' => 'ACTIVE',
                'sold' => $product['sold'] ?? 0,
                'service_terms' => $product['service_terms'] ?? null,
                'service_duration' => $product['service_duration'] ?? null,
            ]);
            if ($row->wasRecentlyCreated) {
                foreach ($product['images'] as $index => $url) {
                    $row->images()->create(['url' => $url, 'sort_order' => $index]);
                }
            }
        }

        foreach ([
                ['title' => 'Belanja dari tetangga kampusmu.', 'description' => 'Makanan, minuman, dan jasa dari mahasiswa lain — diantar langsung oleh seller ke kelas atau kosmu.', 'image' => $image('photo-1504674900247-0877df9cc836'), 'cta_label' => 'Jelajahi Produk', 'cta_url' => '/catalog', 'sort_order' => 1],
                ['title' => 'Buka toko pertamamu di Mahapreneur.', 'description' => 'Daftar sebagai seller, unggah produk, dan mulai terima pesanan dari mahasiswa se-kampus.', 'image' => $image('photo-1521737604893-d14cc237f11d'), 'cta_label' => 'Mulai Berjualan', 'cta_url' => '/auth/register?role=SELLER', 'sort_order' => 2],
                ['title' => 'Bayar fleksibel: QRIS, VA, atau COD.', 'description' => 'Pilih pembayaran online lewat DOKU atau bayar langsung saat pesanan diterima.', 'image' => $image('photo-1556742049-0cfed4f6a45d'), 'cta_label' => 'Cara Berbelanja', 'cta_url' => '/catalog', 'sort_order' => 3],
        ] as $banner) {
            Banner::updateOrCreate(['sort_order' => $banner['sort_order']], $banner);
        }

        ActivityLog::create([
            'user_id' => $tiaAdmin->id,
            'action' => 'SEED',
            'entity' => 'System',
            'description' => 'Data demo Mahapreneur dimuat',
        ]);

        $this->command?->info('Seed selesai. Akun Tia tersedia untuk role CUSTOMER, SELLER, dan SUPERADMIN.');
    }
}
