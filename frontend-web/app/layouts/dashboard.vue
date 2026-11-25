<template>
  <div class="min-h-screen lg:flex">
    <!-- Sidebar desktop -->
    <aside class="hidden w-72 shrink-0 border-r border-line bg-surface lg:flex lg:flex-col">
      <div class="flex items-center gap-2 px-6 py-6">
        <span class="grid size-9 place-items-center rounded-xl bg-primary"><img src="/logo-mark.png" alt="" class="h-4 w-auto" /></span>
        <div>
          <p class="text-sm font-extrabold text-strong">Mahapreneur</p>
          <p class="text-[11px] font-semibold uppercase tracking-wide text-muted">{{ roleLabel }}</p>
        </div>
      </div>
      <nav class="flex-1 space-y-1 px-3 py-2">
        <NuxtLink
          v-for="item in navItems"
          :key="item.to"
          :to="item.to"
          class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold text-body transition hover:bg-surface-muted hover:text-strong"
          active-class="bg-primary !text-white dark:bg-primary-soft"
        >
          <component :is="item.icon" class="size-4.5" />
          {{ item.label }}
        </NuxtLink>
      </nav>
      <div class="border-t border-line p-3">
        <NuxtLink to="/" class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold text-muted hover:bg-surface-muted hover:text-strong">
          <ArrowLeft class="size-4.5" /> Kembali ke marketplace
        </NuxtLink>
      </div>
    </aside>

    <div class="flex min-w-0 flex-1 flex-col">
      <!-- Top bar -->
      <header class="sticky top-0 z-40 flex items-center gap-3 border-b border-line bg-surface/90 px-4 py-3 backdrop-blur-xl lg:px-8">
        <button class="btn btn-ghost !px-2 lg:hidden" aria-label="Buka menu" @click="drawerOpen = true">
          <Menu class="size-5" />
        </button>
        <div class="min-w-0">
          <p class="truncate text-base font-bold text-strong">{{ pageTitle }}</p>
          <p class="truncate text-xs text-muted">{{ auth.user?.name }} · {{ auth.user?.email }}</p>
        </div>

        <div class="ml-auto flex items-center gap-1.5">
          <NuxtLink to="/notifications" class="btn btn-ghost relative !px-2.5" aria-label="Notifikasi">
            <Bell class="size-5" />
            <span v-if="unread" class="absolute -right-0.5 -top-0.5 grid min-w-4 place-items-center rounded-full bg-tertiary px-1 text-[10px] font-bold text-neutral">
              {{ unread }}
            </span>
          </NuxtLink>
          <button class="btn btn-ghost !px-2.5" aria-label="Ganti tema" @click="ui.toggleTheme()">
            <Sun v-if="ui.theme === 'dark'" class="size-5" />
            <Moon v-else class="size-5" />
          </button>
          <NuxtLink to="/" class="btn btn-outline hidden !px-3 sm:inline-flex lg:hidden">Marketplace</NuxtLink>
        </div>
      </header>

      <main class="flex-1 px-4 py-6 lg:px-8">
        <slot />
      </main>
    </div>

    <!-- Drawer mobile -->
    <Transition
      enter-active-class="transition-opacity duration-200"
      leave-active-class="transition-opacity duration-150"
      enter-from-class="opacity-0"
      leave-to-class="opacity-0"
    >
      <div v-if="drawerOpen" class="fixed inset-0 z-50 lg:hidden">
        <div class="absolute inset-0 bg-neutral/50 backdrop-blur-sm" @click="drawerOpen = false" />
        <div class="absolute inset-y-0 left-0 w-72 max-w-[80%] overflow-y-auto bg-surface p-4">
          <div class="mb-4 flex items-center justify-between">
            <p class="text-sm font-extrabold text-strong">{{ roleLabel }}</p>
            <button class="btn btn-ghost !px-2" aria-label="Tutup menu" @click="drawerOpen = false"><X class="size-5" /></button>
          </div>
          <nav class="space-y-1">
            <NuxtLink
              v-for="item in navItems"
              :key="item.to"
              :to="item.to"
              class="flex items-center gap-3 rounded-xl px-3 py-3 text-sm font-semibold text-body hover:bg-surface-muted"
              active-class="bg-primary !text-white"
              @click="drawerOpen = false"
            >
              <component :is="item.icon" class="size-5" />
              {{ item.label }}
            </NuxtLink>
            <NuxtLink to="/" class="flex items-center gap-3 rounded-xl px-3 py-3 text-sm font-semibold text-muted" @click="drawerOpen = false">
              <ArrowLeft class="size-5" /> Kembali ke marketplace
            </NuxtLink>
          </nav>
        </div>
      </div>
    </Transition>
  </div>
</template>

<script setup lang="ts">
import {
  ArrowLeft, BarChart3, Bell, Boxes, Headphones, LayoutDashboard, Menu, MessageCircle, MessageSquare, Moon, Package, ShoppingBag, Store, Sun, Tag, Users, Wallet, X, Image as ImageIcon,
} from 'lucide-vue-next';
import type { Notification } from '~/types';

const auth = useAuthStore();
const ui = useUiStore();
const route = useRoute();
const api = useApi();

const drawerOpen = ref(false);
const unread = ref(0);

const sellerNav = [
  { label: 'Dashboard', to: '/seller', icon: LayoutDashboard },
  { label: 'Pesan', to: '/seller/chats', icon: MessageCircle },
  { label: 'Toko Saya', to: '/seller/store', icon: Store },
  { label: 'Produk', to: '/seller/products', icon: Boxes },
  { label: 'Pesanan Masuk', to: '/seller/orders', icon: ShoppingBag },
  { label: 'Analytics', to: '/seller/analytics', icon: BarChart3 },
  { label: 'Beri masukan', to: '/seller/feedback', icon: MessageSquare },
];

const adminNav = [
  { label: 'Dashboard', to: '/admin', icon: LayoutDashboard },
  { label: 'Pengguna', to: '/admin/users', icon: Users },
  { label: 'Kategori', to: '/admin/categories', icon: Tag },
  { label: 'Toko', to: '/admin/stores', icon: Store },
  { label: 'Produk', to: '/admin/products', icon: Boxes },
  { label: 'Pesanan', to: '/admin/orders', icon: ShoppingBag },
  { label: 'Pembayaran', to: '/admin/payments', icon: Wallet },
  { label: 'Banner', to: '/admin/banners', icon: ImageIcon },
  { label: 'Aktivitas', to: '/admin/activity', icon: Package },
  { label: 'Saran Pengguna', to: '/admin/feedback', icon: MessageSquare },
  { label: 'Customer Service', to: '/admin/customer-service', icon: Headphones },
];

const navItems = computed(() => (auth.isAdmin ? adminNav : sellerNav));
const roleLabel = computed(() => (auth.isAdmin ? 'Panel Superadmin' : 'Panel Seller'));

const titles: Record<string, string> = {
  '/seller': 'Dashboard Seller',
  '/seller/chats': 'Pesan Masuk',
  '/seller/store': 'Pengaturan Toko',
  '/seller/products': 'Manajemen Produk',
  '/seller/products/new': 'Tambah Produk',
  '/seller/orders': 'Pesanan Masuk',
  '/seller/analytics': 'Analytics Toko',
  '/seller/feedback': 'Beri Masukan',
  '/admin': 'Dashboard Superadmin',
  '/admin/users': 'Manajemen Pengguna',
  '/admin/categories': 'Manajemen Kategori',
  '/admin/stores': 'Monitoring Toko',
  '/admin/products': 'Monitoring Produk',
  '/admin/orders': 'Semua Pesanan',
  '/admin/payments': 'Transaksi & Pembayaran',
  '/admin/banners': 'Banner Homepage',
  '/admin/activity': 'Riwayat Aktivitas',
  '/admin/feedback': 'Saran Pengguna',
  '/admin/customer-service': 'Customer Service',
};

const pageTitle = computed(() => titles[route.path] ?? 'Dashboard');

onMounted(async () => {
  try {
    const data = await api.get<{ notifications: Notification[]; unread: number }>('/notifications');
    unread.value = data.unread;
  } catch {
    unread.value = 0;
  }
});
</script>
