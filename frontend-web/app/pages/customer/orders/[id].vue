<template>
  <div v-if="order" class="space-y-5">
    <div class="flex flex-wrap items-start justify-between gap-3">
      <div>
        <NuxtLink to="/customer/orders" class="flex items-center gap-1.5 text-xs font-semibold text-muted hover:text-strong">
          <ArrowLeft class="size-3.5" /> Semua pesanan
        </NuxtLink>
        <h1 class="mt-2 text-xl font-extrabold text-strong">Pesanan {{ order.orderNumber }}</h1>
        <p class="mt-1 text-sm text-muted">Dibuat {{ formatDate(order.createdAt, true) }}</p>
      </div>
      <div class="flex flex-wrap items-center gap-2">
        <StatusBadge :status="order.status" kind="order" />
        <StatusBadge :status="order.paymentStatus" kind="payment" />
      </div>
    </div>

    <!-- Aksi pembayaran -->
    <div
      v-if="order.paymentMethod !== 'COD' && order.paymentStatus !== 'PAID'"
      class="card flex flex-wrap items-center justify-between gap-3 border-amber-300 bg-amber-50 p-5 dark:border-amber-500/30 dark:bg-amber-500/10"
    >
      <div>
        <p class="text-sm font-bold text-amber-900 dark:text-amber-200">Pesanan menunggu pembayaran</p>
        <p class="mt-0.5 text-xs text-amber-800 dark:text-amber-300">
          Selesaikan pembayaran agar pesanan diteruskan ke seller.
          <template v-if="order.payment?.expiresAt"> Batas waktu {{ formatDate(order.payment.expiresAt, true) }}.</template>
        </p>
      </div>
      <NuxtLink :to="`/customer/orders/${order.id}/payment`" class="btn btn-primary">
        <QrCode class="size-4" /> Lihat instruksi pembayaran
      </NuxtLink>
    </div>

    <div v-if="order.paymentMethod === 'COD' && order.paymentStatus === 'UNPAID_COD'" class="card border-orange-300 bg-orange-50 p-5 dark:border-orange-500/30 dark:bg-orange-500/10">
      <p class="flex items-center gap-2 text-sm font-bold text-orange-900 dark:text-orange-200"><Banknote class="size-4" /> Pembayaran di tempat (COD)</p>
      <p class="mt-1 text-xs text-orange-800 dark:text-orange-300">
        Bayar langsung {{ formatRupiah(order.total) }} kepada seller saat pesanan diterima. Seller akan menandai lunas setelah pembayaran diterima.
      </p>
    </div>

    <!-- Ulasan setelah pesanan selesai -->
    <section v-if="order.status === 'COMPLETED'" class="card overflow-hidden border-emerald-200 dark:border-emerald-500/30">
      <header class="flex items-center gap-3 border-b border-line px-5 py-4">
        <span class="grid size-9 place-items-center rounded-xl bg-amber-100 text-amber-500 dark:bg-amber-500/15"><Star class="size-4" fill="currentColor" /></span>
        <div>
          <h2 class="text-sm font-bold text-strong">Bagaimana pengalamanmu?</h2>
          <p class="text-xs text-muted">Bantu seller dengan ulasan singkat tentang pesanan ini.</p>
        </div>
      </header>

      <div v-if="order.review && !editingReview" class="flex flex-wrap items-center justify-between gap-4 p-5">
        <div>
          <div class="flex items-center gap-1 text-amber-400" aria-label="Rating pesanan">
            <Star v-for="star in 5" :key="star" class="size-5" :fill="star <= order.review.rating ? 'currentColor' : 'none'" />
          </div>
          <p v-if="order.review.comment" class="mt-2 text-sm text-body">“{{ order.review.comment }}”</p>
          <p class="mt-1 text-[11px] text-muted">Ulasanmu sudah tersimpan.</p>
        </div>
        <button class="btn btn-outline !py-2 text-xs" @click="startEditingReview">Ubah ulasan</button>
      </div>

      <form v-else class="space-y-4 p-5" @submit.prevent="submitReview">
        <div>
          <p class="text-xs font-semibold text-strong">Beri rating</p>
          <div class="mt-2 flex gap-1" role="radiogroup" aria-label="Pilih rating">
            <button
              v-for="star in 5"
              :key="star"
              type="button"
              class="rounded-lg p-1 transition hover:scale-110"
              :class="star <= reviewRating ? 'text-amber-400' : 'text-muted/40'"
              :aria-label="`${star} bintang`"
              :aria-checked="reviewRating === star"
              role="radio"
              @click="reviewRating = star"
            >
              <Star class="size-7" :fill="star <= reviewRating ? 'currentColor' : 'none'" />
            </button>
          </div>
        </div>
        <textarea v-model="reviewComment" class="input min-h-24 resize-y" maxlength="1000" placeholder="Ceritakan pengalamanmu (opsional)…" />
        <div class="flex flex-wrap items-center justify-between gap-3">
          <p class="text-xs text-muted">Rating membantu customer lain menemukan seller yang terpercaya.</p>
          <button class="btn btn-primary" type="submit" :disabled="reviewing || reviewRating === 0">
            <Loader2 v-if="reviewing" class="size-4 animate-spin" />
            {{ reviewing ? 'Menyimpan…' : order.review ? 'Simpan perubahan' : 'Kirim ulasan' }}
          </button>
        </div>
      </form>
    </section>

    <div class="grid gap-5 lg:grid-cols-[1fr_340px]">
      <div class="space-y-5">
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
            <span class="text-sm font-semibold text-strong">Total</span>
            <span class="text-lg font-extrabold text-strong">{{ formatRupiah(order.total) }}</span>
          </div>
        </section>

        <!-- Info penerimaan -->
        <section class="card p-5">
          <h2 class="flex items-center gap-2 text-sm font-bold text-strong"><MapPin class="size-4" /> Info penerimaan</h2>
          <dl class="mt-3 grid gap-3 text-sm sm:grid-cols-2">
            <div>
              <dt class="text-xs font-semibold text-muted">Nama penerima</dt>
              <dd class="text-body">{{ order.recipientName }}</dd>
            </div>
            <div>
              <dt class="text-xs font-semibold text-muted">Nomor telepon</dt>
              <dd class="text-body">{{ order.recipientPhone }}</dd>
            </div>
            <div class="sm:col-span-2">
              <dt class="text-xs font-semibold text-muted">Alamat / lokasi</dt>
              <dd class="text-body">{{ order.address }}</dd>
            </div>
            <div v-if="order.campus">
              <dt class="text-xs font-semibold text-muted">Gedung / kampus</dt>
              <dd class="text-body">{{ order.campus }}</dd>
            </div>
            <div v-if="order.room">
              <dt class="text-xs font-semibold text-muted">Kelas / ruangan</dt>
              <dd class="text-body">{{ order.room }}</dd>
            </div>
            <div v-if="order.note" class="sm:col-span-2">
              <dt class="text-xs font-semibold text-muted">Catatan untuk seller</dt>
              <dd class="text-body">{{ order.note }}</dd>
            </div>
          </dl>
          <p class="mt-4 flex items-start gap-2 rounded-xl bg-surface-muted p-3 text-[11px] leading-relaxed text-muted">
            <Bike class="mt-0.5 size-3.5 shrink-0" /> Seller mengantarkan pesanan ini sendiri ke lokasi di atas. Tidak ada ekspedisi maupun ongkos kirim.
          </p>
        </section>

        <!-- Riwayat status -->
        <section v-if="order.histories?.length" class="card p-5">
          <h2 class="flex items-center gap-2 text-sm font-bold text-strong"><History class="size-4" /> Riwayat pesanan</h2>
          <ol class="mt-4 space-y-4">
            <li v-for="history in order.histories" :key="history.id" class="flex gap-3">
              <span class="mt-1 grid size-2.5 shrink-0 place-items-center rounded-full bg-primary dark:bg-tertiary" />
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
        <!-- Toko -->
        <section v-if="order.store" class="card p-5">
          <h2 class="text-sm font-bold text-strong">Penjual</h2>
          <div class="mt-3 flex items-center gap-3">
            <span class="grid size-11 shrink-0 place-items-center overflow-hidden rounded-2xl bg-surface-muted">
              <img v-if="order.store.logo" :src="order.store.logo" :alt="order.store.name" class="size-full object-cover" />
              <Store v-else class="size-5 text-muted" />
            </span>
            <div class="min-w-0">
              <p class="truncate text-sm font-bold text-strong">{{ order.store.name }}</p>
              <p class="truncate text-xs text-muted">{{ order.store.campus || order.store.address || 'Lokasi belum diisi' }}</p>
            </div>
          </div>
          <div class="mt-4 space-y-2 text-xs">
            <button class="btn btn-outline w-full !py-2 text-xs" :disabled="startingChat" @click="startChat">
              <MessageCircle class="size-3.5" /> {{ startingChat ? 'Membuka chat…' : 'Chat seller' }}
            </button>
            <NuxtLink :to="`/store/${order.store.slug}`" class="btn btn-ghost w-full !py-2 text-xs">Kunjungi toko</NuxtLink>
          </div>
        </section>

        <!-- Pembayaran -->
        <section class="card p-5">
          <h2 class="text-sm font-bold text-strong">Pembayaran</h2>
          <dl class="mt-3 space-y-2 text-xs">
            <div class="flex justify-between">
              <dt class="text-muted">Metode</dt>
              <dd class="font-semibold text-strong">{{ paymentMethodLabel(order.paymentMethod) }}</dd>
            </div>
            <div v-if="order.payment?.dokuChannel" class="flex justify-between">
              <dt class="text-muted">Channel</dt>
              <dd class="font-semibold text-strong">{{ order.payment.dokuChannel }}</dd>
            </div>
            <div v-if="order.payment?.invoiceNumber" class="flex justify-between gap-3">
              <dt class="text-muted">Invoice</dt>
              <dd class="truncate font-mono font-semibold text-strong">{{ order.payment.invoiceNumber }}</dd>
            </div>
            <div class="flex justify-between">
              <dt class="text-muted">Status</dt>
              <dd><StatusBadge :status="order.paymentStatus" kind="payment" /></dd>
            </div>
            <div v-if="order.payment?.paidAt" class="flex justify-between">
              <dt class="text-muted">Dibayar</dt>
              <dd class="font-semibold text-strong">{{ formatDate(order.payment.paidAt, true) }}</dd>
            </div>
          </dl>
        </section>

        <button v-if="canCancel" class="btn btn-outline w-full text-rose-600" @click="showCancel = true">Batalkan pesanan</button>
      </aside>
    </div>

    <ConfirmDialog
      :open="showCancel"
      title="Batalkan pesanan?"
      message="Pesanan akan dibatalkan dan stok dikembalikan ke seller. Tindakan ini tidak bisa dibatalkan."
      confirm-label="Batalkan pesanan"
      danger
      :loading="cancelling"
      @confirm="cancel"
      @cancel="showCancel = false"
    />
  </div>

  <EmptyState v-else title="Pesanan tidak ditemukan" description="Pesanan mungkin sudah dihapus atau bukan milik akunmu." />
</template>

<script setup lang="ts">
import { ArrowLeft, Banknote, Bike, History, Loader2, MapPin, MessageCircle, Package, QrCode, Star, Store } from 'lucide-vue-next';
import type { Order, OrderReview, Store as StoreType } from '~/types';

definePageMeta({ layout: 'account', requiresAuth: true, roles: ['CUSTOMER'] });

const route = useRoute();
const api = useApi();
const ui = useUiStore();

const showCancel = ref(false);
const cancelling = ref(false);
const startingChat = ref(false);
const editingReview = ref(false);
const reviewing = ref(false);
const reviewRating = ref(0);
const reviewComment = ref('');

const { data, refresh } = await useAsyncData(`order-${route.params.id}`, () =>
  api.get<{ order: Order; store: StoreType }>(`/customer/orders/${route.params.id}`),
);

const order = computed(() => data.value?.order ?? null);
const canCancel = computed(
  () => Boolean(order.value) && ['WAITING_PAYMENT', 'CONFIRMED'].includes(order.value!.status) && order.value!.paymentStatus !== 'PAID',
);

watch(order, (value) => {
  if (value?.review && !editingReview.value) {
    reviewRating.value = value.review.rating;
    reviewComment.value = value.review.comment ?? '';
  }
}, { immediate: true });

function startEditingReview() {
  reviewRating.value = order.value?.review?.rating ?? 0;
  reviewComment.value = order.value?.review?.comment ?? '';
  editingReview.value = true;
}

async function submitReview() {
  if (!order.value || reviewRating.value < 1 || reviewing.value) return;
  reviewing.value = true;
  try {
    await api.post<{ review: OrderReview }>(`/customer/orders/${order.value.id}/review`, {
      rating: reviewRating.value,
      comment: reviewComment.value.trim() || null,
    });
    ui.toast('Ulasan berhasil disimpan. Terima kasih!', 'success');
    editingReview.value = false;
    await refresh();
  } catch (error) {
    ui.toast(apiError(error), 'error');
  } finally {
    reviewing.value = false;
  }
}

async function startChat() {
  if (!order.value?.store || startingChat.value) return;

  startingChat.value = true;
  try {
    const data = await api.post<{ conversation: { id: number } }>('/customer/chats', { storeId: order.value.store.id });
    await navigateTo(`/chat?conversation=${data.conversation.id}`);
  } catch (error) {
    ui.toast(apiError(error), 'error');
  } finally {
    startingChat.value = false;
  }
}

async function cancel() {
  if (!order.value) return;
  cancelling.value = true;
  try {
    await api.post(`/customer/orders/${order.value.id}/cancel`, { reason: 'Dibatalkan oleh customer' });
    ui.toast('Pesanan dibatalkan.', 'success');
    showCancel.value = false;
    await refresh();
  } catch (error) {
    ui.toast(apiError(error), 'error');
  } finally {
    cancelling.value = false;
  }
}

useSeoMeta({ title: () => `Pesanan ${order.value?.orderNumber ?? ''}` });
</script>
