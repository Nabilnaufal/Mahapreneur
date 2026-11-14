<template>
  <div v-if="meta.totalPages > 1" class="mt-8 flex items-center justify-center gap-2">
    <button class="btn btn-outline !px-3" :disabled="meta.page <= 1" aria-label="Halaman sebelumnya" @click="go(meta.page - 1)">
      <ChevronLeft class="size-4" />
    </button>
    <div class="flex items-center gap-1">
      <button
        v-for="page in pages"
        :key="page"
        class="grid size-9 place-items-center rounded-full text-sm font-semibold transition"
        :class="page === meta.page ? 'bg-primary text-white' : 'text-body hover:bg-surface-muted'"
        @click="go(page)"
      >
        {{ page }}
      </button>
    </div>
    <button class="btn btn-outline !px-3" :disabled="meta.page >= meta.totalPages" aria-label="Halaman berikutnya" @click="go(meta.page + 1)">
      <ChevronRight class="size-4" />
    </button>
  </div>
</template>

<script setup lang="ts">
import { ChevronLeft, ChevronRight } from 'lucide-vue-next';
import type { PaginationMeta } from '~/types';

const props = defineProps<{ meta: PaginationMeta }>();
const emit = defineEmits<{ change: [page: number] }>();

// Jendela nomor halaman maksimal 5 buah di sekitar halaman aktif.
const pages = computed(() => {
  const total = props.meta.totalPages;
  const current = props.meta.page;
  const start = Math.max(1, Math.min(current - 2, total - 4));
  const end = Math.min(total, start + 4);
  return Array.from({ length: end - start + 1 }, (_, i) => start + i);
});

const go = (page: number) => {
  if (page >= 1 && page <= props.meta.totalPages && page !== props.meta.page) emit('change', page);
};
</script>