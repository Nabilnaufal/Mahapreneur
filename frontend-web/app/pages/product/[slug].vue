<template>
  <div v-if="product" class="page-container space-y-10 pt-6">
    <nav class="flex flex-wrap items-center gap-2 text-xs text-muted">
      <NuxtLink to="/" class="hover:text-strong">Home</NuxtLink>
      <ChevronRight class="size-3.5" />
      <NuxtLink to="/catalog" class="hover:text-strong">Katalog</NuxtLink>
      <template v-if="product.category">
        <ChevronRight class="size-3.5" />
        <NuxtLink :to="`/catalog?category=${product.category.slug}`" class="hover:text-strong">{{ product.category.name }}</NuxtLink>
      </template>
      <ChevronRight class="size-3.5" />
      <span class="truncate font-semibold text-strong">{{ product.name }}</span>
    </nav>

    <div class="grid gap-8 lg:grid-cols-2">
      <ProductGallery :images="product.images" :alt="product.name">
        <span v-if="product.type === 'SERVICE'" class="badge absolute left-4 top-4 bg-primary text-tertiary">Layanan Jasa</span>
      </ProductGallery>

      <div>
        <p v-if="product.store" class="flex items-center gap-2 text-xs font-bold uppercase tracking-wide text-muted">
          <CategoryIcon :name="product.category?.icon" size="size-3.5" />
          {{ product.category?.name }}
        </p>

        <h1 class="mt-2 text-2xl font-extrabold leading-tight text-strong sm:text-3xl">{{ product.name }}</h1>
        <RatingStars class="mt-3 text-sm" :rating="product.rating" :count="product.ratingCount" show-count />

        <div class="mt-4 flex flex-wrap items-end gap-3">
          <p class="text-3xl font-extrabold text-strong">{{ formatRupiah(product.price) }}</p>
          <span
            class="badge"
            :class="product.stock > 0 ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-500/15 dark:text-emerald-300' : 'bg-rose-100 text-rose-700 dark:bg-rose-500/15 dark:text-rose-300'"
          >
            {{ product.stock > 0 ? (product.type === 'SERVICE' ? `${product.stock} slot tersedia` : `Stok ${product.stock}`) : 'Stok habis' }}
          </span>
          <span v-if="product.sold > 0" class="badge bg-surface-muted text-muted">{{ product.sold }} terjual</span>
        </div>

        <!-- Info toko -->
        <NuxtLink
          v-if="store"
          :to="`/store/${store.slug}`"
          class="card mt-6 flex items-center gap-3 p-4 transition hover:-translate-y-0.5"
        >
          <span class="grid size-11 shrink-0 place-items-center overflow-hidden rounded-2xl bg-surface-muted">
            <img v-if="store.logo" :src="store.logo" :alt="store.name" class="size-full object-cover" />
            <Store v-else class="size-5 text-muted" />
          </span>
          <div class="min-w-0 flex-1">
            <p class="truncate text-sm font-bold text-strong">{{ store.name }}</p>
            <RatingStars class="mt-1 text-[11px]" :rating="store.rating" :count="store.ratingCount" show-count />
            <p class="flex items-center gap-1.5 truncate text-xs text-muted">
              <MapPin class="size-3.5" /> {{ store.campus || store.address || 'Lokasi toko belum diisi' }}
            </p>
          </div>
          <span class="btn btn-outline !py-1.5 text-xs">Kunjungi toko</span>
        </NuxtLink>

        <!-- Aksi -->
        <div class="card mt-5 space-y-4 p-5">
          <div class="flex items-center justify-between gap-4">
            <span class="text-sm font-semibold text-strong">{{ product.type === 'SERVICE' ? 'Jumlah slot' : 'Jumlah' }}</span>
            <QuantityStepper v-model="quantity" :max="Math.max(1, product.stock)" />
          </div>

          <div class="flex items-center justify-between border-t border-line pt-4 text-sm">
            <span class="text-muted">Subtotal</span>
            <span class="text-lg font-extrabold text-strong">{{ formatRupiah(product.price * quantity) }}</span>
          </div>

          <div class="grid gap-2 sm:grid-cols-2">
            <button class="btn btn-outline" :disabled="product.stock === 0" @click="addToCart">
              <Check v-if="added" class="size-4 text-emerald-600" />
              <ShoppingBag v-else class="size-4" />
              {{ added ? 'Masuk keranjang!' : 'Keranjang' }}
            </button>
            <button class="btn btn-primary" :disabled="product.stock === 0" @click="orderNow">
              <Zap class="size-4" /> Pesan sekarang
            </button>
          </div>
          <button class="btn btn-ghost w-full text-xs" @click="toggleWishlist">
            <Heart class="size-4" :class="wishlisted ? 'fill-rose-500 text-rose-500' : ''" />
            {{ wishlisted ? 'Hapus dari wishlist' : 'Simpan ke wishlist' }}
          </button>
        </div>

        <!-- Info pengantaran mandiri -->
        <div class="mt-4 flex items-start gap-3 rounded-2xl border border-line bg-surface-muted p-4">
          <Bike class="mt-0.5 size-4 shrink-0 text-primary dark:text-tertiary" />
          <p class="text-xs leading-relaxed text-muted">
            <span class="font-semibold text-strong">Diantar langsung oleh seller.</span>
            Mahapreneur tidak memakai ekspedisi dan tidak menambahkan ongkos kirim. Kamu menentukan lokasi penerimaan saat checkout
            (kelas, gedung, kos, atau rumah), lalu seller mengantarkannya sendiri.
          </p>
        </div>
      </div>
    </div>

    <!-- Deskripsi & ketentuan -->
    <section class="grid gap-6 lg:grid-cols-3">
      <div class="card p-6 lg:col-span-2">
        <h2 class="text-lg font-extrabold text-strong">Deskripsi {{ product.type === 'SERVICE' ? 'layanan' : 'produk' }}</h2>
        <p class="mt-3 whitespace-pre-line text-sm leading-relaxed text-body">{{ product.description }}</p>
      </div>

      <div class="card space-y-4 p-6">
        <h2 class="text-lg font-extrabold text-strong">Informasi</h2>
        <dl class="space-y-3 text-sm">
          <div class="flex justify-between gap-4">
            <dt class="text-muted">Kategori</dt>
            <dd class="font-semibold text-strong">{{ product.category?.name ?? '-' }}</dd>
          </div>
          <div class="flex justify-between gap-4">
            <dt class="text-muted">Jenis</dt>
            <dd class="font-semibold text-strong">{{ product.type === 'SERVICE' ? 'Jasa' : 'Barang' }}</dd>
          </div>
          <div v-if="product.serviceDuration" class="flex justify-between gap-4">
            <dt class="text-muted">Estimasi</dt>
            <dd class="font-semibold text-strong">{{ product.serviceDuration }}</dd>
          </div>
          <div class="flex justify-between gap-4">
            <dt class="text-muted">Metode bayar</dt>
            <dd class="font-semibold text-strong">OVO, DANA, ShopeePay, COD, dan DOKU</dd>
          </div>
        </dl>
        <div v-if="product.serviceTerms" class="border-t border-line pt-4">
          <p class="text-xs font-bold uppercase tracking-wide text-muted">Ketentuan pemesanan</p>
          <p class="mt-2 whitespace-pre-line text-xs leading-relaxed text-body">{{ product.serviceTerms }}</p>
        </div>
      </div>
    </section>

    <!-- Ulasan pembeli -->
    <section class="card p-6">
      <div class="flex flex-wrap items-end justify-between gap-3 border-b border-line pb-5">
        <div>
          <h2 class="text-lg font-extrabold text-strong">Ulasan pembeli</h2>
          <p class="mt-1 text-xs text-muted">Pengalaman customer yang sudah menyelesaikan pesanan ini.</p>
        </div>
        <RatingStars :rating="product.rating" :count="product.ratingCount" show-count />
      </div>

      <div v-if="reviews.length" class="divide-y divide-[color:var(--line)]">
        <article v-for="review in reviews" :key="review.id" class="py-5 first:pt-0 last:pb-0">
          <div class="flex flex-wrap items-center justify-between gap-2">
            <div class="flex items-center gap-2">
              <span class="grid size-8 place-items-center rounded-full bg-primary text-xs font-bold text-tertiary">
                {{ review.customer?.name?.slice(0, 1).toUpperCase() || '?' }}
              </span>
              <p class="text-sm font-bold text-strong">{{ review.customer?.name || 'Customer' }}</p>
            </div>
            <div class="flex items-center gap-3">
              <RatingStars :rating="review.rating" :show-value="false" size="size-4" />
              <span class="text-[11px] text-muted">{{ formatDate(review.createdAt) }}</span>
            </div>
          </div>
          <p class="mt-3 text-sm leading-relaxed text-body">{{ review.comment || 'Customer memberikan rating untuk produk ini.' }}</p>
        </article>
      </div>
      <p v-else class="py-8 text-center text-sm text-muted">Belum ada ulasan untuk produk ini.</p>
    </section>

    <section v-if="fromStore.length">
      <SectionHeading eyebrow="Toko yang sama" title="Produk lain dari toko ini" :to="store ? `/store/${store.slug}` : undefined" />
      <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
        <ProductCard v-for="item in fromStore" :key="item.id" :product="item" />
      </div>
    </section>

    <section v-if="related.length">
      <SectionHeading eyebrow="Serupa" title="Produk terkait" :to="`/catalog?category=${product.category?.slug}`" />
      <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
        <ProductCard v-for="item in related" :key="item.id" :product="item" />
      </div>
    </section>
  </div>

  <div v-else class="page-container py-20">
    <EmptyState title="Produk tidak ditemukan" description="Produk mungkin sudah dihapus atau dinonaktifkan oleh penjual.">
      <NuxtLink to="/catalog" class="btn btn-primary">Kembali ke katalog</NuxtLink>
    </EmptyState>
  </div>
</template>

<script setup lang="ts">
import { Bike, Check, ChevronRight, Heart, MapPin, ShoppingBag, Store, Zap } from 'lucide-vue-next';
import type { Product, Store as StoreType } from '~/types';

const route = useRoute();
const api = useApi();
const auth = useAuthStore();
const cart = useCartStore();
const wishlist = useWishlistStore();
const ui = useUiStore();
const router = useRouter();
const { fly } = useFlyToCart();

const quantity = ref(1);
const added = ref(false);

const { data } = await useAsyncData(`product-${route.params.slug}`, () =>
  api.get<{ product: Product; store: StoreType; fromStore: Product[]; related: Product[] }>(`/public/products/${route.params.slug}`),
);

const product = computed(() => data.value?.product ?? null);
const store = computed(() => data.value?.store ?? null);
const fromStore = computed(() => data.value?.fromStore ?? []);
const related = computed(() => data.value?.related ?? []);
const reviews = computed(() => product.value?.reviews ?? []);
const wishlisted = computed(() => (product.value ? wishlist.has(product.value.id) : false));

function requireLogin() {
  ui.openLogin(route.fullPath);
}

async function addToCart(event: MouseEvent) {
  if (!product.value) return;
  if (!auth.isCustomer) return requireLogin();
  const button = event.currentTarget as HTMLElement;
  try {
    await cart.add(product.value.id, quantity.value);
    fly(button, product.value.images[0] ?? product.value.image);
    added.value = true;
    setTimeout(() => (added.value = false), 1400);
  } catch (error) {
    ui.toast(apiError(error), 'error');
  }
}

async function orderNow(event: MouseEvent) {
  if (!product.value) return;
  if (!auth.isCustomer) return requireLogin();
  const button = event.currentTarget as HTMLElement;
  try {
    await cart.add(product.value.id, quantity.value);
    fly(button, product.value.images[0] ?? product.value.image);
    await new Promise((resolve) => setTimeout(resolve, 320));
    router.push('/customer/checkout');
  } catch (error) {
    ui.toast(apiError(error), 'error');
  }
}

async function toggleWishlist() {
  if (!product.value) return;
  if (!auth.isCustomer) return requireLogin();
  try {
    await wishlist.toggle(product.value.id);
  } catch (error) {
    ui.toast(apiError(error), 'error');
  }
}

onMounted(() => {
  if (auth.isCustomer) wishlist.fetch();
});

useSeoMeta({
  title: () => product.value?.name ?? 'Produk',
  description: () => product.value?.description?.slice(0, 150) ?? 'Detail produk di Mahapreneur.',
});
</script>
