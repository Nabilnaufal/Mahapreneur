<template>
  <div v-if="store" class="pb-6">
    <!-- Banner toko -->
    <div class="relative h-44 overflow-hidden bg-primary sm:h-60">
      <img v-if="store.banner" :src="store.banner" :alt="`Banner ${store.name}`" class="size-full object-cover" />
      <div v-else class="size-full bg-gradient-to-br from-primary via-primary-soft to-primary" />
      <div class="absolute inset-0 bg-gradient-to-t from-neutral/70 to-transparent" />
    </div>

    <div class="page-container">
      <!-- Kartu identitas toko -->
      <div class="card relative z-10 -mt-14 p-5 sm:p-6">
        <div class="flex flex-col gap-5 sm:flex-row sm:items-start">
          <span class="grid size-20 shrink-0 place-items-center overflow-hidden rounded-3xl border-4 border-surface-raised bg-surface-muted sm:size-24">
            <img v-if="store.logo" :src="store.logo" :alt="store.name" class="size-full object-cover" />
            <Store v-else class="size-8 text-muted" />
          </span>

          <div class="min-w-0 flex-1">
            <div class="flex flex-wrap items-center gap-2">
              <h1 class="text-xl font-extrabold text-strong sm:text-2xl">{{ store.name }}</h1>
              <span class="badge bg-tertiary text-neutral">Toko mahasiswa</span>
              <span
                v-if="store.status !== 'ACTIVE'"
                class="badge bg-rose-100 text-rose-700 dark:bg-rose-500/15 dark:text-rose-300"
              >Sedang tidak aktif</span>
            </div>
            <RatingStars class="mt-2 text-xs" :rating="store.rating" :count="store.ratingCount" show-count />

            <p class="mt-2 max-w-3xl text-sm leading-relaxed text-body">{{ store.description || 'Toko ini belum menuliskan deskripsi.' }}</p>

            <dl class="mt-4 grid gap-3 text-xs sm:grid-cols-3">
              <div class="flex items-start gap-2">
                <User class="mt-0.5 size-4 shrink-0 text-muted" />
                <div>
                  <dt class="font-semibold text-muted">Pemilik</dt>
                  <dd class="text-body">{{ store.user?.name ?? owner?.name ?? '-' }}</dd>
                </div>
              </div>
              <div class="flex items-start gap-2">
                <Clock class="mt-0.5 size-4 shrink-0 text-muted" />
                <div>
                  <dt class="font-semibold text-muted">Jam operasional</dt>
                  <dd class="text-body">{{ store.openTime && store.closeTime ? `${store.openTime}–${store.closeTime} WIB` : 'Belum diatur' }}</dd>
                </div>
              </div>
              <div class="flex items-start gap-2">
                <MapPin class="mt-0.5 size-4 shrink-0 text-muted" />
                <div>
                  <dt class="font-semibold text-muted">Lokasi</dt>
                  <dd class="text-body">{{ store.campus || store.address || 'Belum diatur' }}</dd>
                </div>
              </div>
            </dl>

            <div class="mt-4 flex flex-wrap items-center gap-2 text-xs">
              <button
                v-if="(auth.isCustomer || !auth.isAuthenticated) && store.status === 'ACTIVE'"
                class="btn btn-primary !py-1.5 text-xs"
                :disabled="startingChat"
                @click="startChat"
              >
                <MessageCircle class="size-3.5" /> {{ startingChat ? 'Membuka chat…' : 'Chat penjual' }}
              </button>
              <a v-if="store.email" :href="`mailto:${store.email}`" class="btn btn-outline !py-1.5 text-xs">
                <Mail class="size-3.5" /> {{ store.email }}
              </a>
              <span class="badge bg-surface-muted text-muted">{{ meta.total }} produk</span>
            </div>
          </div>

          <div class="rounded-2xl border border-line bg-surface-muted p-4 text-xs sm:w-56">
            <p class="flex items-center gap-2 font-bold text-strong"><Bike class="size-4" /> Pengantaran mandiri</p>
            <p class="mt-2 leading-relaxed text-muted">
              Seller mengantarkan sendiri pesananmu. Pilih lokasi penerimaan saat checkout — tanpa ongkos kirim.
            </p>
          </div>
        </div>
      </div>

      <!-- Filter dalam toko -->
      <div class="mt-8 space-y-5">
        <div class="flex flex-wrap items-center gap-3">
          <form class="flex flex-1 gap-2 sm:max-w-sm" @submit.prevent="applySearch">
            <label class="flex flex-1 items-center gap-2 rounded-full border border-line bg-surface px-4 py-2.5">
              <Search class="size-4 text-muted" />
              <input v-model="keyword" type="search" placeholder="Cari di toko ini…" class="w-full bg-transparent text-sm text-strong outline-none" aria-label="Cari produk di toko" />
            </label>
          </form>
          <label class="flex items-center gap-2">
            <span class="hidden text-xs font-semibold text-muted sm:block">Urutkan</span>
            <select v-model="sort" class="input !w-auto !py-2 text-xs">
              <option value="newest">Terbaru</option>
              <option value="popular">Terlaris</option>
              <option value="price_asc">Harga terendah</option>
              <option value="price_desc">Harga tertinggi</option>
            </select>
          </label>
        </div>

        <div v-if="categories.length" class="flex gap-2 overflow-x-auto pb-1 no-scrollbar">
          <button
            class="badge shrink-0 border px-3.5 py-2"
            :class="!category ? 'border-primary bg-primary text-white' : 'border-line text-body'"
            @click="setCategory('')"
          >Semua produk</button>
          <button
            v-for="item in categories"
            :key="item.id"
            class="badge shrink-0 border px-3.5 py-2"
            :class="category === item.slug ? 'border-primary bg-primary text-white' : 'border-line text-body'"
            @click="setCategory(item.slug)"
          >{{ item.name }}</button>
        </div>

        <div v-if="products.length" class="grid grid-cols-2 gap-4 lg:grid-cols-4">
          <ProductCard v-for="product in products" :key="product.id" :product="product" />
        </div>
        <EmptyState v-else title="Belum ada produk" description="Toko ini belum menampilkan produk pada filter yang kamu pilih." />

        <PaginationBar :meta="meta" @change="changePage" />
      </div>
    </div>
  </div>

  <div v-else class="page-container py-20">
    <EmptyState title="Toko tidak ditemukan" description="Toko mungkin sudah dinonaktifkan atau tautannya salah.">
      <NuxtLink to="/stores" class="btn btn-primary">Lihat toko lain</NuxtLink>
    </EmptyState>
  </div>
</template>

<script setup lang="ts">
import { Bike, Clock, Mail, MapPin, MessageCircle, Search, Store, User } from 'lucide-vue-next';
import type { Category, PaginationMeta, Product, Store as StoreType } from '~/types';

const route = useRoute();
const router = useRouter();
const api = useApi();
const auth = useAuthStore();
const ui = useUiStore();
const wishlist = useWishlistStore();

const startingChat = ref(false);

const keyword = ref((route.query.q as string) ?? '');
const category = ref((route.query.category as string) ?? '');
const sort = ref((route.query.sort as string) ?? 'newest');
const page = ref(Number(route.query.page) || 1);

const query = computed(() => ({
  q: keyword.value || undefined,
  category: category.value || undefined,
  sort: sort.value,
  page: page.value,
  limit: 8,
}));

const { data, refresh } = await useAsyncData(
  `store-${route.params.slug}`,
  () => api.get<{ store: StoreType; owner: { name: string }; products: Product[]; categories: Category[]; meta: PaginationMeta }>(`/public/stores/${route.params.slug}`, query.value),
  { watch: [query] },
);

const store = computed(() => data.value?.store ?? null);
const owner = computed(() => data.value?.owner ?? null);
const products = computed(() => data.value?.products ?? []);
const categories = computed(() => data.value?.categories ?? []);
const meta = computed(() => data.value?.meta ?? { total: 0, page: 1, limit: 8, totalPages: 1 });

function sync() {
  router.replace({
    query: Object.fromEntries(
      Object.entries({ q: keyword.value, category: category.value, sort: sort.value, page: page.value > 1 ? page.value : undefined }).filter(
        ([, value]) => value !== '' && value !== undefined,
      ),
    ),
  });
}

function applySearch() {
  page.value = 1;
  sync();
  refresh();
}

function setCategory(slug: string) {
  category.value = category.value === slug ? '' : slug;
  applySearch();
}

function changePage(next: number) {
  page.value = next;
  sync();
  refresh();
}

async function startChat() {
  if (!auth.isAuthenticated) {
    ui.openLogin(route.fullPath);
    return;
  }
  if (!store.value || !auth.isCustomer || startingChat.value) return;

  startingChat.value = true;
  try {
    const data = await api.post<{ conversation: { id: number } }>('/customer/chats', { storeId: store.value.id });
    await navigateTo(`/chat?conversation=${data.conversation.id}`);
  } catch (error) {
    ui.toast(apiError(error), 'error');
  } finally {
    startingChat.value = false;
  }
}

watch(sort, applySearch);

onMounted(() => {
  if (auth.isCustomer) wishlist.fetch();
});

useSeoMeta({
  title: () => store.value?.name ?? 'Toko',
  description: () => store.value?.description?.slice(0, 150) ?? 'Toko mahasiswa di Mahapreneur.',
});
</script>
