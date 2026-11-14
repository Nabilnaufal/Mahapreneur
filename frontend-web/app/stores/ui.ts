import { defineStore } from 'pinia';

type Toast = { id: number; message: string; type: 'success' | 'error' | 'info' };

/** Tema (light/dark) + toast global. Preferensi tema disimpan di localStorage. */
export const useUiStore = defineStore('ui', () => {
  const theme = ref<'light' | 'dark'>('light');
  const toasts = ref<Toast[]>([]);
  const mobileMenuOpen = ref(false);
  const searchOpen = ref(false);
  const loginModalOpen = ref(false);
  const loginRedirect = ref('');

  function openLogin(redirect = '') {
    loginRedirect.value = redirect;
    loginModalOpen.value = true;
  }

  function closeLogin() {
    loginModalOpen.value = false;
  }

  function applyTheme(next: 'light' | 'dark') {
    theme.value = next;
    if (import.meta.client) {
      document.documentElement.classList.toggle('dark', next === 'dark');
      localStorage.setItem('mahapreneur-theme', next);
      document.documentElement.style.colorScheme = next;
    }
  }

  function initTheme() {
    if (!import.meta.client) return;
    const saved = localStorage.getItem('mahapreneur-theme');
    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
    applyTheme(saved === 'dark' || (!saved && prefersDark) ? 'dark' : 'light');
  }

  const toggleTheme = () => applyTheme(theme.value === 'dark' ? 'light' : 'dark');

  function toast(message: string, type: Toast['type'] = 'info') {
    const id = Date.now() + Math.random();
    toasts.value.push({ id, message, type });
    setTimeout(() => {
      toasts.value = toasts.value.filter((t) => t.id !== id);
    }, 4200);
  }

  const dismissToast = (id: number) => {
    toasts.value = toasts.value.filter((t) => t.id !== id);
  };

  return { theme, toasts, mobileMenuOpen, searchOpen, loginModalOpen, loginRedirect, openLogin, closeLogin, initTheme, toggleTheme, applyTheme, toast, dismissToast };
});
