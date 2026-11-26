<template>
  <div v-if="order" class="space-y-5">
    <div v-if="redirecting" class="card flex items-center gap-3 border-primary/30 bg-primary/5 p-4 text-sm text-strong">
      <Loader2 class="size-5 animate-spin text-primary" />
      Mengarahkan ke simulator pembayaran DOKU…
    </div>

    <div>
      <NuxtLink :to="`/customer/orders/${order.id}`" class="flex items-center gap-1.5 text-xs font-semibold text-muted hover:text-strong">
        <ArrowLeft class="size-3.5" /> Detail pesanan
      </NuxtLink>
      <h1 class="mt-2 text-xl font-extrabold text-strong">Instruksi pembayaran</h1>
      <p class="mt-1 text-sm text-muted">
        Pesanan <span class="font-mono font-semibold text-strong">{{ order.orderNumber }}</span> · {{ formatRupiah(order.total) }}
      </p>
    </div>

    <!-- Sudah lunas -->
    <div v-if="payment?.status === 'PAID'" class="card border-emerald-300 bg-emerald-50 p-6 text-center dark:border-emerald-500/30 dark:bg-emerald-500/10">
      <CheckCircle2 class="mx-auto size-10 text-emerald-600 dark:text-emerald-400" />
      <p class="mt-3 text-base font-bold text-emerald-900 dark:text-emerald-200">Pembayaran berhasil</p>
      <p class="mt-1 text-sm text-emerald-800 dark:text-emerald-300">
        Pesanan sudah diteruskan ke seller untuk diproses dan diantarkan.
      </p>
      <NuxtLink :to="`/customer/orders/${order.id}`" class="btn btn-primary mt-5">Lihat detail pesanan</NuxtLink>
    </div>

    <div v-else-if="payment?.status === 'EXPIRED' || payment?.status === 'FAILED'" class="card border-rose-300 bg-rose-50 p-6 dark:border-rose-500/30 dark:bg-rose-500/10">
      <AlertTriangle class="mx-auto size-10 text-rose-600 dark:text-rose-400" />
      <p class="mt-3 text-center text-base font-bold text-rose-900 dark:text-rose-200">
        Pembayaran {{ payment.status === 'EXPIRED' ? 'kedaluwarsa' : 'gagal' }}
      </p>
      <p class="mt-1 text-center text-sm text-rose-800 dark:text-rose-300">Buat instruksi pembayaran baru untuk menyelesaikan pesanan ini.</p>
      <button class="btn btn-primary mx-auto mt-5" :disabled="loading" @click="retry">
        <Loader2 v-if="loading" class="size-4 animate-spin" /> Buat pembayaran baru
      </button>
    </div>

    <div v-else class="grid items-start gap-5 lg:grid-cols-[1fr_320px]">
      <div class="space-y-5">
        <!-- Data bayar -->
        <section v-if="payment?.dokuQrString || payment?.dokuVaNumber || payment?.dokuPaymentUrl" class="card p-5">
          <h2 class="text-sm font-bold text-strong">Data pembayaran</h2>

          <div v-if="payment.dokuQrString" class="mt-4 flex flex-col items-center gap-3">
            <div class="grid size-44 place-items-center rounded-2xl border border-line bg-white p-3">
              <QrCode class="size-32 text-neutral" />
            </div>
            <p class="text-center text-xs text-muted">QRIS aktif untuk semua e-wallet dan m-banking.</p>
            <code class="max-w-full break-all rounded-xl bg-surface-muted px-3 py-2 text-center font-mono text-[10px] text-body">
              {{ payment.dokuQrString }}
            </code>
          </div>

          <div v-else-if="payment.dokuVaNumber" class="mt-4">
            <p class="text-xs text-muted">Nomor Virtual Account</p>
            <div class="mt-1 flex items-center gap-2">
              <code class="flex-1 rounded-xl bg-surface-muted px-4 py-3 font-mono text-lg font-bold tracking-wider text-strong">
                {{ payment.dokuVaNumber }}
              </code>
              <button class="btn btn-outline !px-3" aria-label="Salin nomor VA" @click="copy(payment.dokuVaNumber!)">
                <Copy class="size-4" />
              </button>
            </div>
            <p class="mt-2 text-xs text-muted">Nominal harus tepat {{ formatRupiah(order.total) }} agar otomatis terverifikasi.</p>
          </div>

          <a
            v-if="payment.dokuPaymentUrl && payment.dokuPaymentUrl.startsWith('http')"
            :href="payment.dokuPaymentUrl"
            target="_blank"
            rel="noopener"
            class="btn btn-primary mt-4 w-full"
          >
            <ExternalLink class="size-4" /> Buka halaman pembayaran DOKU
          </a>
        </section>

        <section v-else class="card border-amber-300 bg-amber-50 p-5 dark:border-amber-500/30 dark:bg-amber-500/10">
          <h2 class="text-sm font-bold text-amber-900 dark:text-amber-200">Link pembayaran DOKU belum tersedia</h2>
          <p class="mt-1 text-xs leading-relaxed text-amber-800 dark:text-amber-300">
            DOKU belum mengembalikan halaman pembayaran untuk invoice ini. Buat instruksi baru untuk mencoba kembali.
          </p>
          <button class="btn btn-primary mt-4" :disabled="loading" @click="retry">
            <Loader2 v-if="loading" class="size-4 animate-spin" />
            Coba buat pembayaran lagi
          </button>
        </section>

      </div>

      <aside class="space-y-5">
        <section class="card p-5">
          <h2 class="text-sm font-bold text-strong">Ringkasan</h2>
          <dl class="mt-3 space-y-2 text-xs">
            <div class="flex justify-between">
              <dt class="text-muted">Total tagihan</dt>
              <dd class="text-base font-extrabold text-strong">{{ formatRupiah(order.total) }}</dd>
            </div>
            <div class="flex justify-between">
              <dt class="text-muted">Metode</dt>
              <dd class="font-semibold text-strong">{{ paymentMethodLabel(order.paymentMethod) }}</dd>
            </div>
            <div class="flex justify-between">
              <dt class="text-muted">Status</dt>
              <dd><StatusBadge :status="payment?.status ?? 'PENDING'" kind="payment" /></dd>
            </div>
            <div v-if="payment?.invoiceNumber" class="flex justify-between gap-3">
              <dt class="text-muted">Invoice</dt>
              <dd class="truncate font-mono font-semibold text-strong">{{ payment.invoiceNumber }}</dd>
            </div>
            <div v-if="payment?.expiresAt" class="flex justify-between">
              <dt class="text-muted">Batas waktu</dt>
              <dd class="font-semibold text-strong">{{ formatDate(payment.expiresAt, true) }}</dd>
            </div>
          </dl>
        </section>

        <section class="card p-5 text-xs leading-relaxed text-muted">
          <p class="flex items-center gap-2 text-sm font-bold text-strong"><ShieldCheck class="size-4" /> Aman dan terverifikasi</p>
          <p class="mt-2">
            Status pembayaran hanya berubah menjadi lunas setelah backend menerima notifikasi resmi dari DOKU dengan signature dan nominal yang
            sudah diverifikasi. Halaman ini tidak bisa mengubah status pembayaran.
          </p>
        </section>
      </aside>
    </div>
  </div>

  <EmptyState v-else title="Pesanan tidak ditemukan" description="Data pembayaran tidak tersedia untuk pesanan ini." />
</template>

<script setup lang="ts">
import { AlertTriangle, ArrowLeft, CheckCircle2, Copy, ExternalLink, Loader2, QrCode, ShieldCheck } from 'lucide-vue-next';
import type { Payment } from '~/types';

definePageMeta({ layout: 'account', requiresAuth: true, roles: ['CUSTOMER'] });

const route = useRoute();
const api = useApi();
const ui = useUiStore();

const loading = ref(false);

interface PaymentData {
  order: { id: number; orderNumber: string; total: number; status: string; paymentStatus: string; paymentMethod: string };
  payment: Payment | null;
}

const { data, refresh } = await useAsyncData(`payment-${route.params.id}`, () =>
  api.get<PaymentData>(`/customer/orders/${route.params.id}/payment`),
);

const order = computed(() => data.value?.order ?? null);
const payment = computed(() => data.value?.payment ?? null);
const redirecting = ref(false);

onMounted(() => {
  // Hanya pembayaran yang masih menunggu yang diarahkan; status FAILED/PAID
  // harus menampilkan halaman ini, bukan checkout DOKU yang sudah tidak berlaku.
  if (payment.value?.status === 'PENDING') redirectToDoku(payment.value.dokuPaymentUrl);
});

watch(() => payment.value?.dokuPaymentUrl, (paymentUrl) => redirectToDoku(paymentUrl));

function redirectToDoku(paymentUrl?: string | null) {
  if (redirecting.value || !paymentUrl?.startsWith('http')) return;
  redirecting.value = true;
  window.location.replace(paymentUrl);
}

async function retry() {
  loading.value = true;
  try {
    await api.post(`/customer/orders/${route.params.id}/payment/retry`);
    ui.toast('Instruksi pembayaran baru dibuat.', 'success');
    await refresh();
  } catch (error) {
    ui.toast(apiError(error), 'error');
  } finally {
    loading.value = false;
  }
}

async function copy(value: string) {
  try {
    await navigator.clipboard.writeText(value);
    ui.toast('Nomor disalin ke clipboard', 'success');
  } catch {
    ui.toast('Tidak dapat menyalin otomatis. Salin manual ya.', 'error');
  }
}

useSeoMeta({ title: 'Instruksi Pembayaran' });
</script>
