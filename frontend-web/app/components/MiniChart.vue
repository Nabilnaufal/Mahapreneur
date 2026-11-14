<template>
  <div class="card p-5">
    <div class="flex flex-wrap items-end justify-between gap-2">
      <div>
        <p class="text-xs font-bold uppercase tracking-wide text-muted">{{ title }}</p>
        <p class="mt-1 text-lg font-extrabold text-strong">{{ formatRupiah(total) }}</p>
      </div>
      <p class="text-xs text-muted">7 hari terakhir</p>
    </div>

    <div class="mt-5 flex h-44 items-end gap-2">
      <div v-for="point in data" :key="point.date" class="group flex h-full flex-1 flex-col items-center justify-end gap-2">
        <span class="text-[10px] font-semibold text-muted opacity-0 transition group-hover:opacity-100">
          {{ formatRupiah(point.value) }}
        </span>
        <div class="flex w-full flex-1 items-end">
          <div
            class="w-full rounded-t-lg bg-primary/85 transition-all duration-500 dark:bg-primary-soft"
            :style="{ height: `${height(point.value)}%` }"
            :title="`${point.label}: ${formatRupiah(point.value)}`"
          />
        </div>
        <span class="text-[10px] font-semibold uppercase text-muted">{{ point.label }}</span>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
const props = withDefaults(defineProps<{ data: { date: string; label: string; value: number }[]; title?: string }>(), {
  title: 'Penjualan',
});

const total = computed(() => props.data.reduce((sum, point) => sum + point.value, 0));
const max = computed(() => Math.max(...props.data.map((point) => point.value), 1));

/** Tinggi batang minimal 4% agar hari tanpa penjualan tetap terlihat. */
const height = (value: number) => Math.max(4, Math.round((value / max.value) * 100));
</script>