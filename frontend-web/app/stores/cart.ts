import { defineStore } from 'pinia';
import type { CartItem } from '~/types';

export const useCartStore = defineStore('cart', () => {
  const api = useApi();
  const auth = useAuthStore();
  const ui = useUiStore();

  const items = ref<CartItem[]>([]);
  const summary = ref({ itemCount: 0, storeCount: 0, subtotal: 0 });
  const loading = ref(false);

  const count = computed(() => summary.value.itemCount);

  /** Keranjang dikelompokkan per toko karena checkout memisahkan pesanan per seller. */
  const groups = computed(() => {
    const map = new Map<number, { store: CartItem['product']['store']; items: CartItem[]; subtotal: number }>();
    for (const item of items.value) {
      const storeId = item.product.store?.id ?? 0;
      const entry = map.get(storeId) ?? { store: item.product.store, items: [], subtotal: 0 };
      entry.items.push(item);
      entry.subtotal += item.subtotal;
      map.set(storeId, entry);
    }
    return [...map.values()];
  });

  function apply(data: { items: CartItem[]; summary: typeof summary.value }) {
    items.value = data.items;
    summary.value = data.summary;
  }

  async function fetch() {
    if (!auth.isCustomer) return;
    loading.value = true;
    try {
      apply(await api.get('/customer/cart'));
    } catch {
      items.value = [];
    } finally {
      loading.value = false;
    }
  }

  async function add(productId: number, quantity = 1) {
    const data = await api.post('/customer/cart', { productId, quantity });
    apply(data as never);
    ui.toast('Ditambahkan ke keranjang', 'success');
  }

  async function update(itemId: number, quantity: number) {
    apply((await api.patch(`/customer/cart/${itemId}`, { quantity })) as never);
  }

  async function remove(itemId: number) {
    apply((await api.del(`/customer/cart/${itemId}`)) as never);
    ui.toast('Produk dihapus dari keranjang', 'info');
  }

  function reset() {
    items.value = [];
    summary.value = { itemCount: 0, storeCount: 0, subtotal: 0 };
  }

  return { items, summary, loading, count, groups, fetch, add, update, remove, reset };
});
