# Deploy gratis Mahapreneur

Setup ini memakai:

- Frontend Nuxt: Vercel, root directory `frontend-web`.
- Backend Laravel: Render Web Service, root directory `backend`.
- Database: MySQL eksternal yang bisa diakses publik, misalnya FreeDB untuk demo sementara.
- Email: Resend.
- Pembayaran: DOKU Sandbox.

## 1. Push repository ke GitHub

Jangan push file `.env`. File tersebut sudah masuk `.gitignore`.

## 2. Buat database MySQL

Catat host, port, nama database, username, dan password dari provider MySQL. Database gratis pihak ketiga biasanya memiliki batas penyimpanan atau masa aktif, jadi lakukan export berkala.

## 3. Deploy backend ke Render

Buat **New Web Service** dari repository GitHub dan gunakan:

- Root Directory: `backend`
- Runtime: `Docker`
- Dockerfile: `backend/Dockerfile`
- Health Check Path: `/health`
- Plan: `Free`

Environment variables minimum:

```env
APP_NAME=Mahapreneur
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:GENERATE_DENGAN_php_artisan_key:generate
APP_URL=https://NAMA-SERVICE.onrender.com
API_URL=https://NAMA-SERVICE.onrender.com
FRONTEND_URL=https://NAMA-PROJECT.vercel.app
CORS_ORIGIN=https://NAMA-PROJECT.vercel.app

DB_CONNECTION=mysql
DB_HOST=HOST_MYSQL
DB_PORT=3306
DB_DATABASE=NAMA_DATABASE
DB_USERNAME=USERNAME_DATABASE
DB_PASSWORD=PASSWORD_DATABASE

MAIL_MAILER=resend
RESEND_API_KEY=re_...
MAIL_FROM_ADDRESS=noreply@domain-terverifikasi.com
MAIL_FROM_NAME=Mahapreneur

DOKU_CLIENT_ID=...
DOKU_SECRET_KEY=...
DOKU_BASE_URL=https://api-sandbox.doku.com
DOKU_QRIS_CHANNEL=QRIS
DOKU_VA_CHANNEL=BCA
DOKU_METHODS=SHOPEEPAY
```

Generate `APP_KEY` secara lokal dari folder backend:

```bash
php artisan key:generate --show
```

Setelah service online, jalankan migration dari Shell Render:

```bash
php artisan migrate --force
```

Uji backend:

```text
https://NAMA-SERVICE.onrender.com/health
```

## 4. Deploy frontend ke Vercel

Buat project dari repository yang sama dan gunakan root directory `frontend-web`.

Environment variables:

```env
NUXT_PUBLIC_API_BASE=https://NAMA-SERVICE.onrender.com/api
NUXT_PUBLIC_API_ORIGIN=https://NAMA-SERVICE.onrender.com
```

## 5. Hubungkan Resend dan DOKU

Di Resend, verifikasi domain pengirim lalu gunakan alamat dari domain tersebut pada `MAIL_FROM_ADDRESS`.

Di DOKU Sandbox, isi Notification URL:

```text
https://NAMA-SERVICE.onrender.com/api/payments/doku/notification
```

URL webhook harus publik dan HTTPS. Jangan pakai localhost atau URL tunnel lama.

Notification URL diisi **per channel**, bukan satu kali untuk semua: buka
**Settings → Payment Settings → e-Wallet** lalu klik **CONFIGURE** pada OVO/DANA.
Channel yang Notification URL-nya kosong tidak akan pernah mengirim notifikasi, sehingga
status pembayaran di aplikasi tetap `PENDING` meskipun customer sudah membayar.

Sebagai jaring pengaman, aplikasi juga menanyakan status langsung ke DOKU
(`GET /orders/v1/status/{invoice_number}`) saat halaman pembayaran atau detail pesanan
dibuka dan pembayaran masih `PENDING` lebih dari 60 detik. Jadi status tetap terkoreksi
walau notifikasi tidak sampai.

### Channel DOKU yang tidak aktif

DOKU menolak invoice dengan pesan `PAYMENT CHANNEL IS INACTIVE` bila channel belum
diaktifkan di akun Sandbox, atau bila kode channel-nya salah. Hasil uji langsung ke
akun demo (26 Nov 2026): `EMONEY_SHOPEE_PAY`, `EMONEY_LINKAJA`, `VIRTUAL_ACCOUNT_BCA`
dan `VIRTUAL_ACCOUNT_DOKU` berhasil membuat invoice; `EMONEY_OVO` dan `EMONEY_DANA`
membuat invoice tetapi pembayarannya gagal di halaman DOKU; `QRIS` masih nonaktif.

Karena itu `DOKU_METHODS` default-nya `SHOPEEPAY`. Kode channel untuk ShopeePay adalah
`EMONEY_SHOPEE_PAY` (memakai garis bawah) — `EMONEY_SHOPEEPAY` ditolak DOKU dengan
pesan `PAYMENT CHANNEL IS INACTIVE` yang menyesatkan.

Tambahkan channel lain lewat env, contoh `DOKU_METHODS=SHOPEEPAY,LINKAJA,VIRTUAL_ACCOUNT`.
Pesan error asli DOKU ikut ditampilkan di halaman pembayaran agar penyebabnya kelihatan.

## 5b. Deploy backend ke Railway

Langkahnya sama dengan Render: root directory `backend`, builder Dockerfile, health check
`/health`. Tambahkan environment variables yang sama seperti daftar di langkah 3, dengan
penyesuaian:

```env
APP_URL=https://NAMA-SERVICE.up.railway.app
API_URL=https://NAMA-SERVICE.up.railway.app
FRONTEND_URL=https://NAMA-PROJECT.vercel.app
CORS_ORIGIN=https://NAMA-PROJECT.vercel.app
```

Catatan penting di Railway:

- Isi `DOKU_CLIENT_ID`, `DOKU_SECRET_KEY`, dan `DOKU_BASE_URL=https://api-sandbox.doku.com`.
  Tanpa client ID/secret, endpoint `/api/payments/methods` hanya mengembalikan COD sehingga
  pembayaran DOKU tidak muncul di checkout.
- Jangan mencampur kredensial production dengan `DOKU_BASE_URL` sandbox (atau sebaliknya);
  hasilnya 401 dari DOKU.
- `CORS_ORIGIN` harus origin frontend, bukan domain backend.
- Jalankan `php artisan migrate --force` sekali lewat Railway shell/command, dan set
  Notification URL DOKU ke `https://NAMA-SERVICE.up.railway.app/api/payments/doku/notification`
  pada **tiap channel** di **Settings → Payment Settings → e-Wallet → CONFIGURE**.
- Setelah mengubah env, redeploy; Laravel membaca env saat proses start.

## Catatan demo

Service gratis dapat sleep saat tidak ada traffic. Foto yang disimpan di `public/uploads` juga belum memakai object storage persisten, sehingga jangan menganggap file upload aman setelah restart/deploy. Untuk demo DOKU dan pengujian alur, setup ini sudah cukup; untuk produksi, pindahkan foto ke object storage dan gunakan database persisten.
