<template>
  <div class="space-y-5">
    <header>
      <h1 class="text-xl font-extrabold text-strong">Tambah produk baru</h1>
      <p class="mt-1 text-sm text-muted">Lengkapi informasi produk dan tambahkan hingga 5 foto sekaligus.</p>
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
    const images = (payload.images as File[] | undefined) ?? [];
    const productPayload = { ...payload };
    delete productPayload.images;

    const result = await api.post<{ product: Product }>('/seller/products', productPayload);

    if (images.length) {
      try {
        const body = new FormData();
        images.forEach((file) => body.append('images[]', file));
        await api.upload('/seller/products/' + result.product.id + '/images', body);
      } catch (error) {
        ui.toast(`Produk berhasil dibuat, tetapi foto gagal diunggah: ${apiError(error, 'silakan coba dari halaman edit')}`, 'error');
        router.push('/seller/products/' + result.product.id);
        return;
      }
    }

    ui.toast(images.length ? 'Produk dan gambar berhasil ditambahkan.' : 'Produk berhasil ditambahkan.', 'success');
    router.push(`/seller/products/${result.product.id}`);
  } catch (error) {
    ui.toast(apiError(error, 'Gagal membuat produk atau mengunggah gambar'), 'error');
  } finally {
    saving.value = false;
  }
}

useSeoMeta({ title: 'Tambah Produk' });
</script>
