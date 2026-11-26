<template>
  <div v-if="order" class="space-y-5">
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
        <!-- Pilih channel -->
        <section class="card p-5">
          <h2 class="text-sm font-bold text-strong">Pilih channel pembayaran</h2>
          <p class="mt-1 text-xs text-muted">
            <template v-if="simulated">Metode OVO, DANA, dan ShopeePay masih berjalan dalam mode simulasi. Pilih metode lalu tandai pembayaran berhasil untuk melanjutkan pesanan.</template>
            <template v-else>Channel yang tampil mengikuti konfigurasi akun DOKU. Pilih salah satu untuk mendapatkan kode bayar.</template>
          </p>
          <div class="mt-4 grid gap-2 sm:grid-cols-2">
            <button
              v-for="channel in channels"
              :key="channel.channelCode"
              class="rounded-2xl border p-3 text-left text-xs transition"
              :class="payment?.dokuChannel === channel.channelCode ? 'border-primary bg-primary/5 dark:bg-primary-soft/20' : 'border-line hover:border-line-strong'"
              @click="chooseChannel(channel.channelCode)"
            >
              <p class="font-bold text-strong">{{ channel.label }}</p>
              <p class="mt-0.5 font-mono text-[10px] uppercase text-muted">{{ channel.channelCode }}</p>
            </button>
          </div>
          <p v-if="!channels.length" class="mt-3 rounded-xl bg-surface-muted p-3 text-xs text-muted">
            Belum ada channel yang tersedia untuk metode ini. Coba muat ulang halaman atau hubungi admin.
          </p>
        </section>

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

        <!-- Simulasi -->
        <section v-if="simulated" class="card border-dashed border-line-strong p-5">
          <h2 class="flex items-center gap-2 text-sm font-bold text-strong"><FlaskConical class="size-4" /> Simulasi pembayaran</h2>
          <p class="mt-1 text-xs leading-relaxed text-muted">
            Metode ini belum terhubung ke gateway pembayaran. Tombol ini hanya untuk pengujian alur pesanan dan tidak memproses uang sungguhan.
          </p>
          <button class="btn btn-tertiary mt-4" :disabled="loading" @click="simulate">
            <Loader2 v-if="loading" class="size-4 animate-spin" />
            <Zap v-else class="size-4" />
            Simulasikan pembayaran berhasil
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
import { AlertTriangle, ArrowLeft, CheckCircle2, Copy, ExternalLink, FlaskConical, Loader2, QrCode, ShieldCheck, Zap } from 'lucide-vue-next';
import type { Payment } from '~/types';

definePageMeta({ layout: 'account', requiresAuth: true, roles: ['CUSTOMER'] });

const route = useRoute();
const api = useApi();
const ui = useUiStore();

const loading = ref(false);

interface PaymentData {
  order: { id: number; orderNumber: string; total: number; status: string; paymentStatus: string; paymentMethod: string };
  payment: Payment | null;
  channels: { channelCode: string; label: string; method: string }[];
  simulated: boolean;
}

const { data, refresh } = await useAsyncData(`payment-${route.params.id}`, () =>
  api.get<PaymentData>(`/customer/orders/${route.params.id}/payment`),
);

const order = computed(() => data.value?.order ?? null);
const payment = computed(() => data.value?.payment ?? null);
const channels = computed(() => data.value?.channels ?? []);
const simulated = computed(() => data.value?.simulated ?? false);

async function chooseChannel(channelCode: string) {
  loading.value = true;
  try {
    await api.post(`/customer/orders/${route.params.id}/payment/channel`, { channelCode });
    await refresh();
  } catch (error) {
    ui.toast(apiError(error), 'error');
  } finally {
    loading.value = false;
  }
}

async function simulate() {
  loading.value = true;
  try {
    await api.post(`/customer/orders/${route.params.id}/payment/simulate`);
    ui.toast('Pembayaran simulasi berhasil diproses.', 'success');
    await refresh();
  } catch (error) {
    ui.toast(apiError(error), 'error');
  } finally {
    loading.value = false;
  }
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
