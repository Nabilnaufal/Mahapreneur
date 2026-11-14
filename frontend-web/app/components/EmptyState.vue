<template>
  <div class="card grid place-items-center gap-3 px-6 py-14 text-center">
    <span class="grid size-12 place-items-center rounded-2xl bg-surface-muted text-muted">
      <PackageOpen class="size-6" />
    </span>
    <div>
      <p class="text-base font-bold text-strong">{{ title }}</p>
      <p class="mx-auto mt-1 max-w-sm text-sm text-muted">{{ description }}</p>
    </div>
    <slot />
  </div>
</template>

<script setup lang="ts">
import { PackageOpen } from 'lucide-vue-next';

withDefaults(defineProps<{ title: string; description?: string }>(), {
  description: 'Belum ada data untuk ditampilkan di sini.',
});
</script>