<template>
  <div class="flex flex-col gap-4 sm:grid sm:grid-cols-[96px_1fr]">
    <div class="order-2 flex gap-2 overflow-x-auto no-scrollbar sm:order-1 sm:flex-col sm:overflow-visible">
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

    <div
      class="relative order-1 overflow-hidden rounded-3xl border border-line bg-surface-muted sm:order-2"
      @touchstart.passive="onTouchStart"
      @touchend.passive="onTouchEnd"
    >
      <div v-if="images.length" class="flex aspect-square w-full select-none transition-transform duration-300 ease-out" :style="trackStyle">
        <img
          v-for="(image, index) in images"
          :key="image"
          :src="image"
          :alt="`${alt} ${index + 1}`"
          class="h-full w-full shrink-0 object-cover"
          draggable="false"
        />
      </div>
      <div v-else class="grid aspect-square w-full place-items-center text-muted"><ImageOff class="size-10" /></div>

      <template v-if="images.length > 1">
        <button
          type="button"
          class="absolute left-3 top-1/2 grid size-9 -translate-y-1/2 place-items-center rounded-full bg-neutral/70 text-white shadow-sm transition hover:bg-neutral/90"
          aria-label="Gambar sebelumnya"
          @click="changeImage(-1)"
        >
          <ChevronLeft class="size-4" />
        </button>
        <button
          type="button"
          class="absolute right-3 top-1/2 grid size-9 -translate-y-1/2 place-items-center rounded-full bg-neutral/70 text-white shadow-sm transition hover:bg-neutral/90"
          aria-label="Gambar berikutnya"
          @click="changeImage(1)"
        >
          <ChevronRight class="size-4" />
        </button>
        <span class="absolute bottom-3 left-1/2 -translate-x-1/2 rounded-full bg-neutral/70 px-2.5 py-1 text-[11px] font-semibold text-white">
          {{ active + 1 }} / {{ images.length }}
        </span>
      </template>
      <slot />
    </div>
  </div>
</template>

<script setup lang="ts">
import { ChevronLeft, ChevronRight, ImageOff } from 'lucide-vue-next';

const props = withDefaults(defineProps<{ images: string[]; alt?: string }>(), { alt: 'Gambar produk' });
const active = ref(0);
const touchStartX = ref<number | null>(null);
const trackStyle = computed(() => ({ transform: `translateX(-${active.value * 100}%)` }));

function changeImage(direction: number) {
  if (!props.images.length) return;
  active.value = (active.value + direction + props.images.length) % props.images.length;
}

function onTouchStart(event: TouchEvent) {
  touchStartX.value = event.changedTouches[0]?.clientX ?? null;
}

function onTouchEnd(event: TouchEvent) {
  if (touchStartX.value === null) return;
  const endX = event.changedTouches[0]?.clientX ?? touchStartX.value;
  const distance = endX - touchStartX.value;
  touchStartX.value = null;

  if (Math.abs(distance) < 40) return;
  changeImage(distance < 0 ? 1 : -1);
}

watch(
  () => props.images,
  () => {
    active.value = 0;
  },
);
</script>
