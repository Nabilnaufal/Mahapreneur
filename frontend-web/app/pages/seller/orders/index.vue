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
      <div class="hidden grid-cols-[1fr_150px_160px_120px_120px] gap-3 border-b border-line px-5 py-3 text-[11px] font-bold uppercase tracking-wide text-muted lg:grid">
        <span>Pesanan</span><span>Penerima</span><span>Status</span><span>Pembayaran</span><span class="text-right">Total</span>
      </div>
      <div class="divide-y divide-[color:var(--line)]">
        <article
          v-for="order in orders"
          :key="order.id"
          class="grid gap-3 px-5 py-4 transition hover:bg-surface-muted lg:grid-cols-[1fr_150px_190px_120px_120px] lg:items-center"
        >
          <div class="min-w-0">
            <NuxtLink :to="`/seller/orders/${order.id}`" class="font-mono text-xs font-bold text-strong hover:underline">{{ order.orderNumber }}</NuxtLink>
            <p class="text-[11px] text-muted">{{ formatDate(order.createdAt, true) }} · {{ order.items.length }} item</p>
          </div>
          <div class="min-w-0 text-xs">
            <p class="truncate font-semibold text-body">{{ order.recipientName }}</p>
            <p class="truncate text-muted">{{ order.campus || order.address }}</p>
          </div>
          <div class="min-w-0" @click.stop>
            <label class="mb-1 block text-[10px] font-semibold uppercase tracking-wide text-muted lg:hidden">Perbarui status</label>
            <select
              v-if="statusOptions(order).length > 1"
              :value="order.status"
              :disabled="statusUpdating === order.id"
              class="input !rounded-full !py-2 !pl-3 !pr-8 text-xs font-bold uppercase tracking-wide"
              aria-label="Perbarui status pesanan"
              @click.stop
              @change.stop="changeOrderStatus(order, $event)"
            >
              <option v-for="option in statusOptions(order)" :key="option" :value="option">{{ orderStatusLabel(option) }}</option>
            </select>
            <StatusBadge v-else :status="order.status" kind="order" />
          </div>
          <StatusBadge :status="order.paymentStatus" kind="payment" />
          <div class="flex items-center justify-between gap-2 lg:justify-end">
            <span class="text-sm font-extrabold text-strong">{{ formatRupiah(order.total) }}</span>
            <ChevronRight class="size-4 text-muted" />
          </div>
        </article>
      </div>
    </div>

    <EmptyState v-else title="Belum ada pesanan" description="Pesanan dari customer akan muncul di sini beserta info penerima dan status pembayarannya." />

    <PaginationBar :meta="meta" @change="changePage" />
  </div>
</template>

<script setup lang="ts">
import { ChevronRight, Search } from 'lucide-vue-next';
import type { Order, OrderStatus, PaginationMeta } from '~/types';

definePageMeta({ layout: 'dashboard', requiresAuth: true, roles: ['SELLER'] });

const api = useApi();
const ui = useUiStore();

const keyword = ref('');
const status = ref('');
const page = ref(1);
const statusUpdating = ref<number | null>(null);

const STATUS_FLOW: Partial<Record<OrderStatus, OrderStatus[]>> = {
  CONFIRMED: ['PROCESSING'],
  PROCESSING: ['READY'],
  READY: ['ON_DELIVERY'],
  ON_DELIVERY: ['COMPLETED'],
};

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

function statusOptions(order: Order): OrderStatus[] {
  return [order.status, ...(STATUS_FLOW[order.status] ?? [])];
}

async function changeOrderStatus(order: Order, event: Event) {
  const nextStatus = (event.target as HTMLSelectElement).value as OrderStatus;
  if (!nextStatus || nextStatus === order.status) return;

  statusUpdating.value = order.id;
  try {
    await api.patch(`/seller/orders/${order.id}/status`, { status: nextStatus });
    ui.toast(`Status diperbarui: ${orderStatusLabel(nextStatus)}`, 'success');
    await refresh();
  } catch (error) {
    ui.toast(apiError(error), 'error');
    await refresh();
  } finally {
    statusUpdating.value = null;
  }
}

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

useSeoMeta({ title: 'Pesanan Masuk' });
</script>
