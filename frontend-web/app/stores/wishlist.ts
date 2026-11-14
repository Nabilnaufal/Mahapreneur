import { defineStore } from 'pinia';
import type { Product } from '~/types';

export const useWishlistStore = defineStore('wishlist', () => {
  const api = useApi();
  const auth = useAuthStore();
  const ui = useUiStore();

  const items = ref<{ id: number; product: Product }[]>([]);
  const ids = ref<number[]>([]);

  const has = (productId: number) => ids.value.includes(productId);

  async function fetch() {
    if (!auth.isCustomer) {
      items.value = [];
      ids.value = [];
      return;
    }
    try {
      const data = await api.get<{ items: { id: number; product: Product }[] }>('/customer/wishlist');
      items.value = data.items;
      ids.value = data.items.map((item) => item.product.id);
    } catch {
      items.value = [];
      ids.value = [];
    }
  }

  async function toggle(productId: number) {
    if (has(productId)) {
      await api.del(`/customer/wishlist/${productId}`);
      ids.value = ids.value.filter((id) => id !== productId);
      items.value = items.value.filter((item) => item.product.id !== productId);
      ui.toast('Dihapus dari wishlist', 'info');
    } else {
      await api.post('/customer/wishlist', { productId });
      ids.value = [...ids.value, productId];
      ui.toast('Ditambahkan ke wishlist', 'success');
    }
  }

  function reset() {
    items.value = [];
    ids.value = [];
  }

  return { items, ids, has, fetch, toggle, reset };
});