<template>
  <div class="grid gap-4 sm:grid-cols-[96px_1fr]">
    <div class="flex gap-2 overflow-x-auto sm:flex-col sm:overflow-visible no-scrollbar">
      <button
        v-for="(image, index) in images"
        :key="image"
        class="size-20 shrink-0 overflow-hidden rounded-2xl border-2 transition"
        :class="index === active ? 'border-primary' : 'border-transparent opacity-70 hover:opacity-100'"
        :aria-label="`Lihat gambar ${index + 1}`"
        @click="active = index"
      >
        <img :src="image" :alt="`Gambar produk ${index + 1}`" class="size-full object-cover" />
      </button>
    </div>

    <div class="relative overflow-hidden rounded-3xl border border-line bg-surface-muted">
      <img v-if="images[active]" :src="images[active]" :alt="alt" class="aspect-square w-full object-cover" />
      <div v-else class="grid aspect-square w-full place-items-center text-muted"><ImageOff class="size-10" /></div>
      <slot />
    </div>
  </div>
</template>

<script setup lang="ts">
import { ImageOff } from 'lucide-vue-next';

const props = withDefaults(defineProps<{ images: string[]; alt?: string }>(), { alt: 'Gambar produk' });
const active = ref(0);

watch(
  () => props.images,
  () => {
    active.value = 0;
  },
);
</script>