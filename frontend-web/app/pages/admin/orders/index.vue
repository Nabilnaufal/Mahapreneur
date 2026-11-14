<template>
  <div class="space-y-5">
    <header>
      <h1 class="text-xl font-extrabold text-strong">Semua pesanan</h1>
      <p class="mt-1 text-sm text-muted">{{ meta.total }} pesanan dari seluruh toko di Mahapreneur.</p>
    </header>

    <div class="card flex flex-wrap items-center gap-3 p-4">
      <label class="flex min-w-56 flex-1 items-center gap-2 rounded-full border border-line bg-surface-muted px-4 py-2">
        <Search class="size-4 text-muted" />
        <input v-model="keyword" type="search" placeholder="Cari nomor pesanan atau nama penerima…" class="w-full bg-transparent text-sm text-strong outline-none" @keyup.enter="apply" />
      </label>
      <select v-model="status" class="input !w-auto !py-2 text-xs">
        <option value="">Semua status pesanan</option>
        <option v-for="(label, value) in ORDER_STATUS_LABEL" :key="value" :value="value">{{ label }}</option>
      </select>
      <select v-model="paymentStatus" class="input !w-auto !py-2 text-xs">
        <option value="">Semua status pembayaran</option>
        <option v-for="(label, value) in PAYMENT_STATUS_LABEL" :key="value" :value="value">{{ label }}</option>
      </select>
      <button class="btn btn-outline text-xs" @click="apply">Terapkan</button>
    </div>

    <div v-if="orders.length" class="card overflow-hidden">
      <div class="hidden grid-cols-[1fr_1fr_150px_140px_120px] gap-3 border-b border-line px-5 py-3 text-[11px] font-bold uppercase tracking-wide text-muted lg:grid">
        <span>Pesanan</span><span>Customer → Toko</span><span>Status</span><span>Pembayaran</span><span class="text-right">Total</span>
      </div>
      <div class="divide-y divide-[color:var(--line)]">
        <NuxtLink
          v-for="order in orders"
          :key="order.id"
          :to="`/admin/orders/${order.id}`"
          class="grid gap-3 px-5 py-4 transition hover:bg-surface-muted lg:grid-cols-[1fr_1fr_150px_140px_120px] lg:items-center"
        >
          <div class="min-w-0">
            <p class="font-mono text-xs font-bold text-strong">{{ order.orderNumber }}</p>
            <p class="text-[11px] text-muted">{{ formatDate(order.createdAt, true) }} · {{ order.items.length }} item</p>
          </div>
          <div class="min-w-0 text-xs">
            <p class="truncate font-semibold text-body">{{ order.customer?.name }}</p>
            <p class="truncate text-muted">→ {{ order.store?.name }}</p>
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

    <EmptyState v-else title="Tidak ada pesanan" description="Ubah filter atau kata kunci untuk menemukan pesanan lain." />

    <PaginationBar :meta="meta" @change="changePage" />
  </div>
</template>

<script setup lang="ts">
import { ChevronRight, Search } from 'lucide-vue-next';
import type { Order, PaginationMeta } from '~/types';

definePageMeta({ layout: 'dashboard', requiresAuth: true, roles: ['SUPERADMIN'] });

const route = useRoute();
const api = useApi();

const keyword = ref(typeof route.query.q === 'string' ? route.query.q : '');
const status = ref('');
const paymentStatus = ref('');
const page = ref(1);

const query = computed(() => ({
  q: keyword.value || undefined,
  status: status.value || undefined,
  paymentStatus: paymentStatus.value || undefined,
  page: page.value,
  limit: 10,
}));

const { data, refresh } = await useAsyncData('admin-orders', () => api.get<{ orders: Order[]; meta: PaginationMeta }>('/admin/orders', query.value), {
  watch: [query],
});

const orders = computed(() => data.value?.orders ?? []);
const meta = computed(() => data.value?.meta ?? { total: 0, page: 1, limit: 10, totalPages: 1 });

function apply() {
  page.value = 1;
  refresh();
}

function changePage(next: number) {
  page.value = next;
}

useSeoMeta({ title: 'Semua Pesanan' });
</script>