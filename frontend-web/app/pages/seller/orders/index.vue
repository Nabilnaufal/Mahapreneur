<template>
  <div class="space-y-5">
    <header>
      <h1 class="text-xl font-extrabold text-strong">Pesanan masuk</h1>
      <p class="mt-1 text-sm text-muted">{{ meta.total }} pesanan untuk tokomu. Kamu hanya melihat pesanan milik tokomu sendiri.</p>
    </header>

    <div class="card flex flex-wrap items-center gap-3 p-4">
      <label class="flex min-w-56 flex-1 items-center gap-2 rounded-full border border-line bg-surface-muted px-4 py-2">
        <Search class="size-4 text-muted" />
        <input v-model="keyword" type="search" placeholder="Cari nomor pesanan atau nama penerima…" class="w-full bg-transparent text-sm text-strong outline-none" @keyup.enter="apply" />
      </label>
      <button class="btn btn-outline text-xs" @click="apply">Cari</button>
      <button class="btn btn-primary text-xs" :disabled="exporting" @click="exportCsv">
        <Loader2 v-if="exporting" class="size-4 animate-spin" />
        <Download v-else class="size-4" />
        Export CSV
      </button>
    </div>

    <div class="flex gap-2 overflow-x-auto pb-1 no-scrollbar">
      <button
        v-for="tab in tabs"
        :key="tab.value"
        class="badge shrink-0 border px-3.5 py-2"
        :class="status === tab.value ? 'border-primary bg-primary text-white' : 'border-line text-body'"
        @click="setStatus(tab.value)"
      >
        {{ tab.label }}
      </button>
    </div>

    <div v-if="orders.length" class="card overflow-hidden">
      <div class="hidden grid-cols-[1fr_150px_170px_140px_120px] gap-3 border-b border-line px-5 py-3 text-[11px] font-bold uppercase tracking-wide text-muted lg:grid">
        <span>Pesanan</span><span>Penerima</span><span>Status</span><span>Pembayaran</span><span class="text-right">Total</span>
      </div>
      <div class="divide-y divide-[color:var(--line)]">
        <NuxtLink
          v-for="order in orders"
          :key="order.id"
          :to="'/seller/orders/' + order.id"
          class="grid gap-3 px-5 py-4 transition hover:bg-surface-muted lg:grid-cols-[1fr_150px_170px_140px_120px] lg:items-center"
        >
          <div class="min-w-0">
            <p class="font-mono text-xs font-bold text-strong">{{ order.orderNumber }}</p>
            <p class="text-[11px] text-muted">{{ formatDate(order.createdAt, true) }} · {{ order.items.length }} item</p>
          </div>
          <div class="min-w-0 text-xs">
            <p class="truncate font-semibold text-body">{{ order.recipientName }}</p>
            <p class="truncate text-muted">{{ order.campus || order.address }}</p>
          </div>
          <StatusBadge :status="order.status" kind="order" />
          <StatusBadge :status="order.paymentStatus" kind="payment" />
          <div class="flex items-center justify-between gap-2 lg:justify-end">
            <span class="text-sm font-extrabold text-strong">{{ formatRupiah(order.total) }}</span>
            <ChevronRight class="size-4 text-muted" />
          </div>
        </NuxtLink>
      </div>
    </div>

    <EmptyState v-else title="Belum ada pesanan" description="Pesanan dari customer akan muncul di sini beserta info penerima dan status pembayarannya." />

    <PaginationBar :meta="meta" @change="changePage" />
  </div>
</template>

<script setup lang="ts">
import { ChevronRight, Download, Loader2, Search } from 'lucide-vue-next';
import type { Order, PaginationMeta } from '~/types';

definePageMeta({ layout: 'dashboard', requiresAuth: true, roles: ['SELLER'] });

const api = useApi();

const keyword = ref('');
const status = ref('');
const page = ref(1);
const exporting = ref(false);
const ui = useUiStore();

const tabs = [
  { label: 'Semua', value: '' },
  { label: 'Menunggu bayar', value: 'WAITING_PAYMENT' },
  { label: 'Perlu diproses', value: 'CONFIRMED' },
  { label: 'Diproses', value: 'PROCESSING' },
  { label: 'Siap', value: 'READY' },
  { label: 'Diantar', value: 'ON_DELIVERY' },
  { label: 'Selesai', value: 'COMPLETED' },
  { label: 'Dibatalkan', value: 'CANCELLED' },
];

const query = computed(() => ({
  q: keyword.value || undefined,
  status: status.value || undefined,
  page: page.value,
  limit: 10,
}));

const { data, refresh } = await useAsyncData('seller-orders', () => api.get<{ orders: Order[]; meta: PaginationMeta }>('/seller/orders', query.value), {
  watch: [query],
});

const orders = computed(() => data.value?.orders ?? []);
const meta = computed(() => data.value?.meta ?? { total: 0, page: 1, limit: 10, totalPages: 1 });

function apply() {
  page.value = 1;
  refresh();
}

function setStatus(value: string) {
  status.value = value;
  page.value = 1;
}

function changePage(next: number) {
  page.value = next;
}

async function exportCsv() {
  exporting.value = true;
  try {
    const params = new URLSearchParams();
    if (keyword.value.trim()) params.set('q', keyword.value.trim());
    if (status.value) params.set('status', status.value);

    const response = await fetch(`${api.baseURL}/seller/orders/export?${params.toString()}`, {
      credentials: 'include',
    });
    if (!response.ok) {
      const body = await response.json().catch(() => null);
      throw new Error(body?.message || 'Laporan gagal diunduh.');
    }

    const blob = await response.blob();
    const url = URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.download = `laporan-penjualan-${new Date().toISOString().slice(0, 10)}.csv`;
    document.body.appendChild(link);
    link.click();
    link.remove();
    URL.revokeObjectURL(url);
    ui.toast('Laporan penjualan berhasil diunduh.', 'success');
  } catch (error) {
    ui.toast(apiError(error), 'error');
  } finally {
    exporting.value = false;
  }
}

useSeoMeta({ title: 'Pesanan Masuk' });
</script>
