<template>
  <div class="flex min-h-screen flex-col">
    <AppNavbar />
    <main class="page-container flex-1 py-6 pb-24 md:pb-10">
      <div class="grid gap-6 lg:grid-cols-[240px_1fr]">
        <aside class="hidden lg:block">
          <div class="card p-4">
            <div class="hidden items-center gap-3 border-b border-line pb-4 lg:flex">
              <span class="grid size-11 place-items-center rounded-full bg-primary text-sm font-bold text-tertiary">{{ auth.initials }}</span>
              <div class="min-w-0">
                <p class="truncate text-sm font-bold text-strong">{{ auth.user?.name }}</p>
                <p class="truncate text-xs text-muted">{{ auth.user?.email }}</p>
              </div>
            </div>
            <nav class="space-y-1 lg:mt-3">
              <NuxtLink
                v-for="item in items"
                :key="item.to"
                :to="item.to"
                class="flex items-center gap-2.5 rounded-xl px-3 py-2.5 text-sm font-semibold text-body hover:bg-surface-muted hover:text-strong"
                active-class="bg-primary !text-white dark:bg-primary-soft"
              >
                <component :is="item.icon" class="size-4.5" />
                {{ item.label }}
              </NuxtLink>
            </nav>
          </div>
        </aside>
        <div class="min-w-0">
          <slot />
        </div>
      </div>
    </main>
    <MobileBottomNav />
  </div>
</template>

<script setup lang="ts">
import { Heart, MapPin, MessageCircle, Package, User } from 'lucide-vue-next';

const auth = useAuthStore();

const items = [
  { label: 'Profil', to: '/customer/profile', icon: User },
  { label: 'Pesan', to: '/chat', icon: MessageCircle },
  { label: 'Pesanan', to: '/customer/orders', icon: Package },
  { label: 'Wishlist', to: '/customer/wishlist', icon: Heart },
  { label: 'Alamat', to: '/customer/addresses', icon: MapPin },
];
</script>