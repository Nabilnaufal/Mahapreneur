<template>
  <div class="space-y-6">
    <header class="flex flex-wrap items-end justify-between gap-3">
      <div>
        <h1 class="text-xl font-extrabold text-strong">Analytics toko</h1>
        <p class="mt-1 text-sm text-muted">Pantau produk yang paling menarik perhatian dan menghasilkan penjualan.</p>
      </div>
      <button class="btn btn-outline !px-3 text-xs" :disabled="refreshing" @click="refreshAnalytics">
        <Loader2 v-if="refreshing" class="size-3.5 animate-spin" />
        <RefreshCw v-else class="size-3.5" />
        {{ refreshing ? 'Memuat…' : 'Segarkan' }}
      </button>
    </header>

    <p v-if="error" class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-xs text-rose-700 dark:border-rose-500/30 dark:bg-rose-500/10 dark:text-rose-300">
      Data analytics belum berhasil dimuat. Tekan “Segarkan” setelah backend aktif.
    </p>

    <template v-if="auth.hasStore">
      <div class="grid grid-cols-2 gap-3 lg:grid-cols-3 xl:grid-cols-5">
        <DashboardStat label="Total produk" :value="stats.productCount ?? 0" :icon="Boxes" hint="Produk aktif dan nonaktif" />
        <DashboardStat label="Total dilihat" :value="stats.totalViews ?? 0" :icon="Eye" hint="Pembukaan detail produk" />
        <DashboardStat label="Checkout berhasil" :value="stats.totalOrders ?? 0" :icon="ShoppingBag" hint="Pesanan terbayar" />
        <DashboardStat label="Rasio konversi" :value="`${stats.conversionRate ?? 0}%`" :icon="ChartNoAxesCombined" hint="Checkout dibanding dilihat" />
        <DashboardStat label="Pendapatan" :value="formatRupiah(stats.revenue ?? 0)" :icon="Wallet" tone="bg-tertiary text-neutral" />
      </div>

      <div class="grid gap-5 lg:grid-cols-2">
        <MiniChart :data="viewsChart" title="Produk dilihat" :range-label="`${data?.periodDays ?? 30} hari terakhir`" value-format="number" />
        <MiniChart :data="ordersChart" title="Checkout berhasil" :range-label="`${data?.periodDays ?? 30} hari terakhir`" value-format="number" />
      </div>

      <div class="grid gap-5 lg:grid-cols-2">
        <section class="card overflow-hidden">
          <header class="flex items-center justify-between border-b border-line px-5 py-4">
            <div>
              <h2 class="text-sm font-bold text-strong">Produk paling laku</h2>
              <p class="mt-1 text-xs text-muted">Diurutkan berdasarkan jumlah terjual.</p>
            </div>
            <TrendingUp class="size-5 text-tertiary-dark dark:text-tertiary" />
          </header>
          <div v-if="topSold.length" class="divide-y divide-[color:var(--line)]">
            <NuxtLink v-for="(product, index) in topSold" :key="product.id" :to="`/seller/products/${product.id}`" class="flex items-center gap-3 px-5 py-3.5 transition hover:bg-surface-muted">
              <span class="grid size-7 shrink-0 place-items-center rounded-lg bg-surface-muted text-xs font-extrabold text-muted">{{ index + 1 }}</span>
              <span class="size-11 shrink-0 overflow-hidden rounded-xl bg-surface-muted">
                <img v-if="product.image" :src="product.image" :alt="product.name" class="size-full object-cover" />
              </span>
              <span class="min-w-0 flex-1">
                <span class="block line-clamp-1 text-sm font-semibold text-strong">{{ product.name }}</span>
                <span class="mt-1 block text-xs text-muted">{{ product.sold }} terjual · {{ formatRupiah(product.price) }}</span>
              </span>
              <ChevronRight class="size-4 shrink-0 text-muted" />
            </NuxtLink>
          </div>
          <p v-else class="px-5 py-10 text-center text-sm text-muted">Belum ada penjualan tercatat.</p>
        </section>

        <section class="card overflow-hidden">
          <header class="flex items-center justify-between border-b border-line px-5 py-4">
            <div>
              <h2 class="text-sm font-bold text-strong">Produk paling banyak dilihat</h2>
              <p class="mt-1 text-xs text-muted">Berdasarkan kunjungan ke halaman detail.</p>
            </div>
            <Eye class="size-5 text-tertiary-dark dark:text-tertiary" />
          </header>
          <div v-if="topViewed.length" class="divide-y divide-[color:var(--line)]">
            <NuxtLink v-for="(item, index) in topViewed" :key="item.product.id" :to="`/seller/products/${item.product.id}`" class="flex items-center gap-3 px-5 py-3.5 transition hover:bg-surface-muted">
              <span class="grid size-7 shrink-0 place-items-center rounded-lg bg-surface-muted text-xs font-extrabold text-muted">{{ index + 1 }}</span>
              <span class="size-11 shrink-0 overflow-hidden rounded-xl bg-surface-muted">
                <img v-if="item.product.image" :src="item.product.image" :alt="item.product.name" class="size-full object-cover" />
              </span>
              <span class="min-w-0 flex-1">
                <span class="block line-clamp-1 text-sm font-semibold text-strong">{{ item.product.name }}</span>
                <span class="mt-1 block text-xs text-muted">{{ item.views.toLocaleString('id-ID') }} kali dilihat · {{ item.product.sold }} terjual</span>
              </span>
              <ChevronRight class="size-4 shrink-0 text-muted" />
            </NuxtLink>
          </div>
          <p v-else class="px-5 py-10 text-center text-sm text-muted">Belum ada kunjungan detail produk.</p>
        </section>
      </div>

      <section class="card flex items-start gap-3 p-5">
        <span class="grid size-9 shrink-0 place-items-center rounded-xl bg-tertiary text-neutral"><Info class="size-4.5" /></span>
        <div>
          <h2 class="text-sm font-bold text-strong">Cara membaca rasio konversi</h2>
          <p class="mt-1 text-xs leading-relaxed text-muted">Rasio konversi dihitung dari checkout berhasil dibagi total pembukaan detail produk, lalu dikali 100. Angka ini adalah gambaran minat pengunjung dan bukan jumlah orang unik.</p>
        </div>
      </section>
    </template>

    <section v-else class="card overflow-hidden">
      <div class="bg-primary p-8 text-white">
        <p class="badge bg-tertiary text-neutral">Langkah pertama</p>
        <h1 class="mt-3 text-2xl font-extrabold">Buka tokomu untuk melihat analytics</h1>
        <p class="mt-2 max-w-xl text-sm leading-relaxed text-white/70">Setelah toko dibuat dan produk mulai dilihat atau dipesan, performanya akan tampil di halaman ini.</p>
        <NuxtLink to="/seller/store" class="btn btn-tertiary mt-5">Buat toko sekarang</NuxtLink>
      </div>
    </section>
  </div>
</template>

<script setup lang="ts">
import {
  BarChart3 as ChartNoAxesCombined, Boxes, ChevronRight, Eye, Info, Loader2, RefreshCw, ShoppingBag, TrendingUp, Wallet,
} from 'lucide-vue-next';
import type { Product } from '~/types';

definePageMeta({ layout: 'dashboard', requiresAuth: true, roles: ['SELLER'] });

interface AnalyticsData {
  periodDays: number;
  stats: { productCount: number; totalViews: number; totalOrders: number; conversionRate: number; revenue: number };
  chart: { date: string; label: string; views: number; orders: number }[];
  topSold: Product[];
  topViewed: { views: number; product: Product }[];
}

const api = useApi();
const auth = useAuthStore();
const { data, error, refresh } = await useAsyncData(
  'seller-analytics',
  () => (auth.hasStore ? api.get<AnalyticsData>('/seller/analytics') : Promise.resolve(null)),
  { watch: [() => auth.hasStore] },
);

const refreshing = ref(false);
const stats = computed(() => data.value?.stats ?? ({} as AnalyticsData['stats']));
const topSold = computed(() => data.value?.topSold ?? []);
const topViewed = computed(() => data.value?.topViewed ?? []);
const viewsChart = computed(() => (data.value?.chart ?? []).map((point) => ({ date: point.date, label: point.label, value: point.views })));
const ordersChart = computed(() => (data.value?.chart ?? []).map((point) => ({ date: point.date, label: point.label, value: point.orders })));

async function refreshAnalytics() {
  if (refreshing.value || !auth.hasStore) return;
  refreshing.value = true;
  try {
    await refresh();
  } finally {
    refreshing.value = false;
  }
}

useSeoMeta({ title: 'Analytics Toko' });
</script>
