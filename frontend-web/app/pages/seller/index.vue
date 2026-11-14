<template>
  <div class="space-y-6">
    <!-- Belum punya toko -->
    <section v-if="!auth.hasStore" class="card overflow-hidden">
      <div class="bg-primary p-8 text-white">
        <p class="badge bg-tertiary text-neutral">Langkah pertama</p>
        <h1 class="mt-3 text-2xl font-extrabold">Buka tokomu di Mahapreneur</h1>
        <p class="mt-2 max-w-xl text-sm leading-relaxed text-white/70">
          Buat satu toko, unggah produk atau jasa, lalu terima pesanan dari mahasiswa lain. Pesanan kamu antarkan sendiri — tanpa ekspedisi.
        </p>
        <NuxtLink to="/seller/store" class="btn btn-tertiary mt-5">
          <Store class="size-4" /> Buat toko sekarang
        </NuxtLink>
      </div>
    </section>

    <template v-else>
      <header class="flex flex-wrap items-end justify-between gap-3">
        <div>
          <h1 class="text-xl font-extrabold text-strong">Ringkasan toko</h1>
          <p class="mt-1 text-sm text-muted">{{ auth.user?.store?.name }} · data diperbarui otomatis dari pesanan nyata.</p>
        </div>
        <div class="flex gap-2">
          <NuxtLink to="/seller/products/new" class="btn btn-primary text-xs"><Plus class="size-4" /> Tambah produk</NuxtLink>
          <NuxtLink to="/seller/orders" class="btn btn-outline text-xs">Pesanan masuk</NuxtLink>
        </div>
      </header>

      <div class="grid grid-cols-2 gap-3 lg:grid-cols-3 xl:grid-cols-6">
        <DashboardStat label="Total produk" :value="stats.productCount ?? 0" :icon="Boxes" />
        <DashboardStat label="Pesanan masuk" :value="stats.totalOrders ?? 0" :icon="ShoppingBag" />
        <DashboardStat label="Menunggu bayar" :value="stats.waitingPayment ?? 0" :icon="Clock" />
        <DashboardStat label="Perlu diproses" :value="stats.needProcessing ?? 0" :icon="PackageCheck" />
        <DashboardStat label="Selesai" :value="stats.completed ?? 0" :icon="CheckCircle2" />
        <DashboardStat label="Pendapatan" :value="formatRupiah(stats.revenue ?? 0)" :icon="Wallet" tone="bg-tertiary text-neutral" />
      </div>

      <div class="grid gap-5 lg:grid-cols-[1.4fr_1fr]">
        <MiniChart :data="chartData" title="Pendapatan 7 hari terakhir" />

        <section class="card p-5">
          <h2 class="text-sm font-bold text-strong">Produk terlaris</h2>
          <ul v-if="topProducts.length" class="mt-4 space-y-3">
            <li v-for="product in topProducts" :key="product.id" class="flex items-center gap-3">
              <span class="size-10 shrink-0 overflow-hidden rounded-xl bg-surface-muted">
                <img v-if="product.image" :src="product.image" :alt="product.name" class="size-full object-cover" />
              </span>
              <div class="min-w-0 flex-1">
                <p class="line-clamp-1 text-xs font-semibold text-strong">{{ product.name }}</p>
                <p class="text-[11px] text-muted">{{ product.sold }} terjual · stok {{ product.stock }}</p>
              </div>
              <span class="text-xs font-bold text-strong">{{ formatRupiah(product.price) }}</span>
            </li>
          </ul>
          <p v-else class="mt-4 text-xs text-muted">Belum ada penjualan tercatat.</p>
        </section>
      </div>

      <section class="card overflow-hidden">
        <header class="flex items-center justify-between border-b border-line px-5 py-4">
          <h2 class="text-sm font-bold text-strong">Pesanan terbaru</h2>
          <NuxtLink to="/seller/orders" class="text-xs font-semibold text-muted hover:text-strong">Lihat semua</NuxtLink>
        </header>

        <div v-if="recentOrders.length" class="divide-y divide-[color:var(--line)]">
          <NuxtLink
            v-for="order in recentOrders"
            :key="order.id"
            :to="`/seller/orders/${order.id}`"
            class="flex flex-wrap items-center gap-3 px-5 py-4 transition hover:bg-surface-muted"
          >
            <div class="min-w-0 flex-1">
              <p class="font-mono text-xs font-semibold text-strong">{{ order.orderNumber }}</p>
              <p class="text-[11px] text-muted">{{ order.recipientName }} · {{ formatDate(order.createdAt, true) }}</p>
            </div>
            <StatusBadge :status="order.status" kind="order" />
            <StatusBadge :status="order.paymentStatus" kind="payment" />
            <span class="text-sm font-bold text-strong">{{ formatRupiah(order.total) }}</span>
            <ChevronRight class="size-4 text-muted" />
          </NuxtLink>
        </div>
        <p v-else class="px-5 py-10 text-center text-sm text-muted">Belum ada pesanan masuk.</p>
      </section>
    </template>
  </div>
</template>

<script setup lang="ts">
import {
  Boxes, CheckCircle2, ChevronRight, Clock, PackageCheck, Plus, ShoppingBag, Store, Wallet,
} from 'lucide-vue-next';
import type { Order, Product } from '~/types';

definePageMeta({ layout: 'dashboard', requiresAuth: true, roles: ['SELLER'] });

const api = useApi();
const auth = useAuthStore();

interface DashboardData {
  stats: { productCount: number; totalOrders: number; waitingPayment: number; needProcessing: number; completed: number; revenue: number };
  chart: { date: string; label: string; revenue: number }[];
  recentOrders: Order[];
  topProducts: Product[];
}

const { data } = await useAsyncData(
  'seller-dashboard',
  () => (auth.hasStore ? api.get<DashboardData>('/seller/dashboard') : Promise.resolve(null)),
  { watch: [() => auth.hasStore] },
);

const stats = computed(() => data.value?.stats ?? ({} as DashboardData['stats']));
const recentOrders = computed(() => data.value?.recentOrders ?? []);
const topProducts = computed(() => data.value?.topProducts ?? []);
const chartData = computed(() => (data.value?.chart ?? []).map((point) => ({ date: point.date, label: point.label, value: point.revenue })));

useSeoMeta({ title: 'Dashboard Seller' });
</script>