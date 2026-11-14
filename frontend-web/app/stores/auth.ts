import { defineStore } from 'pinia';
import type { Role, User } from '~/types';

export const useAuthStore = defineStore('auth', () => {
  const user = ref<User | null>(null);
  const loaded = ref(false);
  const api = useApi();

  const isAuthenticated = computed(() => Boolean(user.value));
  const isCustomer = computed(() => user.value?.role === 'CUSTOMER');
  const isSeller = computed(() => user.value?.role === 'SELLER');
  const isAdmin = computed(() => user.value?.role === 'SUPERADMIN');
  const hasStore = computed(() => Boolean(user.value?.store));
  const initials = computed(() =>
    (user.value?.name ?? '?')
      .split(' ')
      .slice(0, 2)
      .map((part) => part[0]?.toUpperCase() ?? '')
      .join(''),
  );

  /** Muat ulang sesi dari cookie httpOnly. Aman dipanggil berkali-kali. */
  async function fetchUser() {
    try {
      const data = await api.get<{ user: User }>('/auth/me');
      user.value = data.user;
    } catch {
      user.value = null;
    } finally {
      loaded.value = true;
    }
    return user.value;
  }

  async function login(email: string, password: string) {
    const data = await api.post<{ user: User }>('/auth/login', { email, password });
    user.value = data.user;
    loaded.value = true;
    return data.user;
  }

  async function logout() {
    try {
      await api.post('/auth/logout');
    } finally {
      user.value = null;
    }
  }

  /** Halaman tujuan setelah masuk, disesuaikan dengan role. */
  function homeRoute(role: Role | undefined = user.value?.role) {
    if (role === 'SUPERADMIN') return '/admin';
    if (role === 'SELLER') return '/seller';
    return '/customer/orders';
  }

  return { user, loaded, isAuthenticated, isCustomer, isSeller, isAdmin, hasStore, initials, fetchUser, login, logout, homeRoute };
});
