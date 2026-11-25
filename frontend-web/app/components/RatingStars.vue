<template>
  <span class="inline-flex items-center gap-1" :aria-label="label">
    <span class="inline-flex items-center gap-0.5" :class="colorClass">
      <Star
        v-for="star in 5"
        :key="star"
        :class="size"
        :fill="rating && star <= Math.round(rating) ? 'currentColor' : 'none'"
        stroke-width="1.8"
      />
    </span>
    <span v-if="showValue" class="font-bold text-strong">({{ ratingLabel }})</span>
    <span v-if="showCount && count > 0" class="text-muted">· {{ count }} ulasan</span>
  </span>
</template>

<script setup lang="ts">
import { Star } from 'lucide-vue-next';

const props = withDefaults(
  defineProps<{
    rating?: number | null;
    count?: number;
    size?: string;
    showValue?: boolean;
    showCount?: boolean;
  }>(),
  {
    rating: null,
    count: 0,
    size: 'size-3.5',
    showValue: true,
    showCount: false,
  },
);

const colorClass = computed(() => (props.rating ? 'text-amber-400' : 'text-slate-300 dark:text-slate-600'));
const ratingLabel = computed(() => (props.rating ? props.rating.toFixed(1) : '-'));
const label = computed(() => (props.rating ? 'Rating ' + ratingLabel.value + ' dari 5' : 'Belum ada rating'));
</script>
