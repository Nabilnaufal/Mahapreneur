<template>
  <div class="card p-5">
    <div class="flex flex-wrap items-end justify-between gap-2">
      <div>
        <p class="text-xs font-bold uppercase tracking-wide text-muted">{{ title }}</p>
        <p class="mt-1 text-lg font-extrabold text-strong">{{ formatRupiah(total) }}</p>
      </div>
      <p class="text-xs text-muted">7 hari terakhir</p>
    </div>

    <div class="relative mt-6 h-44">
      <svg class="absolute inset-0 size-full" viewBox="0 0 100 100" preserveAspectRatio="none" aria-hidden="true">
        <defs>
          <linearGradient id="mini-chart-area" x1="0" y1="0" x2="0" y2="1">
            <stop offset="0%" stop-color="#dbff00" stop-opacity="0.45" />
            <stop offset="100%" stop-color="#dbff00" stop-opacity="0" />
          </linearGradient>
        </defs>
        <line v-for="y in [10, 45, 80]" :key="y" x1="0" :y1="y" x2="100" :y2="y" stroke="currentColor" stroke-width="1" vector-effect="non-scaling-stroke" class="text-line" />
        <path v-if="points.length > 1" :d="areaPath" fill="url(#mini-chart-area)" />
        <path
          v-if="points.length > 1"
          :d="linePath"
          fill="none"
          stroke="#dbff00"
          stroke-width="2.5"
          stroke-linecap="round"
          stroke-linejoin="round"
          vector-effect="non-scaling-stroke"
          style="filter: drop-shadow(0 0 5px rgba(219, 255, 0, 0.55))"
        />
      </svg>

      <div
        v-for="point in points"
        :key="point.date"
        class="group absolute -translate-x-1/2 -translate-y-1/2"
        :style="{ left: `${point.x}%`, top: `${point.y}%` }"
      >
        <span class="block size-2.5 rounded-full border-2 border-tertiary bg-white transition group-hover:scale-150 dark:bg-primary" />
        <span class="pointer-events-none absolute bottom-full left-1/2 mb-2 -translate-x-1/2 whitespace-nowrap rounded-md bg-primary px-2 py-1 text-[10px] font-bold text-tertiary opacity-0 shadow transition group-hover:opacity-100">
          {{ formatRupiah(point.value) }}
        </span>
      </div>
    </div>

    <div class="mt-2 flex justify-between">
      <span v-for="point in points" :key="point.date" class="text-[10px] font-semibold uppercase text-muted">{{ point.label }}</span>
    </div>
  </div>
</template>

<script setup lang="ts">
const props = withDefaults(defineProps<{ data: { date: string; label: string; value: number }[]; title?: string }>(), {
  title: 'Penjualan',
});

const total = computed(() => props.data.reduce((sum, point) => sum + point.value, 0));
const max = computed(() => Math.max(...props.data.map((point) => point.value), 1));

/** Titik grafik: x tersebar merata, y menyisakan ruang 10% di atas dan bawah. */
const points = computed(() =>
  props.data.map((point, index) => ({
    ...point,
    x: props.data.length > 1 ? (index / (props.data.length - 1)) * 100 : 50,
    y: 90 - (point.value / max.value) * 80,
  })),
);

const linePath = computed(() => points.value.map((point, index) => `${index === 0 ? 'M' : 'L'} ${point.x},${point.y}`).join(' '));
const areaPath = computed(() => {
  const first = points.value[0];
  const last = points.value[points.value.length - 1];
  if (!first || !last) return '';
  return `${linePath.value} L ${last.x},100 L ${first.x},100 Z`;
});
</script>
