<template>
  <div class="space-y-5">
    <header>
      <h1 class="text-xl font-extrabold text-strong">Transaksi & pembayaran</h1>
      <p class="mt-1 text-sm text-muted">Pantau transaksi dan notifikasi resmi DOKU. Status lunas hanya ditetapkan dari notifikasi yang valid.</p>
    </header>

    <div class="flex gap-2 overflow-x-auto pb-1 no-scrollbar">
      <button
        v-for="tab in tabs"
        :key="tab.value"
        class="badge shrink-0 border px-3.5 py-2"
        :class="activeTab === tab.value ? 'border-primary bg-primary text-white' : 'border-line text-body'"
        @click="activeTab = tab.value"
      >
        {{ tab.label }}
      </button>
    </div>

    <template v-if="activeTab === 'payments'">
      <div class="card flex flex-wrap items-center gap-3 p-4">
        <select v-model="method" class="input !w-auto !py-2 text-xs">
          <option value="">Semua metode</option>
          <option value="OVO">OVO</option>
          <option value="DANA">DANA</option>
          <option value="SHOPEEPAY">ShopeePay</option>
          <option value="COD">COD</option>
        </select>
        <select v-model="status" class="input !w-auto !py-2 text-xs">
          <option value="">Semua status</option>
          <option v-for="(label, value) in PAYMENT_STATUS_LABEL" :key="value" :value="value">{{ label }}</option>
        </select>
        <button class="btn btn-outline text-xs" @click="applyPayments">Terapkan</button>
      </div>

      <div v-if="payments.length" class="card overflow-hidden">
        <div class="hidden grid-cols-[1fr_1fr_150px_130px_120px] gap-3 border-b border-line px-5 py-3 text-[11px] font-bold uppercase tracking-wide text-muted lg:grid">
          <span>Invoice</span><span>Pesanan</span><span>Metode</span><span>Status</span><span class="text-right">Nominal</span>
        </div>
        <div class="divide-y divide-[color:var(--line)]">
          <div v-for="payment in payments" :key="payment.id" class="grid gap-3 px-5 py-4 lg:grid-cols-[1fr_1fr_150px_130px_120px] lg:items-center">
            <div class="min-w-0">
              <p class="truncate font-mono text-xs font-bold text-strong">{{ payment.invoiceNumber || `#${payment.id}` }}</p>
              <p class="text-[11px] text-muted">{{ formatDate(payment.createdAt, true) }}</p>
            </div>
            <div class="min-w-0 text-xs">
              <NuxtLink v-if="payment.order" :to="`/admin/orders/${payment.order.id}`" class="truncate font-mono font-semibold text-strong hover:underline">
                {{ payment.order.orderNumber }}
              </NuxtLink>
              <p class="truncate text-muted">{{ payment.order?.customer?.name }} → {{ payment.order?.store?.name }}</p>
            </div>
            <span class="text-xs font-semibold text-body">{{ paymentMethodLabel(payment.method) }}</span>
            <StatusBadge :status="payment.status" kind="payment" />
            <span class="text-right text-sm font-bold text-strong">{{ formatRupiah(payment.amount) }}</span>
          </div>
        </div>
      </div>

      <EmptyState v-else title="Belum ada transaksi" description="Transaksi pembayaran dari pesanan customer akan tampil di sini." />

      <PaginationBar :meta="paymentMeta" @change="changePaymentPage" />
    </template>

    <template v-else>
      <div v-if="notifications.length" class="card overflow-hidden">
        <div class="hidden grid-cols-[1fr_1fr_140px_130px_110px] gap-3 border-b border-line px-5 py-3 text-[11px] font-bold uppercase tracking-wide text-muted lg:grid">
          <span>Waktu</span><span>Invoice / Pesanan</span><span>Status DOKU</span><span>Signature</span><span class="text-right">Diproses</span>
        </div>
        <div class="divide-y divide-[color:var(--line)]">
          <div v-for="log in notifications" :key="log.id" class="grid gap-3 px-5 py-4 lg:grid-cols-[1fr_1fr_140px_130px_110px] lg:items-center">
            <div class="min-w-0">
              <p class="text-xs font-semibold text-strong">{{ formatDate(log.createdAt, true) }}</p>
              <p class="truncate font-mono text-[10px] text-muted">{{ log.notificationKey }}</p>
            </div>
            <div class="min-w-0 text-xs">
              <p class="truncate font-mono font-semibold text-body">{{ log.invoiceNumber || '—' }}</p>
              <p class="truncate text-muted">{{ log.orderNumber || '' }}</p>
            </div>
            <span class="text-xs font-semibold text-body">{{ log.transactionStatus || '—' }}</span>
            <span class="badge w-fit" :class="log.signatureValid ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-500/15 dark:text-emerald-300' : 'bg-rose-100 text-rose-800 dark:bg-rose-500/15 dark:text-rose-300'">
              {{ log.signatureValid ? 'Valid' : 'Tidak valid' }}
            </span>
            <div class="flex justify-end">
              <span class="badge" :class="log.processed ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-500/15 dark:text-emerald-300' : 'bg-amber-100 text-amber-800 dark:bg-amber-500/15 dark:text-amber-300'">
                {{ log.processed ? 'Ya' : 'Belum' }}
              </span>
            </div>
          </div>
        </div>
      </div>

      <EmptyState v-else title="Belum ada notifikasi DOKU" description="Notifikasi pembayaran yang masuk dari DOKU akan tercatat di sini, termasuk yang tanda tangannya tidak valid." />
    </template>
  </div>
</template>

<script setup lang="ts">
import type { PaginationMeta, PaymentMethod, PaymentNotification, PaymentStatus } from '~/types';

definePageMeta({ layout: 'dashboard', requiresAuth: true, roles: ['SUPERADMIN'] });

interface AdminPayment {
  id: number;
  method: PaymentMethod;
  status: PaymentStatus;
  amount: number;
  invoiceNumber: string | null;
  dokuChannel: string | null;
  createdAt: string;
  order: { id: number; orderNumber: string; store: { name: string }; customer: { name: string; email: string } } | null;
}

const api = useApi();

const tabs = [
  { label: 'Transaksi', value: 'payments' },
  { label: 'Notifikasi DOKU', value: 'notifications' },
];
const activeTab = ref('payments');

const method = ref('');
const status = ref('');
const page = ref(1);

const query = computed(() => ({
  method: method.value || undefined,
  status: status.value || undefined,
  page: page.value,
  limit: 10,
}));

const { data, refresh } = await useAsyncData('admin-payments', () =>
  api.get<{ payments: AdminPayment[]; meta: PaginationMeta }>('/admin/payments', query.value),
  { watch: [query] },
);

const { data: notificationData } = await useAsyncData('admin-payment-notifications', () =>
  api.get<{ notifications: PaymentNotification[] }>('/admin/payment-notifications'),
);

const payments = computed(() => data.value?.payments ?? []);
const paymentMeta = computed(() => data.value?.meta ?? { total: 0, page: 1, limit: 10, totalPages: 1 });
const notifications = computed(() => notificationData.value?.notifications ?? []);

function applyPayments() {
  page.value = 1;
  refresh();
}

function changePaymentPage(next: number) {
  page.value = next;
}

useSeoMeta({ title: 'Transaksi & Pembayaran' });
</script>
