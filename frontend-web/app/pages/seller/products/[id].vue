<template>
  <div v-if="product" class="space-y-5">
    <header class="flex flex-wrap items-end justify-between gap-3">
      <div>
        <NuxtLink to="/seller/products" class="flex items-center gap-1.5 text-xs font-semibold text-muted hover:text-strong">
          <ArrowLeft class="size-3.5" /> Semua produk
        </NuxtLink>
        <h1 class="mt-2 text-xl font-extrabold text-strong">Ubah produk</h1>
        <p class="mt-1 text-sm text-muted">{{ product.name }}</p>
      </div>
      <NuxtLink :to="`/product/${product.slug}`" target="_blank" class="btn btn-outline text-xs">
        <ExternalLink class="size-3.5" /> Lihat di marketplace
      </NuxtLink>
    </header>

    <ProductForm :product="product" :categories="categories" :saving="saving" @submit="save" @remove-image="removeImage" />
  </div>
</template>

<script setup lang="ts">
import { ArrowLeft, ExternalLink } from 'lucide-vue-next';
import type { Category, Product } from '~/types';

definePageMeta({ layout: 'dashboard', requiresAuth: true, roles: ['SELLER'] });

const route = useRoute();
const api = useApi();
const ui = useUiStore();

const saving = ref(false);

const { data, refresh } = await useAsyncData(`seller-product-${route.params.id}`, () =>
  api.get<{ product: Product }>(`/seller/products/${route.params.id}`),
);

const product = computed(() => data.value?.product ?? null);
const { data: categoryData } = await useAsyncData('product-categories-edit', () => api.get<{ categories: Category[] }>('/public/categories'));
const categories = computed(() => categoryData.value?.categories ?? []);

async function save(payload: Record<string, unknown>) {
  if (!product.value) return;
  saving.value = true;
  try {
    const images = (payload.images as File[] | undefined) ?? [];
    const productPayload = { ...payload };
    delete productPayload.images;

    await api.patch('/seller/products/' + product.value.id, productPayload);

    if (images.length) {
      const body = new FormData();
      images.forEach((file) => body.append('images', file));
      await api.upload('/seller/products/' + product.value.id + '/images', body);
    }

    await refresh();
    ui.toast(images.length ? 'Produk dan foto berhasil diperbarui.' : 'Produk diperbarui.', 'success');
  } catch (error) {
    ui.toast(apiError(error, 'Gagal menyimpan produk atau mengunggah foto'), 'error');
  } finally {
    saving.value = false;
  }
}

async function removeImage(imageId: number) {
  if (!product.value) return;
  try {
    await api.del(`/seller/products/${product.value.id}/images/${imageId}`);
    await refresh();
    ui.toast('Gambar dihapus.', 'info');
  } catch (error) {
    ui.toast(apiError(error), 'error');
  }
}

useSeoMeta({ title: 'Ubah Produk' });
</script>
