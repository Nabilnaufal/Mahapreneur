<template>
  <div class="space-y-5">
    <header>
      <h1 class="text-xl font-extrabold text-strong">Monitoring toko</h1>
      <p class="mt-1 text-sm text-muted">{{ meta.total }} toko terdaftar. Tangguhkan toko yang melanggar ketentuan.</p>
    </header>

    <div class="card flex flex-wrap items-center gap-3 p-4">
      <label class="flex min-w-56 flex-1 items-center gap-2 rounded-full border border-line bg-surface-muted px-4 py-2">
        <Search class="size-4 text-muted" />
        <input v-model="keyword" type="search" placeholder="Cari nama atau slug toko…" class="w-full bg-transparent text-sm text-strong outline-none" @keyup.enter="apply" />
      </label>
      <select v-model="status" class="input !w-auto !py-2 text-xs">
        <option value="">Semua status</option>
        <option value="ACTIVE">Aktif</option>
        <option value="INACTIVE">Nonaktif</option>
        <option value="SUSPENDED">Ditangguhkan</option>
      </select>
      <button class="btn btn-outline text-xs" @click="apply">Terapkan</button>
    </div>

    <div v-if="stores.length" class="card overflow-hidden">
      <div class="hidden grid-cols-[1fr_1fr_90px_90px_160px] gap-3 border-b border-line px-5 py-3 text-[11px] font-bold uppercase tracking-wide text-muted lg:grid">
        <span>Toko</span><span>Pemilik</span><span>Produk</span><span>Pesanan</span><span class="text-right">Status</span>
      </div>
      <div class="divide-y divide-[color:var(--line)]">
        <div v-for="store in stores" :key="store.id" class="grid gap-3 px-5 py-4 lg:grid-cols-[1fr_1fr_90px_90px_160px] lg:items-center">
          <div class="flex min-w-0 items-center gap-3">
            <span class="size-10 shrink-0 overflow-hidden rounded-xl bg-surface-muted">
              <img v-if="store.logo" :src="store.logo" :alt="store.name" class="size-full object-cover" />
              <span v-else class="grid size-full place-items-center text-muted"><Store class="size-4" /></span>
            </span>
            <div class="min-w-0">
              <NuxtLink :to="`/store/${store.slug}`" target="_blank" class="line-clamp-1 text-sm font-semibold text-strong hover:underline">{{ store.name }}</NuxtLink>
              <p class="truncate text-[11px] text-muted">{{ store.campus || store.slug }}</p>
            </div>
          </div>
          <div class="min-w-0 text-xs">
            <p class="truncate font-semibold text-body">{{ store.owner?.name ?? '—' }}</p>
            <p class="truncate text-muted">{{ store.owner?.email ?? '' }}</p>
          </div>
          <span class="text-sm text-body">{{ store.productCount ?? store._count?.products ?? 0 }}</span>
          <span class="text-sm text-body">{{ store.orderCount ?? 0 }}</span>
          <div class="flex items-center justify-end gap-2">
            <span class="badge" :class="STORE_STATUS_TONE[store.status]">{{ storeStatusLabel(store.status) }}</span>
            <select
              class="input !w-auto !py-1.5 text-xs"
              :value="store.status"
              :disabled="updating === store.id"
              aria-label="Ubah status toko"
              @change="changeStatus(store, ($event.target as HTMLSelectElement).value)"
            >
              <option value="ACTIVE">Aktif</option>
              <option value="INACTIVE">Nonaktif</option>
              <option value="SUSPENDED">Ditangguhkan</option>
            </select>
          </div>
        </div>
      </div>
    </div>

    <EmptyState v-else title="Tidak ada toko" description="Ubah filter atau kata kunci untuk menemukan toko lain." />

    <PaginationBar :meta="meta" @change="changePage" />
  </div>
</template>

<script setup lang="ts">
import { Search, Store } from 'lucide-vue-next';
import type { PaginationMeta, Store as StoreType } from '~/types';

definePageMeta({ layout: 'dashboard', requiresAuth: true, roles: ['SUPERADMIN'] });

const api = useApi();
const ui = useUiStore();

const keyword = ref('');
const status = ref('');
const page = ref(1);
const updating = ref<number | null>(null);

const query = computed(() => ({
  q: keyword.value || undefined,
  status: status.value || undefined,
  page: page.value,
  limit: 10,
}));

const { data, refresh } = await useAsyncData('admin-stores', () => api.get<{ stores: StoreType[]; meta: PaginationMeta }>('/admin/stores', query.value), {
  watch: [query],
});

const stores = computed(() => data.value?.stores ?? []);
const meta = computed(() => data.value?.meta ?? { total: 0, page: 1, limit: 10, totalPages: 1 });

function apply() {
  page.value = 1;
  refresh();
}

function changePage(next: number) {
  page.value = next;
}

async function changeStatus(store: StoreType, next: string) {
  updating.value = store.id;
  try {
    await api.patch(`/admin/stores/${store.id}/status`, { status: next });
    ui.toast(`Status ${store.name} diperbarui.`, 'success');
    await refresh();
  } catch (error) {
    ui.toast(apiError(error), 'error');
    await refresh();
  } finally {
    updating.value = null;
  }
}

useSeoMeta({ title: 'Monitoring Toko' });
</script>