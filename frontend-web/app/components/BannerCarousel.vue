<template>
  <section
    class="relative touch-pan-y overflow-hidden rounded-3xl"
    aria-roledescription="carousel"
    aria-label="Promo Mahapreneur"
    @touchstart.passive="onTouchStart"
    @touchend="onTouchEnd"
  >
    <div class="relative h-[360px] sm:h-[460px] lg:h-[540px]">
      <TransitionGroup
        enter-active-class="transition-opacity duration-700"
        enter-from-class="opacity-0"
        leave-active-class="transition-opacity duration-700 absolute inset-0"
        leave-to-class="opacity-0"
      >
        <div v-for="(banner, index) in banners" v-show="index === active" :key="banner.id" class="absolute inset-0">
          <img :src="banner.image" :alt="banner.title" class="size-full object-cover" />
          <div class="absolute inset-0 bg-gradient-to-r from-primary via-primary/85 to-primary/30" />
          <div class="absolute inset-0 flex items-center">
            <div class="max-w-2xl px-6 sm:px-10">
              <p class="badge bg-tertiary text-neutral">Mahapreneur</p>
              <h2 class="mt-4 text-2xl font-extrabold leading-tight text-white sm:text-4xl lg:text-5xl">{{ banner.title }}</h2>
              <p class="mt-3 max-w-xl text-sm leading-relaxed text-white/75 sm:text-base">{{ banner.description }}</p>
              <NuxtLink v-if="banner.ctaUrl" :to="banner.ctaUrl" class="btn btn-tertiary mt-6">
                {{ banner.ctaLabel || 'Lihat selengkapnya' }}
                <ArrowRight class="size-4" />
              </NuxtLink>
            </div>
          </div>
        </div>
      </TransitionGroup>
    </div>

    <div v-if="banners.length > 1" class="absolute bottom-5 left-1/2 flex -translate-x-1/2 items-center gap-1.5">
      <button
        v-for="(banner, index) in banners"
        :key="banner.id"
        class="h-1.5 rounded-full transition-all"
        :class="index === active ? 'w-6 bg-tertiary' : 'w-1.5 bg-white/50'"
        :aria-label="`Banner ${index + 1}`"
        @click="go(index)"
      />
    </div>
  </section>
</template>

<script setup lang="ts">
import { ArrowRight } from 'lucide-vue-next';
import type { Banner } from '~/types';

const props = defineProps<{ banners: Banner[] }>();

const active = ref(0);
let timer: ReturnType<typeof setInterval> | null = null;

function go(index: number) {
  const total = props.banners.length;
  if (!total) return;
  active.value = (index + total) % total;
}

// Navigasi banner memakai gesture geser (tidak ada tombol panah).
const SWIPE_THRESHOLD = 40;
let startX = 0;
let startY = 0;

function onTouchStart(event: TouchEvent) {
  const touch = event.touches[0];
  if (!touch) return;
  startX = touch.clientX;
  startY = touch.clientY;
}

function onTouchEnd(event: TouchEvent) {
  const touch = event.changedTouches[0];
  if (!touch) return;
  const dx = touch.clientX - startX;
  const dy = touch.clientY - startY;
  // Abaikan gerakan vertikal agar scroll halaman tetap nyaman.
  if (Math.abs(dx) < SWIPE_THRESHOLD || Math.abs(dx) < Math.abs(dy)) return;
  go(active.value + (dx < 0 ? 1 : -1));
  start();
}

function start() {
  stop();
  if (props.banners.length < 2) return;
  timer = setInterval(() => go(active.value + 1), 6000);
}
function stop() {
  if (timer) clearInterval(timer);
  timer = null;
}

onMounted(start);
onUnmounted(stop);
watch(() => props.banners.length, start);
</script>