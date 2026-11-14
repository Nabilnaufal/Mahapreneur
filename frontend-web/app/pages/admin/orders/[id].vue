<template>
  <div v-if="order" class="space-y-5">
    <div class="flex flex-wrap items-start justify-between gap-3">
      <div>
        <NuxtLink to="/admin/orders" class="flex items-center gap-1.5 text-xs font-semibold text-muted hover:text-strong">
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

    <div class="grid gap-5 lg:grid-cols-[1fr_340px]">
      <div class="space-y-5">
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
            <span class="text-sm font-semibold text-strong">Total pesanan</span>
            <span class="text-lg font-extrabold text-strong">{{ formatRupiah(order.total) }}</span>
          </div>
        </section>

        <section class="card p-5">
          <h2 class="flex items-center gap-2 text-sm font-bold text-strong"><MapPin class="size-4" /> Tujuan pengantaran</h2>
          <div class="mt-4 rounded-2xl bg-surface-muted p-4">
            <p class="text-base font-bold text-strong">{{ order.recipientName }}</p>
            <p class="mt-1 text-sm font-semibold text-body">{{ order.recipientPhone }}</p>
            <p class="mt-2 text-sm text-body">{{ order.address }}</p>
            <p v-if="order.campus || order.room" class="mt-1 text-xs text-muted">{{ [order.campus, order.room].filter(Boolean).join(' · ') }}</p>
            <p v-if="order.note" class="mt-3 rounded-xl border border-line bg-surface p-3 text-xs text-body">
              <span class="font-semibold text-strong">Catatan:</span> {{ order.note }}
            </p>
          </div>
        </section>

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
          <h2 class="text-sm font-bold text-strong">Pihak terkait</h2>
          <dl class="mt-3 space-y-3 text-xs">
            <div>
              <dt class="text-muted">Customer</dt>
              <dd class="mt-0.5 font-semibold text-strong">{{ order.customer?.name }}</dd>
              <dd class="text-muted">{{ order.customer?.email }}</dd>
            </div>
            <div>
              <dt class="text-muted">Toko</dt>
              <dd class="mt-0.5 font-semibold text-strong">{{ order.store?.name }}</dd>
              <NuxtLink v-if="order.store" :to="`/store/${order.store.slug}`" target="_blank" class="text-muted hover:underline">Lihat toko</NuxtLink>
            </div>
          </dl>
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
          <p class="mt-4 text-[11px] leading-relaxed text-muted">
            Status pembayaran hanya berubah menjadi lunas setelah notifikasi resmi DOKU diverifikasi backend.
          </p>
        </section>
      </aside>
    </div>
  </div>

  <EmptyState v-else title="Pesanan tidak ditemukan" description="Pesanan ini tidak tersedia atau sudah dihapus." />
</template>

<script setup lang="ts">
import { ArrowLeft, History, MapPin, Package } from 'lucide-vue-next';
import type { Order } from '~/types';

definePageMeta({ layout: 'dashboard', requiresAuth: true, roles: ['SUPERADMIN'] });

const route = useRoute();
const api = useApi();

const { data } = await useAsyncData(`admin-order-${route.params.id}`, () =>
  api.get<{ order: Order }>(`/admin/orders/${route.params.id}`),
);

const order = computed(() => data.value?.order ?? null);

useSeoMeta({ title: () => `Pesanan ${order.value?.orderNumber ?? ''}` });
</script>