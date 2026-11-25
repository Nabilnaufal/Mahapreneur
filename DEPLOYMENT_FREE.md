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
DOKU_EWALLET_CHANNEL=SHOPEEPAY
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

## Catatan demo

Service gratis dapat sleep saat tidak ada traffic. Foto yang disimpan di `public/uploads` juga belum memakai object storage persisten, sehingga jangan menganggap file upload aman setelah restart/deploy. Untuk demo DOKU dan pengujian alur, setup ini sudah cukup; untuk produksi, pindahkan foto ke object storage dan gunakan database persisten.
