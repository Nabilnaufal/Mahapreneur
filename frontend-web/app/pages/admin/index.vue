<template>
  <div class="space-y-6">
    <header>
      <h1 class="text-xl font-extrabold text-strong">Ringkasan marketplace</h1>
      <p class="mt-1 text-sm text-muted">Statistik Mahapreneur dari data nyata — pengguna, toko, produk, dan transaksi.</p>
    </header>

    <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
      <DashboardStat label="Total pengguna" :value="stats.totalUsers ?? 0" :icon="Users" :hint="`${stats.totalSellers ?? 0} seller · ${stats.totalCustomers ?? 0} customer`" />
      <DashboardStat label="Total toko" :value="stats.totalStores ?? 0" :icon="Store" />
      <DashboardStat label="Total produk" :value="stats.totalProducts ?? 0" :icon="Boxes" />
      <DashboardStat label="Total pesanan" :value="stats.totalOrders ?? 0" :icon="ShoppingBag" :hint="`${stats.pendingPayment ?? 0} menunggu pembayaran`" />
      <DashboardStat label="Pesanan lunas" :value="stats.paidOrders ?? 0" :icon="CheckCircle2" />
      <DashboardStat label="Total transaksi" :value="formatRupiah(stats.revenue ?? 0)" :icon="Wallet" tone="bg-tertiary text-neutral" />
    </div>

    <div class="grid gap-5 lg:grid-cols-[1.4fr_1fr]">
      <MiniChart :data="chartData" title="Pendapatan 7 hari terakhir" />

      <section class="card p-5">
        <h2 class="text-sm font-bold text-strong">Produk per kategori</h2>
        <ul class="mt-4 space-y-3">
          <li v-for="item in categoryBreakdown" :key="item.name">
            <div class="flex items-center justify-between text-xs">
              <span class="font-semibold text-body">{{ item.name }}</span>
              <span class="text-muted">{{ item.products }} produk</span>
            </div>
            <div class="mt-1.5 h-1.5 overflow-hidden rounded-full bg-surface-muted">
              <div
                class="h-full rounded-full bg-primary dark:bg-tertiary"
                :style="{ width: `${maxCategory ? Math.round((item.products / maxCategory) * 100) : 0}%` }"
              />
            </div>
          </li>
        </ul>
        <p v-if="!categoryBreakdown.length" class="mt-3 text-xs text-muted">Belum ada kategori.</p>
      </section>
    </div>

    <div class="grid gap-5 lg:grid-cols-2">
      <section class="card overflow-hidden">
        <header class="flex items-center justify-between border-b border-line px-5 py-4">
          <h2 class="text-sm font-bold text-strong">Pesanan terbaru</h2>
          <NuxtLink to="/admin/orders" class="text-xs font-semibold text-muted hover:text-strong">Lihat semua</NuxtLink>
        </header>
        <div class="divide-y divide-[color:var(--line)]">
          <NuxtLink
            v-for="order in recentOrders"
            :key="order.id"
            :to="`/admin/orders?q=${order.orderNumber}`"
            class="flex flex-wrap items-center gap-3 px-5 py-3.5 transition hover:bg-surface-muted"
          >
            <div class="min-w-0 flex-1">
              <p class="font-mono text-xs font-bold text-strong">{{ order.orderNumber }}</p>
              <p class="text-[11px] text-muted">{{ order.customer?.name }} → {{ order.store?.name }}</p>
            </div>
            <StatusBadge :status="order.status" kind="order" />
            <span class="text-sm font-bold text-strong">{{ formatRupiah(order.total) }}</span>
          </NuxtLink>
          <p v-if="!recentOrders.length" class="px-5 py-8 text-center text-sm text-muted">Belum ada pesanan.</p>
        </div>
      </section>

      <section class="card overflow-hidden">
        <header class="flex items-center justify-between border-b border-line px-5 py-4">
          <h2 class="text-sm font-bold text-strong">Toko paling aktif</h2>
          <NuxtLink to="/admin/stores" class="text-xs font-semibold text-muted hover:text-strong">Lihat semua</NuxtLink>
        </header>
        <div class="divide-y divide-[color:var(--line)]">
          <div v-for="store in topStores" :key="store.id" class="flex items-center gap-3 px-5 py-3.5">
            <span class="grid size-9 shrink-0 place-items-center overflow-hidden rounded-xl bg-surface-muted">
              <img v-if="store.logo" :src="store.logo" :alt="store.name" class="size-full object-cover" />
              <Store v-else class="size-4 text-muted" />
            </span>
            <div class="min-w-0 flex-1">
              <p class="truncate text-xs font-bold text-strong">{{ store.name }}</p>
              <p class="text-[11px] text-muted">{{ store._count?.products ?? 0 }} produk · {{ store.orderCount ?? 0 }} pesanan</p>
            </div>
            <NuxtLink :to="`/store/${store.slug}`" target="_blank" class="btn btn-ghost !px-2 text-xs" aria-label="Lihat toko">
              <ExternalLink class="size-3.5" />
            </NuxtLink>
          </div>
          <p v-if="!topStores.length" class="px-5 py-8 text-center text-sm text-muted">Belum ada toko.</p>
        </div>
      </section>
    </div>
  </div>
</template>

<script setup lang="ts">
import { Boxes, CheckCircle2, ExternalLink, ShoppingBag, Store, Users, Wallet } from 'lucide-vue-next';
import type { Order, Store as StoreType } from '~/types';

definePageMeta({ layout: 'dashboard', requiresAuth: true, roles: ['SUPERADMIN'] });

const api = useApi();

interface AdminDashboard {
  stats: {
    totalUsers: number; totalSellers: number; totalCustomers: number; totalStores: number;
    totalProducts: number; totalOrders: number; paidOrders: number; pendingPayment: number; revenue: number;
  };
  chart: { date: string; label: string; orders: number; revenue: number }[];
  categoryBreakdown: { name: string; products: number }[];
  recentOrders: Order[];
  topStores: StoreType[];
}

const { data } = await useAsyncData('admin-dashboard', () => api.get<AdminDashboard>('/admin/dashboard'));

const stats = computed(() => data.value?.stats ?? ({} as AdminDashboard['stats']));
const recentOrders = computed(() => data.value?.recentOrders ?? []);
const topStores = computed(() => data.value?.topStores ?? []);
const categoryBreakdown = computed(() => data.value?.categoryBreakdown ?? []);
const maxCategory = computed(() => Math.max(...categoryBreakdown.value.map((item) => item.products), 1));
const chartData = computed(() => (data.value?.chart ?? []).map((point) => ({ date: point.date, label: point.label, value: point.revenue })));

useSeoMeta({ title: 'Dashboard Admin' });
</script>