<template>
  <div class="relative min-h-screen lg:grid lg:grid-cols-[1.05fr_1fr]">
    <aside class="relative hidden overflow-hidden bg-primary p-12 lg:flex lg:flex-col">
      <div class="pointer-events-none absolute inset-0" aria-hidden="true">
        <div class="absolute -right-32 -top-32 size-[28rem] rounded-full bg-tertiary/15 blur-[110px]" />
        <div class="absolute -bottom-24 left-1/3 size-80 rounded-full bg-primary-soft blur-[100px]" />
        <div class="absolute inset-0 opacity-[0.06] auth-grid" />
      </div>

      <NuxtLink to="/" class="relative flex items-center gap-2.5 text-white">
        <img src="/logo-mark.png" alt="" class="h-7 w-auto" />
        <span class="text-xl font-extrabold">Mahapreneur</span>
      </NuxtLink>

      <div class="relative my-auto max-w-lg py-12">
        <p class="badge bg-tertiary text-neutral">Marketplace Mahasiswa</p>
        <h2 class="mt-5 text-4xl font-extrabold leading-[1.1] text-white">
          Satu kampus,<br />ribuan peluang <span class="text-tertiary">jual beli.</span>
        </h2>
        <p class="mt-4 max-w-md text-sm leading-relaxed text-white/65">
          Buka toko, tawarkan produk atau jasa, dan antar sendiri pesananmu ke kelas, kos, atau rumah pembeli — tanpa ekspedisi, tanpa ongkir.
        </p>

        <ul class="mt-9 grid gap-3">
          <li
            v-for="item in features"
            :key="item.title"
            class="flex items-start gap-3 rounded-2xl border border-white/10 bg-white/[0.06] p-4 backdrop-blur-sm"
          >
            <span class="grid size-9 shrink-0 place-items-center rounded-xl bg-tertiary/15 text-tertiary">
              <component :is="item.icon" class="size-4" />
            </span>
            <div>
              <p class="text-sm font-bold text-white">{{ item.title }}</p>
              <p class="mt-0.5 text-xs leading-snug text-white/55">{{ item.desc }}</p>
            </div>
          </li>
        </ul>
      </div>

      <p class="relative text-xs text-white/35">© 2026 Mahapreneur · Dibuat untuk mahasiswa.</p>
    </aside>

    <main class="relative flex min-h-screen items-center justify-center px-5 py-10 sm:px-8">
      <div class="pointer-events-none absolute inset-x-0 top-0 h-56 bg-gradient-to-b from-tertiary/15 to-transparent lg:hidden" aria-hidden="true" />
      <div class="relative w-full max-w-md">
        <NuxtLink to="/" class="mb-7 flex items-center gap-2 lg:hidden">
          <span class="grid size-9 place-items-center rounded-xl bg-primary">
            <img src="/logo-mark.png" alt="" class="h-4 w-auto" />
          </span>
          <span class="text-lg font-extrabold text-strong">Mahapreneur</span>
        </NuxtLink>

        <div class="card rounded-[1.75rem] p-6 sm:p-8">
          <slot />
        </div>

        <p class="mt-6 flex items-center justify-center gap-1.5 text-[11px] text-muted">
          <ShieldCheck class="size-3.5" /> Transaksi aman · Pembayaran terverifikasi
        </p>
      </div>
    </main>
  </div>
</template>

<script setup lang="ts">
import { BadgeCheck, ShieldCheck, Store, Wallet } from 'lucide-vue-next';

const features = [
  { icon: Store, title: 'Toko milik sendiri', desc: 'Kelola produk, stok, dan pesanan dari satu dashboard.' },
  { icon: Wallet, title: 'Pembayaran fleksibel', desc: 'QRIS, Virtual Account, e-wallet, atau COD saat bertemu.' },
  { icon: BadgeCheck, title: 'Antar langsung', desc: 'Seller mengantar pesanan sendiri ke pembeli di sekitar kampus.' },
];
</script>

<style scoped>
.auth-grid {
  background-image:
    linear-gradient(rgba(255, 255, 255, 0.6) 1px, transparent 1px),
    linear-gradient(90deg, rgba(255, 255, 255, 0.6) 1px, transparent 1px);
  background-size: 44px 44px;
}
</style>
