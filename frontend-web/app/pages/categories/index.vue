<template>
  <div class="page-container space-y-6 pt-6">
    <header>
      <p class="text-[11px] font-bold uppercase tracking-[0.14em] text-muted">Kategori</p>
      <h1 class="mt-1 text-2xl font-extrabold text-strong sm:text-3xl">Jelajahi berdasarkan kategori</h1>
      <p class="mt-1 max-w-2xl text-sm text-muted">Kategori dikelola superadmin, jadi daftar ini selalu mengikuti produk yang benar-benar ada.</p>
    </header>

    <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
      <NuxtLink
        v-for="category in categories"
        :key="category.id"
        :to="`/catalog?category=${category.slug}`"
        class="card flex items-center gap-4 p-5 transition hover:-translate-y-1"
      >
        <span class="grid size-12 shrink-0 place-items-center rounded-2xl bg-surface-muted text-primary dark:text-tertiary">
          <CategoryIcon :name="category.icon" size="size-6" />
        </span>
        <div class="min-w-0">
          <p class="truncate text-sm font-bold text-strong">{{ category.name }}</p>
          <p class="text-xs text-muted">{{ category.productCount ?? 0 }} produk & jasa</p>
        </div>
        <ArrowRight class="ml-auto size-4 shrink-0 text-muted" />
      </NuxtLink>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ArrowRight } from 'lucide-vue-next';
import type { Category } from '~/types';

const api = useApi();
const { data } = await useAsyncData('categories-page', () => api.get<{ categories: Category[] }>('/public/categories'));
const categories = computed(() => data.value?.categories ?? []);

useSeoMeta({ title: 'Kategori', description: 'Jelajahi kategori produk dan jasa di Mahapreneur.' });
</script>