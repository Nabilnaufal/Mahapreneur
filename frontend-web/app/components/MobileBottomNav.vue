<template>
  <nav
    class="fixed inset-x-0 bottom-0 z-50 border-t border-line bg-surface/95 pb-[env(safe-area-inset-bottom)] backdrop-blur-xl md:hidden"
    aria-label="Navigasi utama"
  >
    <div class="grid grid-cols-5">
      <template v-for="item in items" :key="item.label">
        <NuxtLink
          v-if="item.to"
          :to="item.to"
          class="relative flex flex-col items-center gap-1 py-2.5 text-[11px] font-semibold text-muted transition"
          active-class="text-strong"
        >
          <component :is="item.icon" class="size-5" />
          {{ item.label }}
        </NuxtLink>
        <button v-else class="relative flex flex-col items-center gap-1 py-2.5 text-[11px] font-semibold text-muted transition" @click="ui.openLogin()">
          <component :is="item.icon" class="size-5" />
          {{ item.label }}
        </button>
      </template>
    </div>
  </nav>
</template>

<script setup lang="ts">
import { Compass, Heart, Home, Package, User } from 'lucide-vue-next';

const auth = useAuthStore();
const ui = useUiStore();

const items = computed(() => [
  { label: 'Home', to: '/', icon: Home },
  { label: 'Explore', to: '/catalog', icon: Compass },
  { label: 'Wishlist', to: '/customer/wishlist', icon: Heart },
  { label: 'Pesanan', to: '/customer/orders', icon: Package },
  { label: 'Profil', to: auth.isAuthenticated ? '/customer/profile' : '', icon: User },
]);
</script>
