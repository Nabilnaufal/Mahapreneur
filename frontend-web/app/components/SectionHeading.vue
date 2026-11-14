<template>
  <div class="mb-5 flex flex-wrap items-end justify-between gap-3">
    <div>
      <p v-if="eyebrow" class="text-[11px] font-bold uppercase tracking-[0.14em] text-muted">{{ eyebrow }}</p>
      <h2 class="mt-1 text-xl font-extrabold text-strong sm:text-2xl">{{ title }}</h2>
      <p v-if="subtitle" class="mt-1 max-w-2xl text-sm text-muted">{{ subtitle }}</p>
    </div>
    <NuxtLink v-if="to" :to="to" class="btn btn-outline !py-2 text-xs">
      {{ linkLabel }}
      <ArrowRight class="size-3.5" />
    </NuxtLink>
  </div>
</template>

<script setup lang="ts">
import { ArrowRight } from 'lucide-vue-next';

withDefaults(
  defineProps<{ title: string; subtitle?: string; eyebrow?: string; to?: string; linkLabel?: string }>(),
  { linkLabel: 'Lihat semua' },
);
</script>