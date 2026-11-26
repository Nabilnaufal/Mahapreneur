<template>
  <footer class="mt-20 border-t border-line bg-surface">
    <div class="page-container grid gap-10 py-14 md:grid-cols-2 lg:grid-cols-4">
      <div class="lg:col-span-1">
        <div class="flex items-center gap-2">
          <span class="grid size-9 place-items-center rounded-xl bg-primary"><img src="/logo-mark.png" alt="" class="h-4 w-auto" /></span>
          <span class="text-lg font-extrabold text-strong">Mahapreneur</span>
        </div>
        <p class="mt-4 text-sm leading-relaxed text-muted">
          Marketplace bisnis mahasiswa. Setiap pesanan diserahkan langsung oleh seller — tanpa ekspedisi, tanpa ongkos kirim.
        </p>
        <p class="mt-4 inline-flex items-center gap-2 rounded-full bg-tertiary px-3 py-1 text-[11px] font-bold uppercase tracking-wide text-neutral">
          <Bike class="size-3.5" /> Diantar sendiri oleh seller
        </p>
      </div>

      <div>
        <h3 class="text-sm font-bold text-strong">Jelajahi</h3>
        <ul class="mt-4 space-y-2.5 text-sm text-muted">
          <li><NuxtLink to="/catalog" class="hover:text-strong">Semua produk</NuxtLink></li>
          <li><NuxtLink to="/categories" class="hover:text-strong">Kategori</NuxtLink></li>
          <li><NuxtLink to="/stores" class="hover:text-strong">Toko mahasiswa</NuxtLink></li>
          <li><NuxtLink to="/catalog?type=SERVICE" class="hover:text-strong">Jasa mahasiswa</NuxtLink></li>
        </ul>
      </div>

      <div>
        <h3 class="text-sm font-bold text-strong">Untuk Seller</h3>
        <ul class="mt-4 space-y-2.5 text-sm text-muted">
          <li><NuxtLink to="/auth/register?role=SELLER" class="hover:text-strong">Buka toko</NuxtLink></li>
          <li><NuxtLink to="/seller/products" class="hover:text-strong">Kelola produk</NuxtLink></li>
          <li><NuxtLink to="/seller/orders" class="hover:text-strong">Pesanan masuk</NuxtLink></li>
          <li><NuxtLink to="/auth/register" class="hover:text-strong">Daftar akun</NuxtLink></li>
        </ul>
      </div>

      <div>
        <h3 class="text-sm font-bold text-strong">Bantuan</h3>
        <ul class="mt-4 space-y-2.5 text-sm text-muted">
          <li><NuxtLink to="/customer/orders" class="hover:text-strong">Lacak pesanan</NuxtLink></li>
          <li><NuxtLink to="/customer/profile" class="hover:text-strong">Pengaturan akun</NuxtLink></li>
          <li><span class="inline-flex items-center gap-1.5"><ShieldCheck class="size-4" /> Pembayaran lewat DOKU</span></li>
          <li><span class="inline-flex items-center gap-1.5"><Wallet class="size-4" /> OVO, DANA, ShopeePay, dan COD</span></li>
        </ul>
      </div>
    </div>

    <div class="border-t border-line py-6">
      <div class="page-container flex flex-col items-center justify-between gap-3 text-xs text-muted md:flex-row">
        <p>© {{ new Date().getFullYear() }} Mahapreneur. Dibuat untuk praktik mata kuliah E-Business.</p>
        <p class="flex items-center gap-4">
          <span>Ketentuan</span>
          <span>Privasi</span>
          <span>Keamanan</span>
        </p>
      </div>
    </div>
  </footer>
</template>

<script setup lang="ts">
import { Bike, ShieldCheck, Wallet } from 'lucide-vue-next';
</script>
