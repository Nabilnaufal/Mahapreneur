<template>
  <div v-if="order" class="space-y-5">
    <div class="flex flex-wrap items-start justify-between gap-3">
      <div>
        <NuxtLink to="/seller/orders" class="flex items-center gap-1.5 text-xs font-semibold text-muted hover:text-strong">
          <ArrowLeft class="size-3.5" /> Semua pesanan
        </NuxtLink>
        <h1 class="mt-2 text-xl font-extrabold text-strong">Pesanan {{ order.orderNumber }}</h1>
        <p class="mt-1 text-sm text-muted">Masuk {{ formatDate(order.createdAt, true) }}</p>
      </div>
      <div class="flex flex-wrap items-center gap-2">
        <StatusBadge :status="order.status" kind="order" />
        <StatusBadge :status="order.paymentStatus" kind="payment" />
      </div>
    </div>

    <!-- Aksi utama -->
    <section class="card p-5">
      <h2 class="text-sm font-bold text-strong">Kelola pesanan</h2>

      <p v-if="order.status === 'WAITING_PAYMENT'" class="mt-2 flex items-start gap-2 rounded-xl bg-amber-50 p-3 text-xs text-amber-800 dark:bg-amber-500/10 dark:text-amber-300">
        <Clock class="mt-0.5 size-3.5 shrink-0" />
        Customer belum menyelesaikan pembayaran. Pesanan akan otomatis masuk ke daftar proses setelah pembayaran terverifikasi.
      </p>

      <p v-else-if="order.paymentStatus === 'UNPAID_COD'" class="mt-2 flex items-start gap-2 rounded-xl bg-orange-50 p-3 text-xs text-orange-800 dark:bg-orange-500/10 dark:text-orange-300">
        <Banknote class="mt-0.5 size-3.5 shrink-0" />
        Pesanan COD. Tagih {{ formatRupiah(order.total) }} saat menyerahkan pesanan, lalu tandai pembayaran lunas di bawah.
      </p>

      <div class="mt-4 flex flex-wrap gap-2">
        <button
          v-for="action in nextActions"
          :key="action.status"
          class="btn"
          :class="action.danger ? 'btn-outline text-rose-600' : 'btn-primary'"
          :disabled="updating"
          @click="action.danger ? (showCancel = true) : updateStatus(action.status)"
        >
          <Loader2 v-if="updating && !action.danger" class="size-4 animate-spin" />
          <component :is="action.icon" v-else class="size-4" />
          {{ action.label }}
        </button>

        <button
          v-if="order.paymentMethod === 'COD' && order.paymentStatus === 'UNPAID_COD'"
          class="btn btn-tertiary"
          :disabled="updating"
          @click="markCodPaid"
        >
          <BadgeCheck class="size-4" /> Tandai pembayaran COD lunas
        </button>
      </div>

      <div v-if="order.status === 'COMPLETED'" class="mt-3 text-xs text-muted">Pesanan ini sudah selesai.</div>
      <div v-if="order.status === 'CANCELLED'" class="mt-3 text-xs text-muted">Pesanan ini dibatalkan.</div>
    </section>

    <div class="grid gap-5 lg:grid-cols-[1fr_340px]">
      <div class="space-y-5">
        <!-- Info penerima: penting untuk pengantaran mandiri -->
        <section class="card p-5">
          <h2 class="flex items-center gap-2 text-sm font-bold text-strong"><Bike class="size-4" /> Antar ke lokasi ini</h2>
          <div class="mt-4 rounded-2xl bg-surface-muted p-4">
            <p class="text-base font-bold text-strong">{{ order.recipientName }}</p>
            <a :href="`tel:${order.recipientPhone}`" class="mt-1 flex items-center gap-1.5 text-sm font-semibold text-primary hover:underline dark:text-tertiary">
              <Phone class="size-3.5" /> {{ order.recipientPhone }}
            </a>
            <p class="mt-2 text-sm text-body">{{ order.address }}</p>
            <p v-if="order.campus || order.room" class="mt-1 text-xs text-muted">
              {{ [order.campus, order.room].filter(Boolean).join(' · ') }}
            </p>
            <p v-if="order.note" class="mt-3 rounded-xl border border-line bg-surface p-3 text-xs text-body">
              <span class="font-semibold text-strong">Catatan customer:</span> {{ order.note }}
            </p>
          </div>
          <p class="mt-3 text-[11px] leading-relaxed text-muted">
            Mahapreneur tidak memakai ekspedisi. Antarkan sendiri pesanan ini, lalu perbarui status agar customer tahu perkembangannya.
          </p>
        </section>

        <!-- Item -->
        <section class="card overflow-hidden">
          <header class="flex items-center gap-2 border-b border-line px-5 py-3.5">
            <Package class="size-4" /> <h2 class="text-sm font-bold text-strong">Produk dipesan</h2>
          </header>
          <ul class="divide-y divide-[color:var(--line)]">
            <li v-for="item in order.items" :key="item.id" class="flex items-center gap-3 p-5">
              <span class="size-14 shrink-0 overflow-hidden rounded-xl bg-surface-muted">
                <img v-if="item.image" :src="item.image" :alt="item.productName" class="size-full object-cover" />
              </span>
              <div class="min-w-0 flex-1">
                <p class="line-clamp-1 text-sm font-semibold text-strong">{{ item.productName }}</p>
                <p class="text-xs text-muted">{{ item.quantity }} × {{ formatRupiah(item.productPrice) }}</p>
              </div>
              <p class="text-sm font-bold text-strong">{{ formatRupiah(item.subtotal) }}</p>
            </li>
          </ul>
          <div class="flex justify-between border-t border-line px-5 py-4">
            <span class="text-sm font-semibold text-strong">Total diterima seller</span>
            <span class="text-lg font-extrabold text-strong">{{ formatRupiah(order.total) }}</span>
          </div>
        </section>

        <!-- Riwayat -->
        <section v-if="order.histories?.length" class="card p-5">
          <h2 class="flex items-center gap-2 text-sm font-bold text-strong"><History class="size-4" /> Riwayat status</h2>
          <ol class="mt-4 space-y-4">
            <li v-for="history in order.histories" :key="history.id" class="flex gap-3">
              <span class="mt-1 size-2.5 shrink-0 rounded-full bg-primary dark:bg-tertiary" />
              <div>
                <p class="text-sm font-semibold text-strong">{{ orderStatusLabel(history.status) }}</p>
                <p v-if="history.note" class="text-xs text-muted">{{ history.note }}</p>
                <p class="text-[11px] text-muted">{{ formatDate(history.createdAt, true) }}</p>
              </div>
            </li>
          </ol>
        </section>
      </div>

      <aside class="space-y-5">
        <section class="card p-5">
          <h2 class="text-sm font-bold text-strong">Customer</h2>
          <p class="mt-2 text-sm font-semibold text-strong">{{ order.customer?.name }}</p>
          <p class="text-xs text-muted">{{ order.customer?.email }}</p>
          <p v-if="order.customer?.phone" class="mt-1 text-xs text-muted">{{ order.customer.phone }}</p>
        </section>

        <section class="card p-5">
          <h2 class="text-sm font-bold text-strong">Pembayaran</h2>
          <dl class="mt-3 space-y-2 text-xs">
            <div class="flex justify-between">
              <dt class="text-muted">Metode</dt>
              <dd class="font-semibold text-strong">{{ paymentMethodLabel(order.paymentMethod) }}</dd>
            </div>
            <div class="flex justify-between">
              <dt class="text-muted">Status</dt>
              <dd><StatusBadge :status="order.paymentStatus" kind="payment" /></dd>
            </div>
            <div v-if="order.payment?.invoiceNumber" class="flex justify-between gap-3">
              <dt class="text-muted">Invoice</dt>
              <dd class="truncate font-mono font-semibold text-strong">{{ order.payment.invoiceNumber }}</dd>
            </div>
            <div v-if="order.payment?.dokuChannel" class="flex justify-between">
              <dt class="text-muted">Channel</dt>
              <dd class="font-semibold text-strong">{{ order.payment.dokuChannel }}</dd>
            </div>
            <div v-if="order.payment?.paidAt" class="flex justify-between">
              <dt class="text-muted">Dibayar</dt>
              <dd class="font-semibold text-strong">{{ formatDate(order.payment.paidAt, true) }}</dd>
            </div>
          </dl>
        </section>
      </aside>
    </div>

    <ConfirmDialog
      :open="showCancel"
      title="Batalkan pesanan ini?"
      message="Stok produk akan dikembalikan dan customer mendapat notifikasi pembatalan."
      confirm-label="Batalkan pesanan"
      danger
      :loading="updating"
      @confirm="cancelOrder"
      @cancel="showCancel = false"
    />
  </div>

  <EmptyState v-else title="Pesanan tidak ditemukan" description="Pesanan ini bukan milik tokomu atau sudah tidak tersedia." />
</template>

<script setup lang="ts">
import {
  ArrowLeft, BadgeCheck, Banknote, Bike, CheckCircle2, Clock, History, Loader2, Package, Phone, PlayCircle, Send, Truck,
} from 'lucide-vue-next';
import type { Order, OrderStatus } from '~/types';

definePageMeta({ layout: 'dashboard', requiresAuth: true, roles: ['SELLER'] });

const route = useRoute();
const api = useApi();
const ui = useUiStore();

const updating = ref(false);
const showCancel = ref(false);

const { data, refresh } = await useAsyncData(`seller-order-${route.params.id}`, () =>
  api.get<{ order: Order }>(`/seller/orders/${route.params.id}`),
);

const order = computed(() => data.value?.order ?? null);

// Hanya transisi yang diizinkan backend yang ditampilkan.
const TRANSITIONS: Record<string, { status: OrderStatus; label: string; icon: unknown; danger?: boolean }[]> = {
  CONFIRMED: [
    { status: 'PROCESSING', label: 'Mulai proses', icon: PlayCircle },
    { status: 'CANCELLED', label: 'Batalkan', icon: Clock, danger: true },
  ],
  PROCESSING: [
    { status: 'READY', label: 'Tandai siap', icon: CheckCircle2 },
    { status: 'CANCELLED', label: 'Batalkan', icon: Clock, danger: true },
  ],
  READY: [{ status: 'ON_DELIVERY', label: 'Mulai antar', icon: Truck }],
  ON_DELIVERY: [{ status: 'COMPLETED', label: 'Pesanan diterima', icon: Send }],
};

const nextActions = computed(() => (order.value ? TRANSITIONS[order.value.status] ?? [] : []));

async function updateStatus(status: OrderStatus, note?: string) {
  if (!order.value) return;
  updating.value = true;
  try {
    await api.patch(`/seller/orders/${order.value.id}/status`, { status, note });
    ui.toast(`Status diperbarui: ${orderStatusLabel(status)}`, 'success');
    await refresh();
  } catch (error) {
    ui.toast(apiError(error), 'error');
  } finally {
    updating.value = false;
    showCancel.value = false;
  }
}

async function markCodPaid() {
  if (!order.value) return;
  updating.value = true;
  try {
    await api.post(`/seller/orders/${order.value.id}/cod-paid`);
    ui.toast('Pembayaran COD ditandai lunas.', 'success');
    await refresh();
  } catch (error) {
    ui.toast(apiError(error), 'error');
  } finally {
    updating.value = false;
  }
}

const cancelOrder = () => updateStatus('CANCELLED', 'Dibatalkan oleh seller');

useSeoMeta({ title: () => `Pesanan ${order.value?.orderNumber ?? ''}` });
</script>