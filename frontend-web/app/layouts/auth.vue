<template>
  <div class="grid min-h-screen lg:grid-cols-2">
    <div class="relative hidden flex-col justify-between bg-primary p-12 lg:flex">
      <NuxtLink to="/" class="flex items-center gap-2.5 text-white">
        <img src="/logo-mark.png" alt="" class="h-6 w-auto" />
        <span class="text-xl font-extrabold">Mahapreneur</span>
      </NuxtLink>

      <div class="max-w-md">
        <p class="badge bg-tertiary text-neutral">Marketplace Mahasiswa</p>
        <h2 class="mt-5 text-4xl font-extrabold leading-tight text-white">
          Satu kampus, ribuan peluang jual beli.
        </h2>
        <p class="mt-4 text-sm leading-relaxed text-white/70">
          Buka toko, tawarkan produk atau jasa, dan antar sendiri pesananmu ke kelas, kos, atau rumah pembeli. Tanpa ekspedisi, tanpa ongkos kirim.
        </p>
      </div>

      <ul class="space-y-3 text-sm text-white/80">
        <li v-for="point in points" :key="point" class="flex items-center gap-3">
          <CheckCircle2 class="size-4 shrink-0 text-tertiary" />
          {{ point }}
        </li>
      </ul>
    </div>

    <div class="flex items-center justify-center px-5 py-10 sm:px-10">
      <div class="w-full max-w-md">
        <NuxtLink to="/" class="mb-8 flex items-center gap-2 lg:hidden">
          <span class="grid size-9 place-items-center rounded-xl bg-primary"><img src="/logo-mark.png" alt="" class="h-4 w-auto" /></span>
          <span class="text-lg font-extrabold text-strong">Mahapreneur</span>
        </NuxtLink>
        <slot />
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { CheckCircle2 } from 'lucide-vue-next';

const points = [
  'Verifikasi email untuk menjaga keamanan akun',
  'Pembayaran QRIS, Virtual Account, atau COD',
  'Seller mengantarkan pesanan langsung ke penerima',
];
</script>