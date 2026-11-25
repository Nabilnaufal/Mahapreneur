<template>
  <NuxtLink :to="`/store/${store.slug}`" class="card group overflow-hidden transition hover:-translate-y-1">
    <div class="relative h-28 bg-surface-muted">
      <img v-if="store.banner" :src="store.banner" :alt="store.name" class="size-full object-cover" />
      <div v-else class="size-full bg-gradient-to-r from-primary to-primary-soft" />
    </div>
    <div class="px-5 pb-5">
      <div class="relative z-10 -mt-7 flex items-end gap-3">
        <span class="grid size-14 shrink-0 place-items-center overflow-hidden rounded-2xl border-4 border-surface bg-surface-raised">
          <img v-if="store.logo" :src="store.logo" :alt="store.name" class="size-full object-cover" />
          <Store v-else class="size-6 text-muted" />
        </span>
        <span class="badge mb-1 bg-tertiary text-neutral">{{ store.productCount ?? store._count?.products ?? 0 }} produk</span>
      </div>
      <h3 class="mt-3 truncate text-base font-extrabold text-strong group-hover:underline">{{ store.name }}</h3>
      <RatingStars class="mt-2 text-xs" :rating="store.rating" :count="store.ratingCount" show-count />
      <p class="mt-1 line-clamp-2 text-xs leading-relaxed text-muted">{{ store.description || 'Toko mahasiswa di Mahapreneur.' }}</p>
      <p v-if="store.campus" class="mt-3 flex items-center gap-1.5 text-[11px] font-semibold text-muted">
        <MapPin class="size-3.5" /> {{ store.campus }}
      </p>
    </div>
  </NuxtLink>
</template>

<script setup lang="ts">
import { MapPin, Store } from 'lucide-vue-next';
import type { Store as StoreType } from '~/types';

defineProps<{ store: StoreType }>();
</script>
