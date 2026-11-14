<template>
  <div class="space-y-5">
    <header class="flex flex-wrap items-end justify-between gap-3">
      <div>
        <h1 class="text-xl font-extrabold text-strong">Wishlist</h1>
        <p class="mt-1 text-sm text-muted">{{ items.length }} produk dan jasa tersimpan.</p>
      </div>
      <NuxtLink to="/catalog" class="btn btn-outline text-xs">Jelajahi produk</NuxtLink>
    </header>

    <div v-if="items.length" class="grid grid-cols-2 gap-4 lg:grid-cols-3">
      <ProductCard v-for="item in items" :key="item.id" :product="item.product" />
    </div>
    <EmptyState v-else title="Wishlist masih kosong" description="Simpan produk atau jasa favoritmu agar mudah ditemukan lagi.">
      <NuxtLink to="/catalog" class="btn btn-primary">Mulai jelajahi</NuxtLink>
    </EmptyState>
  </div>
</template>

<script setup lang="ts">
definePageMeta({ layout: 'account', requiresAuth: true, roles: ['CUSTOMER'] });

const wishlist = useWishlistStore();
const items = computed(() => wishlist.items);

await useAsyncData('wishlist-page', async () => {
  await wishlist.fetch();
  return true;
});

useSeoMeta({ title: 'Wishlist' });
</script>