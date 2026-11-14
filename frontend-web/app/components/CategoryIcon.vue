<template>
  <component :is="resolved" :class="size" />
</template>

<script setup lang="ts">
import {
  Apple, Briefcase, Coffee, Laptop, Package, Shirt, UtensilsCrossed, Watch, Boxes, Store,
} from 'lucide-vue-next';

const props = withDefaults(defineProps<{ name?: string | null; size?: string }>(), { size: 'size-5' });

// Nama ikon disimpan di database sebagai string agar kategori bisa dikelola admin.
const MAP: Record<string, unknown> = {
  UtensilsCrossed, Coffee, Apple, Shirt, Watch, Laptop, Briefcase, Package, Boxes, Store,
};

const resolved = computed(() => MAP[props.name ?? ''] ?? Package);
</script>