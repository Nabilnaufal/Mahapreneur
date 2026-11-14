<template>
  <div class="space-y-5">
    <header>
      <h1 class="text-xl font-extrabold text-strong">Tambah produk baru</h1>
      <p class="mt-1 text-sm text-muted">Setelah produk dibuat, kamu bisa mengunggah hingga 5 gambar di halaman edit.</p>
    </header>

    <ProductForm :categories="categories" :saving="saving" @submit="create" />
  </div>
</template>

<script setup lang="ts">
import type { Category, Product } from '~/types';

definePageMeta({ layout: 'dashboard', requiresAuth: true, roles: ['SELLER'] });

const api = useApi();
const ui = useUiStore();
const router = useRouter();

const saving = ref(false);

const { data } = await useAsyncData('product-categories', () => api.get<{ categories: Category[] }>('/public/categories'));
const categories = computed(() => data.value?.categories ?? []);

async function create(payload: Record<string, unknown>) {
  saving.value = true;
  try {
    const result = await api.post<{ product: Product }>('/seller/products', payload);
    ui.toast('Produk dibuat. Sekarang unggah gambarnya.', 'success');
    router.push(`/seller/products/${result.product.id}`);
  } catch (error) {
    ui.toast(apiError(error, 'Gagal membuat produk'), 'error');
  } finally {
    saving.value = false;
  }
}

useSeoMeta({ title: 'Tambah Produk' });
</script>