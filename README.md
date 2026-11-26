<h1 align="center">Mahapreneur</h1>

<p align="center">
  <img src="./frontend-web/public/logo-full.png" alt="Mahapreneur" width="340">
</p>

<p align="center">
  Marketplace multi-vendor untuk bisnis mahasiswa. Mahasiswa bisa membuka toko, menjual produk atau jasa, dan mengantar sendiri pesanan ke pembeli di lingkungan kampus.
</p>

<p align="center">
  <a href="https://nuxt.com/"><img alt="Nuxt" src="https://img.shields.io/badge/Framework-Nuxt_4-00DC82?style=for-the-badge&logo=nuxt&logoColor=white"></a>
  <a href="https://vuejs.org/"><img alt="Vue" src="https://img.shields.io/badge/UI-Vue_3-42B883?style=for-the-badge&logo=vue.js&logoColor=white"></a>
  <a href="https://www.typescriptlang.org/"><img alt="TypeScript" src="https://img.shields.io/badge/Language-TypeScript-3178C6?style=for-the-badge&logo=typescript&logoColor=white"></a>
  <a href="https://tailwindcss.com/"><img alt="Tailwind CSS" src="https://img.shields.io/badge/Style-Tailwind_CSS_4-06B6D4?style=for-the-badge&logo=tailwindcss&logoColor=white"></a>
  <a href="https://pinia.vuejs.org/"><img alt="Pinia" src="https://img.shields.io/badge/State-Pinia-FFD859?style=for-the-badge&logo=vue.js&logoColor=black"></a>
</p>

<p align="center">
  <a href="https://laravel.com/"><img alt="Laravel" src="https://img.shields.io/badge/API-Laravel_12-FF2D20?style=for-the-badge&logo=laravel&logoColor=white"></a>
  <a href="https://www.php.net/"><img alt="PHP" src="https://img.shields.io/badge/PHP-8.2+-777BB4?style=for-the-badge&logo=php&logoColor=white"></a>
  <a href="https://www.apachefriends.org/"><img alt="MariaDB" src="https://img.shields.io/badge/Database-MariaDB_10.4-003545?style=for-the-badge&logo=mariadb&logoColor=white"></a>
  <a href="https://www.doku.com/"><img alt="DOKU" src="https://img.shields.io/badge/Payment-DOKU_Sandbox-102C40?style=for-the-badge&logo=databricks&logoColor=DBFF00"></a>
  <a href="https://nodejs.org/"><img alt="Node.js" src="https://img.shields.io/badge/Runtime-Node.js_20-5FA04E?style=for-the-badge&logo=nodedotjs&logoColor=white"></a>
</p>

<p align="center">
  <a href="./LICENSE"><img alt="License" src="https://img.shields.io/badge/License-MIT-3DA639?style=for-the-badge"></a>
  <img alt="Status" src="https://img.shields.io/badge/Status-Siap_Dijalankan-102C40?style=for-the-badge">
</p>

## Ringkasan

Mahapreneur adalah aplikasi marketplace tempat mahasiswa membuka toko sendiri dan menjual produk atau jasa ke mahasiswa lain. Setiap pesanan diantar langsung oleh penjual ke pembeli — tanpa ekspedisi, tanpa ongkos kirim, dan tanpa nomor resi.

Aplikasi ini dibangun sebagai proyek mata kuliah E-Business dengan dua bagian:

- **`backend/`** — REST API Laravel 12 + Eloquent + MySQL.
- **`frontend-web/`** — aplikasi Nuxt 4 + Vue 3 + Tailwind CSS + Pinia.

Pembayaran online memakai **DOKU Sandbox** (QRIS, Virtual Account, dan e-wallet) ditambah opsi COD. Status pembayaran hanya menjadi `PAID` setelah backend menerima dan memverifikasi notifikasi resmi DOKU.

## Fitur Utama

- Katalog produk dan jasa dengan pencarian, filter kategori/toko/harga, serta pagination.
- Halaman toko dengan banner, logo, jam operasional, lokasi kampus, dan daftar produk.
- Keranjang per toko, checkout multi-seller, dan pemisahan pesanan otomatis per penjual.
- Wishlist, alamat pengiriman, profil, dan riwayat pesanan untuk customer.
- Dashboard seller: statistik toko, grafik pendapatan 7 hari, kelola produk, dan update status pesanan.
- Dashboard superadmin: pengguna, kategori, toko, produk, pesanan, pembayaran, banner, dan log aktivitas.
- Notifikasi in-app untuk perubahan status pesanan dan pembayaran.
- Autentikasi token terenkripsi di cookie `httpOnly`, verifikasi email, dan reset password.
- Upload gambar produk, logo toko, dan banner dengan validasi tipe file.
- Tema terang/gelap, layout responsif, animasi tambah ke keranjang, dan swipe banner di mobile.
- Pembayaran DOKU Sandbox dengan verifikasi signature webhook dan proteksi notifikasi duplikat.

## Peran Pengguna

| Peran | Kemampuan |
| --- | --- |
| Customer | Belanja, wishlist, keranjang, checkout, bayar, dan melacak status pesanan. |
| Seller | Membuka toko, mengelola produk, menerima pesanan, dan mengantar sendiri pesanan. |
| Superadmin | Memantau seluruh marketplace, moderasi toko/produk, serta mengelola kategori dan banner. |

## Teknologi

| Bagian | Teknologi | Fungsi |
| --- | --- | --- |
| Frontend | [Nuxt 4](https://nuxt.com/) + [Vue 3](https://vuejs.org/) | SSR, routing berbasis file, halaman publik & dashboard |
| Styling | [Tailwind CSS 4](https://tailwindcss.com/) + [Lucide](https://lucide.dev/) | Design system, tema, dan ikon |
| State | [Pinia](https://pinia.vuejs.org/) | Auth, keranjang, wishlist, UI, notifikasi |
| Backend | [Laravel 12](https://laravel.com/) | REST API, routing, validasi, autentikasi, dan keamanan |
| ORM | [Eloquent](https://laravel.com/docs/eloquent) | Model, relasi, migrasi, dan query database |
| Database | [MariaDB 10.4 (XAMPP)](https://www.apachefriends.org/) | Penyimpanan data transaksi — dikelola lewat phpMyAdmin |
| Pembayaran | [DOKU Sandbox](https://www.doku.com/) | QRIS, Virtual Account, e-wallet, webhook |
| Runtime | PHP 8.2+ & [Node.js 20+](https://nodejs.org/) | Menjalankan API dan build frontend |

## Arsitektur

```text
Browser
  │
  ├── Nuxt 4 (SSR)  ── httpOnly cookie ──►  Laravel 12 REST API
  │        │                                     │
  │        └── Pinia (auth, cart, wishlist)      ├── Eloquent ORM ──► MySQL
  │                                              ├── Upload lokal (backend/public/uploads)
  │                                              └── DOKU Sandbox ◄── webhook notifikasi
  │
  └── Halaman publik, customer, seller, dan admin
```

## Struktur Project

```text
.
|-- assets/
|   `-- icon/                     # Sumber logo & favicon
|-- backend/
|   |-- app/
|   |   |-- Http/
|   |   |   |-- Controllers/Api/   # Public, auth, customer, seller, admin, payment
|   |   |   |-- Middleware/        # Auth token, role, toko, normalisasi input
|   |   |   `-- Resources/         # Bentuk response camelCase
|   |   |-- Models/                # Model Eloquent marketplace
|   |   |-- Services/              # DOKU & alur pembayaran
|   |   `-- Support/               # Token, upload, activity log, helper
|   |-- database/
|   |   |-- migrations/            # Schema marketplace
|   |   `-- seeders/               # Data demo & akun demo
|   |-- routes/api.php             # Definisi endpoint REST
|   |-- config/mahapreneur.php     # Konfigurasi khusus aplikasi
|   `-- public/uploads/            # File upload (tidak di-commit)
|-- frontend-web/
|   |-- app/
|   |   |-- assets/css/           # Design token & animasi
|   |   |-- components/           # Komponen UI
|   |   |-- composables/          # useApi, format, fly-to-cart
|   |   |-- layouts/              # Default, auth, dashboard
|   |   |-- pages/                # Routing berbasis file
|   |   |-- stores/               # Pinia stores
|   |   `-- types/                # Tipe data bersama
|   |-- public/                   # Logo, favicon, og-image
|   `-- nuxt.config.ts
|-- README.md
`-- LICENSE
```

## Prasyarat

- PHP 8.2+ dengan ekstensi `pdo_mysql`, `mbstring`, `openssl`, dan `fileinfo`.
- [Composer](https://getcomposer.org/).
- [Node.js 20+](https://nodejs.org/) dan npm.
- [XAMPP](https://www.apachefriends.org/) (Apache + MariaDB/MySQL + phpMyAdmin) atau server MySQL 8 mandiri.
- Git.
- Kredensial **DOKU Sandbox** (wajib untuk pembayaran online; tanpa kredensial hanya COD yang muncul di checkout).

## Instalasi

### 1. Clone repository

```bash
git clone https://github.com/nabil-udah-kenyang/mahapreneur.git
cd mahapreneur
```

### 2. Siapkan database lewat XAMPP

1. Buka **XAMPP Control Panel**, lalu klik **Start** pada **Apache** dan **MySQL**.
2. Buka `http://localhost/phpmyadmin` di browser.
3. Klik **New** di panel kiri, isi nama database `mahapreneur`, pilih collation `utf8mb4_unicode_ci`, lalu klik **Create**.
4. Database `mahapreneur` langsung muncul di daftar kiri phpMyAdmin.

Alternatif lewat terminal Linux:

```bash
/opt/lampp/bin/mysql -u root -e "CREATE DATABASE mahapreneur CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

Windows:

```powershell
C:\xampp\mysql\bin\mysql.exe -u root -e "CREATE DATABASE mahapreneur CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

> Default XAMPP memakai user `root` tanpa password di port `3306`. Kalau kamu sudah memberi password pada MySQL XAMPP, sesuaikan `DB_PASSWORD` di langkah berikut.

### 3. Jalankan backend

```bash
cd backend
composer install
cp .env.example .env
php artisan key:generate
```

Sesuaikan `backend/.env`, minimal:

```env
APP_URL=http://localhost:8000
FRONTEND_URL=http://localhost:3000
API_URL=http://localhost:8000
APP_TIMEZONE=Asia/Jakarta
CORS_ORIGIN=http://localhost:3000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=mahapreneur
DB_USERNAME=root
DB_PASSWORD=
```

Lalu buat tabel dan isi data demo:

```bash
php artisan migrate --seed
php artisan serve --host=0.0.0.0 --port=8000
```

Backend berjalan di `http://localhost:8000`. Endpoint kesehatan: `GET /health`.

### 4. Jalankan frontend

Buka terminal lain:

```bash
cd frontend-web
cp .env.example .env
npm install
npm run dev
```

Frontend berjalan di `http://localhost:3000`.

### 5. Masuk ke aplikasi

Buka `http://localhost:3000`, lalu login memakai akun seed di bawah.

## Akun Seed Lokal

Gunakan tiga kredensial berikut untuk cross-check tiap role:

| Peran | Email | Toko |
| --- | --- | --- |
| Customer | `customer@gmail.com` | — |
| Seller | `seller@gmail.com` | Tia Kitchen & Creative |
| Superadmin | `admin@gmail.com` | — |

Password ketiga akun: `password`. Semua akun dibuat oleh `php artisan migrate --seed` hanya untuk pengembangan lokal.

## Konfigurasi Environment

### Backend (`backend/.env`)

| Variabel | Keterangan |
| --- | --- |
| `APP_KEY` | Kunci enkripsi Laravel — dibuat dengan `php artisan key:generate` |
| `APP_URL` | URL backend Laravel |
| `FRONTEND_URL` | URL frontend untuk link verifikasi & reset password |
| `API_URL` | URL publik API, dipakai untuk URL gambar dan callback pembayaran |
| `APP_TIMEZONE` | Zona waktu aplikasi, default `Asia/Jakarta` |
| `CORS_ORIGIN` | Origin frontend yang diizinkan, yaitu port `3000` — bukan port backend (boleh lebih dari satu, pisahkan koma) |
| `DB_CONNECTION`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` | Koneksi database, default XAMPP |
| `MAIL_MAILER` / `RESEND_API_KEY` | Mailer Resend dan API key untuk email verifikasi/reset password |
| `MAIL_FROM_ADDRESS` / `MAIL_FROM_NAME` | Identitas pengirim email dari domain `nabil-naufal.online` yang sudah diverifikasi di Resend |
| `DOKU_CLIENT_ID` / `DOKU_SECRET_KEY` | Kredensial DOKU; kosong berarti hanya COD yang ditawarkan |
| `DOKU_METHODS` | Metode online yang ditawarkan, default `OVO,DANA` (channel harus aktif di akun DOKU) |
| `MAX_UPLOAD_MB` | Batas ukuran file upload |
| `AUTH_TTL_DAYS` / `EMAIL_VERIFICATION_TTL_HOURS` / `PASSWORD_RESET_TTL_MINUTES` | Masa berlaku token |
| `PAYMENT_TTL_MINUTES` | Masa berlaku instruksi pembayaran |

### Frontend (`frontend-web/.env`)

| Variabel | Keterangan |
| --- | --- |
| `NUXT_PUBLIC_API_BASE` | URL API, mis. `http://localhost:8000/api` |
| `NUXT_PUBLIC_API_ORIGIN` | Origin API untuk URL gambar upload |

## Pembayaran DOKU

Status pembayaran **hanya** berubah menjadi `PAID` setelah backend menerima dan memverifikasi notifikasi resmi DOKU. Redirect dari browser tidak pernah dipercaya.

### Tanpa kredensial

Jika `DOKU_CLIENT_ID` dan `DOKU_SECRET_KEY` kosong, endpoint `/api/payments/methods`
hanya mengembalikan COD dan pembayaran online disembunyikan. Tidak ada mode simulasi.

### DOKU Sandbox asli

1. Isi `DOKU_CLIENT_ID` dan `DOKU_SECRET_KEY` dari dashboard DOKU Sandbox, plus
   `DOKU_BASE_URL=https://api-sandbox.doku.com`.
2. Pilih channel yang sudah aktif di akun Sandbox lewat `DOKU_METHODS` (default `OVO,DANA`).
   Channel nonaktif ditolak DOKU dengan `PAYMENT CHANNEL IS INACTIVE`.
3. Daftarkan URL notifikasi `POST {API_URL}/api/payments/doku/notification`.
4. Backend memverifikasi signature HMAC-SHA256 dari raw body sebelum memproses.
5. Notifikasi duplikat diabaikan lewat kolom unik `notification_key`.

## Status Pesanan & Pembayaran

| Jenis | Nilai |
| --- | --- |
| Pesanan | `WAITING_PAYMENT` → `CONFIRMED` → `PROCESSING` → `READY` → `ON_DELIVERY` → `COMPLETED` (`CANCELLED`) |
| Pembayaran | `PENDING`, `PAID`, `FAILED`, `EXPIRED`, `UNPAID_COD`, `REFUNDED` |

## Aturan Bisnis

- **Tidak ada pengiriman, ekspedisi, ongkir, atau resi.** Seller mengantar sendiri pesanan ke alamat yang diisi customer saat checkout.
- Tidak ada rating atau jumlah terjual palsu — semua angka berasal dari transaksi nyata.
- Tidak ada tombol atau halaman dekoratif; setiap fitur memiliki fungsi.
- Harga dan stok dihitung ulang di server saat checkout; stok dikurangi dengan update bersyarat agar tidak minus.
- Checkout memecah keranjang menjadi satu pesanan per toko dalam satu transaksi database.
- Uang disimpan sebagai integer rupiah tanpa desimal.

## Ringkasan API

Semua response memakai envelope `{ success, message, data }` dengan field camelCase.

| Prefix | Fungsi |
| --- | --- |
| `/api/public/*` | Homepage, katalog, detail produk, daftar toko |
| `/api/auth/*` | Registrasi, login, logout, verifikasi email, reset password |
| `/api/customer/*` | Keranjang, wishlist, alamat, checkout, pesanan, profil, chat penjual |
| `/api/seller/*` | Toko, produk, pesanan masuk, chat pembeli, dashboard |
| `/api/admin/*` | Moderasi pengguna, toko, produk, pesanan, banner, log |
| `/api/notifications/*` | Notifikasi in-app |
| `/api/payments/*` | Metode pembayaran dan webhook DOKU |

## Keamanan

- Password di-hash dengan bcrypt (cast `hashed` Eloquent); token autentikasi dienkripsi `Crypt` lalu disimpan di cookie `httpOnly`, `sameSite`, dan `secure` saat produksi.
- Frontend tidak menyimpan token autentikasi di `localStorage`.
- CORS terbatas, validasi input Laravel, dan pembatasan tipe/ukuran upload.
- Webhook DOKU diverifikasi signature-nya sebelum mengubah status pembayaran.
- File `.env`, folder upload, `node_modules`, `vendor`, dan artefak build tidak di-commit.

## Perintah Berguna

| Lokasi | Perintah | Fungsi |
| --- | --- | --- |
| `backend/` | `php artisan serve --port=8000` | Jalankan API |
| `backend/` | `composer dev` | Jalankan API di port `8000` |
| `backend/` | `php artisan migrate --seed` | Buat tabel dan isi data demo |
| `backend/` | `php artisan migrate:fresh --seed` | Reset database lalu isi data demo |
| `backend/` | `php artisan test` | Jalankan test PHPUnit |
| `backend/` | `vendor/bin/pint` | Rapikan gaya kode PHP |
| `backend/` | `php artisan route:list` | Lihat daftar endpoint |
| `frontend-web/` | `npm run dev` | Jalankan Nuxt dev server |
| `frontend-web/` | `npm run typecheck` | Cek tipe Vue + TypeScript |
| `frontend-web/` | `npm run build` | Build produksi |
| `frontend-web/` | `npm run preview` | Pratinjau hasil build |

## Catatan Deployment

- Set `APP_ENV=production`, `APP_DEBUG=false`, `APP_KEY` acak, `CORS_ORIGIN`, dan `APP_URL` sesuai domain.
- Isi kredensial DOKU dan `RESEND_API_KEY`; tanpa kredensial DOKU checkout hanya menawarkan COD.
- Arahkan `NUXT_PUBLIC_API_BASE` ke URL API publik, lalu build dengan `npm run build`.
- Folder `backend/public/uploads/` harus persisten (volume) atau diganti object storage.
- Jalankan `php artisan migrate --force` saat deploy pertama dan setiap ada migrasi baru.
- Gunakan reverse proxy (Nginx/Caddy) dengan HTTPS agar cookie `secure` bekerja, dan arahkan document root ke `backend/public`.
- Jalankan `php artisan config:cache` dan `php artisan route:cache` setelah konfigurasi final.

## Troubleshooting

**Port sudah dipakai** — ubah port `php artisan serve` atau hentikan proses lama:

```bash
lsof -i :3000 -i :8000
```

**Database tidak muncul di phpMyAdmin** — pastikan:
- Modul **MySQL** di XAMPP Control Panel sudah berstatus *Running* (port `3306`).
- Database `mahapreneur` sudah dibuat lewat phpMyAdmin.
- `DB_HOST`, `DB_PORT`, dan kredensial di `backend/.env` sudah benar.
- Jangan menjalankan instance MySQL lain di port yang sama.

**Gagal konek database** — pastikan MySQL XAMPP berjalan dan konfigurasi `DB_*` benar, lalu cek:

```bash
php artisan migrate:status
```

**Perubahan kode tidak terbaca** — bersihkan cache konfigurasi:

```bash
php artisan optimize:clear
```

**Ikon tidak muncul** — pastikan browser memuat aset dari `frontend-web/public/`, lalu hard refresh.

**Pembayaran tidak berubah status** — cek `backend/storage/logs/laravel.log`, pastikan signature webhook valid, dan `API_URL` bisa diakses oleh DOKU.

**Upload tidak tampil** — pastikan folder `backend/public/uploads` ada dan bisa ditulis, serta `NUXT_PUBLIC_API_ORIGIN` menunjuk ke API yang benar.

## Lisensi

Dirilis di bawah [MIT License](./LICENSE). Silakan pakai, fork, dan modifikasi sesuai kebutuhan.
