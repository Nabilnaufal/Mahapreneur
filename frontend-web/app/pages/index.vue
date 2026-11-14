<template>
  <div class="page-container space-y-12 pt-6">
    <BannerCarousel v-if="home?.banners.length" :banners="home.banners" />

    <!-- Kategori -->
    <section>
      <SectionHeading eyebrow="Kurasi Kategori" title="Belanja sesuai kebutuhanmu" to="/categories" link-label="Semua kategori" />
      <div class="grid grid-cols-2 gap-3 sm:grid-cols-4 lg:grid-cols-8">
        <NuxtLink
          v-for="category in home?.categories ?? []"
          :key="category.id"
          :to="`/catalog?category=${category.slug}`"
          class="card flex flex-col items-center gap-2.5 px-3 py-5 text-center transition hover:-translate-y-1"
        >
          <span class="grid size-11 place-items-center rounded-2xl bg-surface-muted text-primary dark:text-tertiary">
            <CategoryIcon :name="category.icon" size="size-5" />
          </span>
          <span class="text-xs font-bold text-strong">{{ category.name }}</span>
          <span class="text-[10px] text-muted">{{ category.productCount ?? 0 }} produk</span>
        </NuxtLink>
      </div>
    </section>

    <!-- Produk terbaru -->
    <section v-if="home?.latest.length">
      <SectionHeading eyebrow="Baru masuk" title="Produk terbaru dari mahasiswa" to="/catalog?sort=newest" />
      <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
        <ProductCard v-for="product in home.latest.slice(0, 4)" :key="product.id" :product="product" />
      </div>
    </section>

    <!-- Promo Mahapreneur -->
    <section class="overflow-hidden rounded-3xl bg-primary p-8 md:p-12">
      <div class="grid items-center gap-8 lg:grid-cols-[1.2fr_1fr]">
        <div>
          <p class="badge bg-tertiary text-neutral">Tentang Mahapreneur</p>
          <h2 class="mt-4 text-2xl font-extrabold leading-tight text-white md:text-3xl">
            Marketplace bisnis mahasiswa, diantar langsung ke tanganmu.
          </h2>
          <p class="mt-3 max-w-xl text-sm leading-relaxed text-white/70">
            Mahapreneur menghubungkan mahasiswa yang berjualan dengan mahasiswa yang butuh. Tanpa ekspedisi dan tanpa ongkos kirim —
            seller mengantar sendiri pesananmu ke kelas, kos, atau rumah.
          </p>
          <div class="mt-6 flex flex-wrap gap-3">
            <NuxtLink to="/auth/register?role=SELLER" class="btn btn-tertiary">
              <Store class="size-4" /> Buka toko sekarang
            </NuxtLink>
            <NuxtLink to="/stores" class="btn btn-outline !border-white/25 !text-white hover:!bg-white/10">
              Lihat toko mahasiswa
            </NuxtLink>
          </div>
        </div>

        <dl class="grid grid-cols-2 gap-3">
          <div v-for="stat in statCards" :key="stat.label" class="rounded-2xl bg-white/5 p-4 backdrop-blur">
            <dt class="text-[11px] font-semibold uppercase tracking-wide text-white/60">{{ stat.label }}</dt>
            <dd class="mt-1 text-2xl font-extrabold text-tertiary">{{ stat.value }}</dd>
          </div>
        </dl>
      </div>
    </section>

    <!-- Terlaris -->
    <section v-if="home?.bestsellers.length">
      <SectionHeading eyebrow="Paling dicari" title="Produk terlaris minggu ini" subtitle="Berdasarkan jumlah pembelian nyata di Mahapreneur." to="/catalog?sort=popular" />
      <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
        <ProductCard v-for="product in home.bestsellers.slice(0, 4)" :key="product.id" :product="product" />
      </div>
    </section>

    <!-- Rekomendasi -->
    <section v-if="home?.recommended.length">
      <SectionHeading
        eyebrow="Untuk kamu"
        title="Produk baru yang belum terjual"
        subtitle="Bantu sesama mahasiswa mendapat pembeli pertamanya."
        to="/catalog"
      />
      <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
        <ProductCard v-for="product in home.recommended.slice(0, 4)" :key="product.id" :product="product" />
      </div>
    </section>

    <!-- Per kategori -->
    <section v-for="section in home?.categorySections ?? []" :key="section.category.id">
      <SectionHeading
        :eyebrow="`Kategori ${section.category.name}`"
        :title="`Pilihan ${section.category.name.toLowerCase()} terbaru`"
        :to="`/catalog?category=${section.category.slug}`"
      />
      <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
        <ProductCard v-for="product in section.products" :key="product.id" :product="product" />
      </div>
    </section>

    <!-- Toko pilihan -->
    <section v-if="home?.topStores.length">
      <SectionHeading eyebrow="Toko mahasiswa" title="Kenalan dengan penjualnya" to="/stores" />
      <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <StoreCard v-for="store in home.topStores" :key="store.id" :store="store" />
      </div>
    </section>
  </div>
</template>

<script setup lang="ts">
import { Store } from 'lucide-vue-next';
import type { Banner, Category, Product, Store as StoreType } from '~/types';

interface HomeData {
  banners: Banner[];
  categories: Category[];
  latest: Product[];
  bestsellers: Product[];
  recommended: Product[];
  categorySections: { category: Category; products: Product[] }[];
  topStores: StoreType[];
  stats: { sellers: number; stores: number; products: number; orders: number };
}

const api = useApi();
const auth = useAuthStore();
const wishlist = useWishlistStore();

const { data: home } = await useAsyncData('home', () => api.get<HomeData>('/public/home'));

const statCards = computed(() => [
  { label: 'Toko aktif', value: formatNumber(home.value?.stats.stores ?? 0) },
  { label: 'Seller mahasiswa', value: formatNumber(home.value?.stats.sellers ?? 0) },
  { label: 'Produk & jasa', value: formatNumber(home.value?.stats.products ?? 0) },
  { label: 'Pesanan diproses', value: formatNumber(home.value?.stats.orders ?? 0) },
]);

onMounted(() => {
  if (auth.isCustomer) wishlist.fetch();
});

useSeoMeta({
  title: 'Marketplace Bisnis Mahasiswa',
  ogTitle: 'Mahapreneur — Marketplace Bisnis Mahasiswa',
  description: 'Belanja produk dan jasa dari toko mahasiswa, atau buka tokomu sendiri. Pesanan diantar langsung oleh seller.',
});
</script>