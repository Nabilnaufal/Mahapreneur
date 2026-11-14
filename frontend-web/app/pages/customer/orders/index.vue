<template>
  <div class="space-y-5">
    <header>
      <h1 class="text-xl font-extrabold text-strong">Pesanan saya</h1>
      <p class="mt-1 text-sm text-muted">Semua pesanan dan transaksimu di Mahapreneur.</p>
    </header>

    <div class="flex gap-2 overflow-x-auto pb-1 no-scrollbar">
      <button
        v-for="tab in tabs"
        :key="tab.value"
        class="badge shrink-0 border px-3.5 py-2 transition"
        :class="status === tab.value ? 'border-primary bg-primary text-white' : 'border-line text-body hover:border-line-strong'"
        @click="setStatus(tab.value)"
      >
        {{ tab.label }}
      </button>
    </div>

    <div v-if="pending" class="space-y-3">
      <div v-for="n in 3" :key="n" class="card h-32 animate-pulse" />
    </div>

    <div v-else-if="orders.length" class="space-y-4">
      <article v-for="order in orders" :key="order.id" class="card overflow-hidden">
        <header class="flex flex-wrap items-center gap-3 border-b border-line px-5 py-3.5">
          <NuxtLink v-if="order.store" :to="`/store/${order.store.slug}`" class="flex items-center gap-2 text-sm font-bold text-strong hover:underline">
            <Store class="size-4" /> {{ order.store.name }}
          </NuxtLink>
          <span class="text-xs text-muted">{{ formatDate(order.createdAt, true) }}</span>
          <span class="ml-auto font-mono text-xs font-semibold text-muted">{{ order.orderNumber }}</span>
        </header>

        <div class="grid gap-4 p-5 lg:grid-cols-[1fr_240px]">
          <div class="space-y-3">
            <div v-for="item in order.items" :key="item.id" class="flex items-center gap-3">
              <span class="size-14 shrink-0 overflow-hidden rounded-xl bg-surface-muted">
                <img v-if="item.image" :src="item.image" :alt="item.productName" class="size-full object-cover" />
              </span>
              <div class="min-w-0 flex-1">
                <p class="line-clamp-1 text-sm font-semibold text-strong">{{ item.productName }}</p>
                <p class="text-xs text-muted">{{ item.quantity }} × {{ formatRupiah(item.productPrice) }}</p>
              </div>
              <p class="text-sm font-bold text-strong">{{ formatRupiah(item.subtotal) }}</p>
            </div>
          </div>

          <div class="rounded-2xl bg-surface-muted p-4">
            <div class="flex flex-wrap items-center gap-2">
              <StatusBadge :status="order.status" kind="order" />
              <StatusBadge :status="order.paymentStatus" kind="payment" />
            </div>
            <dl class="mt-3 space-y-1.5 text-xs">
              <div class="flex justify-between">
                <dt class="text-muted">Metode</dt>
                <dd class="font-semibold text-strong">{{ paymentMethodLabel(order.paymentMethod) }}</dd>
              </div>
              <div class="flex justify-between">
                <dt class="text-muted">Total</dt>
                <dd class="text-sm font-extrabold text-strong">{{ formatRupiah(order.total) }}</dd>
              </div>
            </dl>

            <div class="mt-3 flex flex-col gap-2">
              <NuxtLink :to="`/customer/orders/${order.id}`" class="btn btn-outline !py-2 text-xs">Lihat detail</NuxtLink>
              <NuxtLink
                v-if="order.paymentMethod !== 'COD' && order.paymentStatus !== 'PAID'"
                :to="`/customer/orders/${order.id}/payment`"
                class="btn btn-primary !py-2 text-xs"
              >
                <QrCode class="size-3.5" /> Bayar sekarang
              </NuxtLink>
              <button
                v-if="canCancel(order)"
                class="btn btn-ghost !py-2 text-xs text-rose-600"
                @click="askCancel(order)"
              >
                Batalkan pesanan
              </button>
            </div>
          </div>
        </div>
      </article>

      <PaginationBar :meta="meta" @change="changePage" />
    </div>

    <EmptyState v-else title="Belum ada pesanan" description="Pesanan yang kamu buat akan muncul di sini beserta status pembayarannya.">
      <NuxtLink to="/catalog" class="btn btn-primary">Mulai belanja</NuxtLink>
    </EmptyState>

    <ConfirmDialog
      :open="Boolean(pendingCancel)"
      title="Batalkan pesanan?"
      :message="pendingCancel ? `Pesanan ${pendingCancel.orderNumber} akan dibatalkan dan stok dikembalikan ke seller.` : ''"
      confirm-label="Batalkan pesanan"
      danger
      :loading="cancelling"
      @confirm="doCancel"
      @cancel="pendingCancel = null"
    />
  </div>
</template>

<script setup lang="ts">
import { QrCode, Store } from 'lucide-vue-next';
import type { Order, PaginationMeta } from '~/types';

definePageMeta({ layout: 'account', requiresAuth: true, roles: ['CUSTOMER'] });

const api = useApi();
const ui = useUiStore();
const router = useRouter();

const status = ref('');
const page = ref(1);
const pendingCancel = ref<Order | null>(null);
const cancelling = ref(false);

const tabs = [
  { label: 'Semua', value: '' },
  { label: 'Menunggu bayar', value: 'WAITING_PAYMENT' },
  { label: 'Diproses', value: 'CONFIRMED' },
  { label: 'Siap', value: 'READY' },
  { label: 'Diantar', value: 'ON_DELIVERY' },
  { label: 'Selesai', value: 'COMPLETED' },
  { label: 'Dibatalkan', value: 'CANCELLED' },
];

const query = computed(() => ({ status: status.value || undefined, page: page.value, limit: 5 }));

const { data, pending, refresh } = await useAsyncData('customer-orders', () => api.get<{ orders: Order[]; meta: PaginationMeta }>('/customer/orders', query.value), {
  watch: [query],
});

const orders = computed(() => data.value?.orders ?? []);
const meta = computed(() => data.value?.meta ?? { total: 0, page: 1, limit: 5, totalPages: 1 });

function setStatus(value: string) {
  status.value = value;
  page.value = 1;
}

function changePage(next: number) {
  page.value = next;
}

const canCancel = (order: Order) => ['WAITING_PAYMENT', 'CONFIRMED'].includes(order.status) && order.paymentStatus !== 'PAID';

function askCancel(order: Order) {
  pendingCancel.value = order;
}

async function doCancel() {
  if (!pendingCancel.value) return;
  cancelling.value = true;
  try {
    await api.post(`/customer/orders/${pendingCancel.value.id}/cancel`, { reason: 'Dibatalkan oleh customer' });
    ui.toast('Pesanan dibatalkan.', 'success');
    await refresh();
  } catch (error) {
    ui.toast(apiError(error), 'error');
  } finally {
    cancelling.value = false;
    pendingCancel.value = null;
  }
}

useSeoMeta({ title: 'Pesanan Saya' });
</script>