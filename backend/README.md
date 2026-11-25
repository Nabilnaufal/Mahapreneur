# Mahapreneur API

REST API Laravel 12 untuk marketplace Mahapreneur. Frontend Nuxt berjalan terpisah di `../frontend-web`.

## Menjalankan

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan serve --host=0.0.0.0 --port=8000
```

Butuh PHP 8.2+, Composer, dan MySQL/MariaDB. Seeder lokal menyediakan akun `customer@gmail.com`, `seller@gmail.com`, dan `admin@gmail.com` (password: `password`) untuk role `CUSTOMER`, `SELLER`, dan `SUPERADMIN`.

## Struktur

- `app/Http/Controllers/{Auth,Public,Customer,Seller,Admin}` — endpoint per role dan menu.
- `app/Models` — model Eloquent; response memakai camelCase.
- `app/Services` — DOKU dan alur pembayaran.
- `routes/api.php` — daftar endpoint.
- `config/mahapreneur.php` — konfigurasi khusus aplikasi.
- `database/migrations` dan `database/seeders` — schema dan data demo.

## Perintah

```bash
php artisan test      # test PHPUnit
vendor/bin/pint       # format kode
php artisan route:list
```

Dokumentasi lengkap ada di `../README.md`.
