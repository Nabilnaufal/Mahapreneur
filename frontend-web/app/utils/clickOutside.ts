import type { Directive } from 'vue';

/** Tutup dropdown saat pengguna mengeklik di luar elemennya. */
export const vClickOutside: Directive<HTMLElement, () => void> = {
  mounted(el, binding) {
    el.dataset.clickOutside = 'true';
    const handler = (event: MouseEvent) => {
      if (!el.contains(event.target as Node)) binding.value();
    };
    (el as HTMLElement & { __clickOutside?: (e: MouseEvent) => void }).__clickOutside = handler;
    document.addEventListener('click', handler);
  },
  unmounted(el) {
    const handler = (el as HTMLElement & { __clickOutside?: (e: MouseEvent) => void }).__clickOutside;
    if (handler) document.removeEventListener('click', handler);
  },
};
