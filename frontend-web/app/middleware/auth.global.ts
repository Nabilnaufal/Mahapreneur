import type { Role } from '~/types';

/**
 * Middleware global: memuat sesi sekali, lalu menegakkan aturan akses berbasis
 * role di sisi klien. Backend tetap memverifikasi ulang setiap request —
 * middleware ini hanya untuk pengalaman navigasi.
 */
export default defineNuxtRouteMiddleware(async (to) => {
  const auth = useAuthStore();
  if (!auth.loaded) await auth.fetchUser();

  const roles = to.meta.roles as Role[] | undefined;

  if (to.meta.requiresAuth && !auth.isAuthenticated) {
    return navigateTo(`/auth/login?redirect=${encodeURIComponent(to.fullPath)}`);
  }
  if (roles?.length && auth.user && !roles.includes(auth.user.role)) {
    return navigateTo(auth.homeRoute());
  }
  if (to.meta.guestOnly && auth.isAuthenticated) {
    return navigateTo(auth.homeRoute());
  }
});
