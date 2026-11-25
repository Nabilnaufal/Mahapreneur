<template>
  <header class="sticky top-0 z-50 px-3 pt-3 md:px-5 md:pt-4">
    <nav
      class="page-container flex items-center gap-3 rounded-3xl border border-line bg-surface/90 px-3 py-2.5 backdrop-blur-xl md:gap-4 md:px-5 md:py-3"
      style="box-shadow: 0 12px 34px -24px rgba(10, 25, 38, 0.5)"
    >
      <NuxtLink to="/" class="flex shrink-0 items-center gap-2" aria-label="Mahapreneur">
        <span class="grid size-9 place-items-center rounded-xl bg-primary">
          <img src="/logo-mark.png" alt="" class="h-4 w-auto" />
        </span>
        <span class="hidden text-lg font-extrabold tracking-tight text-strong sm:block">Mahapreneur</span>
      </NuxtLink>

      <div class="hidden items-center gap-1 lg:flex">
        <NuxtLink
          v-for="link in links"
          :key="link.to"
          :to="link.to"
          class="rounded-full px-3.5 py-2 text-sm font-semibold text-body transition hover:bg-surface-muted hover:text-strong"
          active-class="bg-primary text-white hover:bg-primary hover:text-white dark:bg-primary-soft"
        >
          {{ link.label }}
        </NuxtLink>
      </div>

      <!-- Pencarian: selalu tampil di desktop, dipisah di mobile -->
      <form class="ml-auto hidden max-w-md flex-1 md:block" @submit.prevent="submitSearch">
        <label class="flex items-center gap-2 rounded-full border border-line bg-surface-muted px-4 py-2">
          <Search class="size-4 text-muted" />
          <input
            v-model="keyword"
            type="search"
            placeholder="Cari produk, jasa, atau toko…"
            class="w-full bg-transparent text-sm text-strong outline-none placeholder:text-muted"
            aria-label="Cari produk"
          />
        </label>
      </form>

      <div class="ml-auto flex items-center gap-1 md:ml-0">
        <button class="btn btn-ghost !px-2.5 md:hidden" aria-label="Cari" @click="ui.searchOpen = !ui.searchOpen">
          <Search class="size-5" />
        </button>

        <button
          class="btn btn-ghost !px-2.5"
          :aria-label="ui.theme === 'dark' ? 'Aktifkan mode terang' : 'Aktifkan mode gelap'"
          @click="ui.toggleTheme()"
        >
          <Sun v-if="ui.theme === 'dark'" class="size-5" />
          <Moon v-else class="size-5" />
        </button>

        <template v-if="auth.isCustomer">
          <NuxtLink to="/customer/wishlist" class="btn btn-ghost !px-2.5" aria-label="Wishlist">
            <Heart class="size-5" />
          </NuxtLink>
          <NuxtLink
            to="/customer/cart"
            class="btn btn-ghost relative !px-2.5"
            :class="{ 'cart-bump': cartBump }"
            data-cart-target
            aria-label="Keranjang"
          >
            <ShoppingBag class="size-5" />
            <span
              v-if="cart.count"
              :key="cart.count"
              class="badge-pop absolute -right-1 -top-1 grid min-w-5 place-items-center rounded-full bg-tertiary px-1 text-[11px] font-bold text-neutral"
            >
              {{ cart.count }}
            </span>
          </NuxtLink>
        </template>

        <NuxtLink
          v-else-if="!auth.isAuthenticated"
          to="/customer/cart"
          class="btn btn-ghost !px-2.5"
          data-cart-target
          aria-label="Keranjang"
        >
          <ShoppingBag class="size-5" />
        </NuxtLink>

        <template v-if="auth.isAuthenticated">
          <div v-click-outside="() => (menuOpen = false)" class="relative hidden md:block">
            <button class="btn btn-ghost gap-2 !px-2" @click="menuOpen = !menuOpen">
              <span class="grid size-8 place-items-center rounded-full bg-primary text-xs font-bold text-tertiary">
                {{ auth.initials }}
              </span>
              <ChevronDown class="hidden size-4 sm:block" />
            </button>
            <div v-if="menuOpen" class="card absolute right-0 mt-2 w-60 overflow-hidden p-2">
              <div class="border-b border-line px-3 py-2">
                <p class="truncate text-sm font-semibold text-strong">{{ auth.user?.name }}</p>
                <p class="truncate text-xs text-muted">{{ auth.user?.email }}</p>
              </div>
              <NuxtLink
                v-for="item in accountMenu"
                :key="item.to"
                :to="item.to"
                class="flex items-center gap-2.5 rounded-xl px-3 py-2 text-sm font-medium text-body hover:bg-surface-muted hover:text-strong"
                @click="menuOpen = false"
              >
                <component :is="item.icon" class="size-4" />
                {{ item.label }}
              </NuxtLink>
              <button
                class="flex w-full items-center gap-2.5 rounded-xl px-3 py-2 text-sm font-medium text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-500/10"
                @click="signOut"
              >
                <LogOut class="size-4" />
                Keluar
              </button>
            </div>
          </div>
        </template>
        <div v-else class="flex items-center gap-2">
          <button class="btn btn-ghost hidden sm:inline-flex" @click="ui.openLogin(route.fullPath)">Masuk</button>
          <NuxtLink to="/auth/register" class="btn btn-primary">Daftar</NuxtLink>
        </div>
      </div>
    </nav>

    <!-- Search bar mobile -->
    <div v-if="ui.searchOpen" class="page-container mt-2 md:hidden">
      <form class="card flex items-center gap-2 px-4 py-2.5" @submit.prevent="submitSearch">
        <Search class="size-4 text-muted" />
        <input
          v-model="keyword"
          type="search"
          placeholder="Cari produk atau toko…"
          class="w-full bg-transparent text-sm text-strong outline-none placeholder:text-muted"
          aria-label="Cari produk"
        />
      </form>
    </div>
  </header>
</template>

<script setup lang="ts">
import {
  ChevronDown, Heart, LayoutDashboard, LogOut, MessageCircle, Moon, Package, Search, ShoppingBag, Sun, User,
} from 'lucide-vue-next';
import { vClickOutside } from '~/utils/clickOutside';

const auth = useAuthStore();
const cart = useCartStore();
const ui = useUiStore();
const router = useRouter();
const route = useRoute();

const keyword = ref(typeof route.query.q === 'string' ? route.query.q : '');
const menuOpen = ref(false);
const cartBump = ref(false);

// Ikon keranjang "berdenyut" setiap kali jumlah item bertambah.
watch(
  () => cart.count,
  (next, prev) => {
    if (next <= prev) return;
    cartBump.value = true;
    setTimeout(() => (cartBump.value = false), 500);
  },
);

const links = [
  { label: 'Home', to: '/' },
  { label: 'Explore', to: '/catalog' },
  { label: 'Kategori', to: '/categories' },
  { label: 'Toko', to: '/stores' },
];

const accountMenu = computed(() => {
  if (auth.isAdmin) {
    return [
      { label: 'Dashboard Admin', to: '/admin', icon: LayoutDashboard },
      { label: 'Pengguna', to: '/admin/users', icon: User },
      { label: 'Pesanan', to: '/admin/orders', icon: Package },
    ];
  }
  if (auth.isSeller) {
    return [
      { label: 'Dashboard Seller', to: '/seller', icon: LayoutDashboard },
      { label: 'Pesan', to: '/seller/chats', icon: MessageCircle },
      { label: 'Produk Saya', to: '/seller/products', icon: Package },
      { label: 'Pesanan Masuk', to: '/seller/orders', icon: ShoppingBag },
    ];
  }
  return [
    { label: 'Pesan', to: '/chat', icon: MessageCircle },
    { label: 'Pesanan Saya', to: '/customer/orders', icon: Package },
    { label: 'Wishlist', to: '/customer/wishlist', icon: Heart },
    { label: 'Profil', to: '/customer/profile', icon: User },
  ];
});

function submitSearch() {
  const q = keyword.value.trim();
  ui.searchOpen = false;
  router.push(q ? { path: '/catalog', query: { q } } : '/catalog');
}

async function signOut() {
  menuOpen.value = false;
  cart.reset();
  await auth.logout();
  ui.toast('Kamu sudah keluar', 'info');
  router.push('/');
}

watch(() => route.fullPath, () => {
  menuOpen.value = false;
  ui.searchOpen = false;
});

watch(
  () => route.query.q,
  (value) => {
    keyword.value = typeof value === 'string' ? value : '';
  },
);
</script>
