<template>
  <div class="page-container space-y-6 pt-6">
    <header>
      <p class="text-[11px] font-bold uppercase tracking-[0.14em] text-muted">Toko Mahasiswa</p>
      <h1 class="mt-1 text-2xl font-extrabold text-strong sm:text-3xl">Semua toko di Mahapreneur</h1>
      <p class="mt-1 max-w-2xl text-sm text-muted">
        Setiap toko dijalankan oleh mahasiswa. Belanja langsung dari penjualnya dan atur sendiri cara pesanan diserahkan.
      </p>
    </header>

    <form class="flex max-w-md gap-2" @submit.prevent="apply">
      <label class="flex flex-1 items-center gap-2 rounded-full border border-line bg-surface px-4 py-2.5">
        <Search class="size-4 text-muted" />
        <input v-model="keyword" type="search" placeholder="Cari nama toko…" class="w-full bg-transparent text-sm text-strong outline-none" aria-label="Cari toko" />
      </label>
      <button class="btn btn-primary">Cari</button>
    </form>

    <div v-if="stores.length" class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
      <StoreCard v-for="store in stores" :key="store.id" :store="store" />
    </div>
    <EmptyState v-else title="Toko tidak ditemukan" description="Coba kata kunci lain, atau jelajahi katalog produk." />

    <PaginationBar :meta="meta" @change="changePage" />
  </div>
</template>

<script setup lang="ts">
import { Search } from 'lucide-vue-next';
import type { PaginationMeta, Store } from '~/types';

const api = useApi();
const route = useRoute();
const router = useRouter();

const keyword = ref((route.query.q as string) ?? '');
const page = ref(Number(route.query.page) || 1);

const query = computed(() => ({ q: keyword.value || undefined, page: page.value, limit: 9 }));

const { data, refresh } = await useAsyncData('stores', () => api.get<{ stores: Store[]; meta: PaginationMeta }>('/public/stores', query.value), {
  watch: [query],
});

const stores = computed(() => data.value?.stores ?? []);
const meta = computed(() => data.value?.meta ?? { total: 0, page: 1, limit: 9, totalPages: 1 });

function apply() {
  page.value = 1;
  router.replace({ query: keyword.value ? { q: keyword.value } : {} });
  refresh();
}

function changePage(next: number) {
  page.value = next;
  refresh();
}

useSeoMeta({ title: 'Toko Mahasiswa', description: 'Daftar toko mahasiswa di Mahapreneur.' });
</script>