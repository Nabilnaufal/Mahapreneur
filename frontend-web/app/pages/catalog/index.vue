<template>
  <div class="page-container space-y-6 pt-6">
    <nav class="flex items-center gap-2 text-xs text-muted">
      <NuxtLink to="/" class="hover:text-strong">Home</NuxtLink>
      <ChevronRight class="size-3.5" />
      <span class="font-semibold text-strong">Katalog</span>
    </nav>

    <header class="flex flex-wrap items-end justify-between gap-4">
      <div>
        <p class="text-[11px] font-bold uppercase tracking-[0.14em] text-muted">Katalog Mahapreneur</p>
        <h1 class="mt-1 text-2xl font-extrabold text-strong sm:text-3xl">
          {{ activeCategory?.name ?? (filters.type === 'SERVICE' ? 'Jasa mahasiswa' : 'Semua produk') }}
        </h1>
        <p class="mt-1 text-sm text-muted">
          {{ meta.total }} {{ filters.type === 'SERVICE' ? 'layanan' : 'produk' }} ditemukan<template v-if="filters.q"> untuk “{{ filters.q }}”</template>.
        </p>
      </div>

      <div class="flex items-center gap-2">
        <button class="btn btn-outline lg:hidden" @click="showFilters = true">
          <SlidersHorizontal class="size-4" /> Filter
        </button>
        <label class="flex items-center gap-2">
          <span class="hidden text-xs font-semibold text-muted sm:block">Urutkan</span>
          <select v-model="filters.sort" class="input !w-auto !py-2 text-xs" @change="applyFilters">
            <option value="newest">Terbaru</option>
            <option value="popular">Terlaris</option>
            <option value="price_asc">Harga terendah</option>
            <option value="price_desc">Harga tertinggi</option>
            <option value="oldest">Terlama</option>
          </select>
        </label>
      </div>
    </header>

    <!-- Chip kategori -->
    <div class="flex gap-2 overflow-x-auto pb-1 no-scrollbar">
      <button
        class="badge shrink-0 border px-3.5 py-2 transition"
        :class="!filters.category ? 'border-primary bg-primary text-white' : 'border-line text-body hover:border-line-strong'"
        @click="setCategory('')"
      >
        Semua
      </button>
      <button
        v-for="category in categories"
        :key="category.id"
        class="badge shrink-0 border px-3.5 py-2 transition"
        :class="filters.category === category.slug ? 'border-primary bg-primary text-white' : 'border-line text-body hover:border-line-strong'"
        @click="setCategory(category.slug)"
      >
        {{ category.name }}
      </button>
      <button
        class="badge shrink-0 border px-3.5 py-2 transition"
        :class="filters.type === 'SERVICE' ? 'border-primary bg-primary text-white' : 'border-line text-body hover:border-line-strong'"
        @click="toggleType"
      >
        Hanya jasa
      </button>
    </div>

    <div class="grid gap-6 lg:grid-cols-[260px_1fr]">
      <!-- Filter desktop -->
      <aside class="hidden lg:block">
        <FilterPanel
          :categories="categories"
          :stores="storeOptions"
          :model-value="filters"
          @update:model-value="onFilterChange"
          @reset="resetFilters"
        />
      </aside>

      <div>
        <div v-if="loading" class="grid grid-cols-2 gap-4 lg:grid-cols-3">
          <div v-for="n in 6" :key="n" class="card h-72 animate-pulse" />
        </div>

        <div v-else-if="products.length" class="grid grid-cols-2 gap-4 lg:grid-cols-3">
          <ProductCard v-for="product in products" :key="product.id" :product="product" />
        </div>

        <EmptyState
          v-else
          title="Produk tidak ditemukan"
          description="Coba ubah kata kunci, kategori, atau rentang harga pencarianmu."
        >
          <button class="btn btn-primary" @click="resetFilters">Reset filter</button>
        </EmptyState>

        <PaginationBar :meta="meta" @change="changePage" />
      </div>
    </div>

    <!-- Filter mobile -->
    <Transition
      enter-active-class="transition-opacity duration-200"
      leave-active-class="transition-opacity duration-150"
      enter-from-class="opacity-0"
      leave-to-class="opacity-0"
    >
      <div v-if="showFilters" class="fixed inset-0 z-[65] lg:hidden">
        <div class="absolute inset-0 bg-neutral/60 backdrop-blur-sm" @click="showFilters = false" />
        <div class="absolute inset-x-0 bottom-0 max-h-[85vh] overflow-y-auto rounded-t-3xl bg-surface-muted p-4">
          <div class="mb-3 flex items-center justify-between">
            <p class="text-sm font-bold text-strong">Filter produk</p>
            <button class="btn btn-ghost !px-2" aria-label="Tutup filter" @click="showFilters = false"><X class="size-5" /></button>
          </div>
          <FilterPanel
            :categories="categories"
            :stores="storeOptions"
            :model-value="filters"
            @update:model-value="onFilterChange"
            @reset="resetFilters"
          />
          <button class="btn btn-primary mt-4 w-full" @click="showFilters = false">Terapkan filter</button>
        </div>
      </div>
    </Transition>
  </div>
</template>

<script setup lang="ts">
import { ChevronRight, SlidersHorizontal, X } from 'lucide-vue-next';
import type { Category, PaginationMeta, Product, Store } from '~/types';

interface Filters {
  q: string;
  category: string;
  store: string;
  minPrice: string;
  maxPrice: string;
  type: string;
  sort: string;
  inStock?: string;
}

const api = useApi();
const auth = useAuthStore();
const wishlist = useWishlistStore();
const route = useRoute();
const router = useRouter();

const showFilters = ref(false);

const filters = reactive<Filters>({
  q: (route.query.q as string) ?? '',
  category: (route.query.category as string) ?? '',
  store: (route.query.store as string) ?? '',
  minPrice: (route.query.minPrice as string) ?? '',
  maxPrice: (route.query.maxPrice as string) ?? '',
  type: (route.query.type as string) ?? '',
  sort: (route.query.sort as string) ?? 'newest',
  inStock: (route.query.inStock as string) ?? '',
});

const page = ref(Number(route.query.page) || 1);

const query = computed(() => ({
  ...Object.fromEntries(Object.entries(filters).filter(([, value]) => value !== '')),
  page: page.value,
  limit: 9,
}));

const { data, pending: loading, refresh } = await useAsyncData(
  'catalog',
  () => api.get<{ products: Product[]; meta: PaginationMeta }>('/public/products', query.value),
  { watch: [query] },
);

const { data: categoryData } = await useAsyncData('catalog-categories', () =>
  api.get<{ categories: Category[] }>('/public/categories'),
);

const products = computed(() => data.value?.products ?? []);
const meta = computed(() => data.value?.meta ?? { total: 0, page: 1, limit: 9, totalPages: 1 });
const categories = computed(() => categoryData.value?.categories ?? []);
const activeCategory = computed(() => categories.value.find((category) => category.slug === filters.category));

// Opsi filter toko diambil dari produk yang sedang tampil agar tetap relevan.
const storeOptions = computed(() => {
  const map = new Map<number, Store>();
  for (const product of products.value) {
    if (product.store) map.set(product.store.id, product.store as Store);
  }
  return [...map.values()];
});

function syncUrl() {
  router.replace({
    query: Object.fromEntries(
      Object.entries({ ...filters, page: page.value > 1 ? page.value : undefined }).filter(
        ([, value]) => value !== '' && value !== undefined,
      ),
    ),
  });
}

function applyFilters() {
  page.value = 1;
  syncUrl();
  refresh();
}

function onFilterChange(next: Filters) {
  Object.assign(filters, next);
  applyFilters();
}

function setCategory(slug: string) {
  filters.category = filters.category === slug ? '' : slug;
  applyFilters();
}

function toggleType() {
  filters.type = filters.type === 'SERVICE' ? '' : 'SERVICE';
  applyFilters();
}

function resetFilters() {
  Object.assign(filters, { q: '', category: '', store: '', minPrice: '', maxPrice: '', type: '', sort: 'newest', inStock: '' });
  applyFilters();
}

function changePage(next: number) {
  page.value = next;
  syncUrl();
  refresh();
  if (import.meta.client) window.scrollTo({ top: 0, behavior: 'smooth' });
}

onMounted(() => {
  if (auth.isCustomer) wishlist.fetch();
});

useSeoMeta({
  title: 'Katalog Produk & Jasa',
  description: 'Jelajahi produk dan jasa dari toko mahasiswa di Mahapreneur. Filter berdasarkan kategori, harga, dan toko.',
});
</script>