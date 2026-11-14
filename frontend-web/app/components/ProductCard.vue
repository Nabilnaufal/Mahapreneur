<template>
  <article class="card group flex flex-col overflow-hidden transition hover:-translate-y-1">
    <NuxtLink :to="`/product/${product.slug}`" class="relative block aspect-[4/3] overflow-hidden bg-surface-muted">
      <img
        v-if="product.image"
        :src="product.image"
        :alt="product.name"
        class="size-full object-cover transition duration-500 group-hover:scale-105"
        loading="lazy"
      />
      <div v-else class="grid size-full place-items-center text-muted"><ImageOff class="size-8" /></div>

      <span v-if="product.type === 'SERVICE'" class="badge absolute left-3 top-3 bg-primary text-tertiary">Jasa</span>
      <span
        v-else-if="product.stock === 0"
        class="badge absolute left-3 top-3 bg-neutral/85 text-white"
      >Stok habis</span>
      <span v-else-if="product.sold > 0" class="badge absolute left-3 top-3 bg-tertiary text-neutral">Terjual {{ product.sold }}</span>

      <button
        class="absolute right-3 top-3 grid size-9 place-items-center rounded-full bg-surface/90 text-body backdrop-blur transition hover:text-rose-500"
        :aria-label="wishlisted ? 'Hapus dari wishlist' : 'Tambah ke wishlist'"
        @click.prevent.stop="toggleWishlist"
      >
        <Heart class="size-4" :class="wishlisted ? 'fill-rose-500 text-rose-500' : ''" />
      </button>
    </NuxtLink>

    <div class="flex flex-1 flex-col p-4">
      <NuxtLink
        v-if="product.store"
        :to="`/store/${product.store.slug}`"
        class="text-[11px] font-bold uppercase tracking-wide text-muted hover:text-strong"
      >
        {{ product.store.name }}
      </NuxtLink>
      <NuxtLink :to="`/product/${product.slug}`" class="mt-1 line-clamp-2 text-sm font-bold leading-snug text-strong hover:underline">
        {{ product.name }}
      </NuxtLink>

      <p class="mt-2 flex items-center gap-1.5 text-[11px] font-semibold text-muted">
        <CategoryIcon :name="product.category?.icon" size="size-3.5" />
        {{ product.category?.name ?? 'Tanpa kategori' }}
      </p>

      <div class="mt-auto flex items-end justify-between gap-2 pt-4">
        <div>
          <p class="text-base font-extrabold text-strong">{{ formatRupiah(product.price) }}</p>
          <p class="text-[11px] text-muted">
            {{ product.type === 'SERVICE' ? `${product.stock} slot tersedia` : `Stok ${product.stock}` }}
          </p>
        </div>
        <button
          class="btn !px-3 !py-2"
          :class="added ? 'btn-tertiary' : 'btn-primary'"
          :disabled="product.stock === 0"
          :aria-label="`Tambah ${product.name} ke keranjang`"
          @click="addToCart"
        >
          <Check v-if="added" class="size-4" />
          <ShoppingBag v-else class="size-4" />
          <span class="hidden xl:inline">{{ added ? 'Masuk!' : 'Keranjang' }}</span>
        </button>
      </div>
    </div>
  </article>
</template>

<script setup lang="ts">
import { Check, Heart, ImageOff, ShoppingBag } from 'lucide-vue-next';
import type { Product } from '~/types';

const props = defineProps<{ product: Product }>();

const api = useApi();
const auth = useAuthStore();
const cart = useCartStore();
const wishlist = useWishlistStore();
const ui = useUiStore();
const router = useRouter();
const { fly } = useFlyToCart();

const wishlisted = computed(() => wishlist.has(props.product.id));
const added = ref(false);

function requireLogin() {
  ui.openLogin(router.currentRoute.value.fullPath);
}

async function addToCart(event: MouseEvent) {
  if (!auth.isCustomer) return requireLogin();
  const button = event.currentTarget as HTMLElement;
  try {
    await cart.add(props.product.id, 1);
    fly(button, props.product.image);
    added.value = true;
    setTimeout(() => (added.value = false), 1400);
  } catch (error) {
    ui.toast(apiError(error, 'Gagal menambahkan ke keranjang'), 'error');
  }
}

async function toggleWishlist() {
  if (!auth.isCustomer) return requireLogin();
  try {
    await wishlist.toggle(props.product.id);
  } catch (error) {
    ui.toast(apiError(error), 'error');
  }
}
</script>
