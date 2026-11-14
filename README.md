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
  <a href="https://expressjs.com/"><img alt="Express" src="https://img.shields.io/badge/API-Express_4-000000?style=for-the-badge&logo=express&logoColor=white"></a>
  <a href="https://www.prisma.io/"><img alt="Prisma" src="https://img.shields.io/badge/ORM-Prisma-2D3748?style=for-the-badge&logo=prisma&logoColor=white"></a>
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

- **`backend/`** — REST API Express + TypeScript + Prisma + MariaDB/MySQL.
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
- Autentikasi JWT di cookie `httpOnly`, verifikasi email, dan reset password.
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
| Backend | [Express 4](https://expressjs.com/) + [TypeScript](https://www.typescriptlang.org/) | REST API, validasi Zod, autentikasi |
| ORM | [Prisma 6](https://www.prisma.io/) | Schema, migrasi, dan query database |
| Database | [MariaDB 10.4 (XAMPP)](https://www.apachefriends.org/) | Penyimpanan data transaksi — dikelola lewat phpMyAdmin |
| Pembayaran | [DOKU Sandbox](https://www.doku.com/) | QRIS, Virtual Account, e-wallet, webhook |
| Runtime | [Node.js 20+](https://nodejs.org/) | Menjalankan backend dan build frontend |

## Arsitektur

```text
Browser
  │
  ├── Nuxt 4 (SSR)  ── httpOnly cookie ──►  Express REST API
  │        │                                     │
  │        └── Pinia (auth, cart, wishlist)      ├── Prisma ORM ──► MySQL
  │                                              ├── Upload lokal (backend/uploads)
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
|   |-- prisma/
|   |   |-- schema.prisma         # Schema database marketplace
|   |   |-- migrations/           # Riwayat migrasi database
|   |   `-- seed.ts               # Data demo & akun demo
|   |-- src/
|   |   |-- config/               # Env, Prisma, DOKU, mailer
|   |   |-- controllers/          # Public, auth, customer, seller, admin, payment
|   |   |-- middleware/           # Auth, role, upload
|   |   |-- routes/               # Definisi endpoint REST
|   |   |-- services/             # Logika pembayaran & transaksi
|   |   `-- utils/                # Response, validasi, serialisasi
|   `-- uploads/                  # File upload (tidak di-commit)
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

- [Node.js 20+](https://nodejs.org/) dan npm.
- [XAMPP](https://www.apachefriends.org/) (Apache + MariaDB/MySQL + phpMyAdmin) atau server MySQL 8 mandiri.
- Git.
- Kredensial **DOKU Sandbox** (opsional; tanpa kredensial aplikasi otomatis memakai mode simulasi).

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

> Default XAMPP memakai user `root` tanpa password di port `3306`. Kalau kamu sudah memberi password pada MySQL XAMPP, sesuaikan `DATABASE_URL` di langkah berikut.

### 3. Jalankan backend

```bash
cd backend
cp .env.example .env
npm install
```

Sesuaikan `backend/.env`, minimal:

```env
DATABASE_URL="mysql://root@127.0.0.1:3306/mahapreneur"
JWT_SECRET="ganti-dengan-string-acak-panjang"
CORS_ORIGIN=http://localhost:3000
APP_URL=http://localhost:3000
```

Lalu buat tabel dan isi data demo:

```bash
npx prisma migrate dev
npm run db:seed
npm run dev
```

Backend berjalan di `http://localhost:4000`.

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

Buka `http://localhost:3000`, lalu login memakai akun demo di bawah.

## Akun Demo

Semua akun memakai password yang sama: **`Mahapreneur1`**.

| Peran | Email | Toko |
| --- | --- | --- |
| Superadmin | `admin@mahapreneur.test` | — |
| Seller | `dapur@mahapreneur.test` | Dapur Nusantara |
| Seller | `kopi@mahapreneur.test` | Kopi Senja |
| Seller | `jasa@mahapreneur.test` | Studio Nadia |
| Customer | `pembeli@mahapreneur.test` | — |

> Akun demo dibuat oleh `npm run db:seed` hanya untuk pengembangan lokal. Ganti atau hapus sebelum aplikasi dipasang di server publik.

## Konfigurasi Environment

### Backend (`backend/.env`)

| Variabel | Keterangan |
| --- | --- |
| `DATABASE_URL` | Koneksi database, default XAMPP: `mysql://root@127.0.0.1:3306/mahapreneur` |
| `PORT` | Port API, default `4000` |
| `APP_URL` | URL frontend untuk link verifikasi & reset password |
| `API_URL` | URL publik API, dipakai untuk callback pembayaran |
| `CORS_ORIGIN` | Origin frontend yang diizinkan (boleh lebih dari satu, pisahkan koma) |
| `JWT_SECRET` | Rahasia signing JWT — wajib diisi dan wajib diganti di produksi |
| `SMTP_HOST` dll. | Konfigurasi email; jika kosong, email dicetak ke console |
| `DOKU_CLIENT_ID` / `DOKU_SECRET_KEY` | Kredensial DOKU; kosong berarti mode simulasi |
| `UPLOAD_DIR` / `MAX_UPLOAD_MB` | Lokasi dan batas ukuran file upload |

### Frontend (`frontend-web/.env`)

| Variabel | Keterangan |
| --- | --- |
| `NUXT_PUBLIC_API_BASE` | URL API, mis. `http://localhost:4000/api` |
| `NUXT_PUBLIC_API_ORIGIN` | Origin API untuk URL gambar upload |

## Pembayaran DOKU

Status pembayaran **hanya** berubah menjadi `PAID` setelah backend menerima dan memverifikasi notifikasi resmi DOKU. Redirect dari browser tidak pernah dipercaya.

### Mode simulasi

Jika `DOKU_CLIENT_ID` dan `DOKU_SECRET_KEY` kosong:

- Backend berjalan dalam mode simulasi dan menandai response dengan `simulated: true`.
- Halaman pembayaran menampilkan tombol **Simulasikan pembayaran berhasil**.
- Seluruh alur checkout, status pesanan, dan notifikasi tetap bisa diuji tanpa akun DOKU.

### Mode DOKU Sandbox asli

1. Isi `DOKU_CLIENT_ID` dan `DOKU_SECRET_KEY` dari dashboard DOKU Sandbox.
2. Daftarkan URL notifikasi `POST {API_URL}/api/payments/doku/notification`.
3. Backend memverifikasi signature HMAC-SHA256 dari raw body sebelum memproses.
4. Notifikasi duplikat diabaikan lewat kolom unik `notificationKey`.

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

Semua response memakai envelope `{ success, message, data }`.

| Prefix | Fungsi |
| --- | --- |
| `/api/public/*` | Homepage, katalog, detail produk, daftar toko |
| `/api/auth/*` | Registrasi, login, logout, verifikasi email, reset password |
| `/api/customer/*` | Keranjang, wishlist, alamat, checkout, pesanan, profil |
| `/api/seller/*` | Toko, produk, pesanan masuk, dashboard |
| `/api/admin/*` | Moderasi pengguna, toko, produk, pesanan, banner, log |
| `/api/notifications/*` | Notifikasi in-app |
| `/api/payments/*` | Metode pembayaran dan webhook DOKU |

## Keamanan

- Password di-hash dengan bcrypt; token JWT disimpan di cookie `httpOnly`, `sameSite`, dan `secure` saat produksi.
- Frontend tidak menyimpan token autentikasi di `localStorage`.
- Helmet, CORS terbatas, validasi input dengan Zod, dan pembatasan tipe/ukuran upload.
- Webhook DOKU diverifikasi signature-nya sebelum mengubah status pembayaran.
- File `.env`, folder upload, `node_modules`, dan artefak build tidak di-commit.

## Perintah Berguna

| Lokasi | Perintah | Fungsi |
| --- | --- | --- |
| `backend/` | `npm run dev` | Jalankan API dengan hot reload |
| `backend/` | `npm run typecheck` | Cek tipe TypeScript |
| `backend/` | `npm run build` | Kompilasi ke `dist/` |
| `backend/` | `npm run db:seed` | Isi ulang data demo |
| `backend/` | `npm run prisma:migrate` | Buat migrasi baru |
| `backend/` | `npm run prisma:deploy` | Terapkan migrasi di produksi |
| `frontend-web/` | `npm run dev` | Jalankan Nuxt dev server |
| `frontend-web/` | `npm run typecheck` | Cek tipe Vue + TypeScript |
| `frontend-web/` | `npm run build` | Build produksi |
| `frontend-web/` | `npm run preview` | Pratinjau hasil build |

## Catatan Deployment

- Set `NODE_ENV=production`, `JWT_SECRET` acak yang panjang, `CORS_ORIGIN`, dan `APP_URL` sesuai domain.
- Isi kredensial DOKU dan SMTP agar mode simulasi serta token verifikasi console tidak aktif.
- Arahkan `NUXT_PUBLIC_API_BASE` ke URL API publik, lalu build dengan `npm run build`.
- Folder `backend/uploads/` harus persisten (volume) atau diganti object storage.
- Jalankan `npm run prisma:deploy` saat deploy pertama dan setiap ada migrasi baru.
- Gunakan reverse proxy (Nginx/Caddy) dengan HTTPS agar cookie `secure` bekerja.

## Troubleshooting

**Port sudah dipakai** — ubah `PORT` di `backend/.env` atau hentikan proses lama:

```bash
lsof -i :3000 -i :4000
```

**Database tidak muncul di phpMyAdmin** — pastikan:
- Modul **MySQL** di XAMPP Control Panel sudah berstatus *Running* (port `3306`).
- Database `mahapreneur` sudah dibuat lewat phpMyAdmin.
- `DATABASE_URL` di `backend/.env` memakai port `3306` dan user `root`.
- Jangan menjalankan instance MySQL lain di port yang sama.

**Gagal konek database** — pastikan MySQL XAMPP berjalan dan `DATABASE_URL` benar, lalu cek:

```bash
npx prisma migrate status
```

**Prisma client tidak sinkron** — jalankan:

```bash
npx prisma generate
```

**Ikon tidak muncul** — pastikan browser memuat aset dari `frontend-web/public/`, lalu hard refresh.

**Pembayaran tidak berubah status** — cek log backend, pastikan signature webhook valid, dan `API_URL` bisa diakses oleh DOKU.

**`bcrypt` gagal dimuat setelah pindah mesin** — jalankan ulang:

```bash
npm rebuild bcrypt
```

**Upload tidak tampil** — pastikan `UPLOAD_DIR` ada dan `NUXT_PUBLIC_API_ORIGIN` menunjuk ke API yang benar.

## Lisensi

Dirilis di bawah [MIT License](./LICENSE). Silakan pakai, fork, dan modifikasi sesuai kebutuhan.
