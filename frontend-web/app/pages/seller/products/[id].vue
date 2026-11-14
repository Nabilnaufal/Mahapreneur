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

    <!-- Gambar -->
    <section class="card p-5">
      <div class="flex flex-wrap items-center justify-between gap-2">
        <div>
          <h2 class="text-sm font-bold text-strong">Gambar produk</h2>
          <p class="mt-0.5 text-xs text-muted">Maksimal 5 gambar. Gambar pertama menjadi thumbnail.</p>
        </div>
        <label class="btn btn-outline cursor-pointer !py-2 text-xs">
          <Upload class="size-3.5" /> Unggah gambar
          <input type="file" accept="image/*" multiple class="hidden" @change="uploadImages" />
        </label>
      </div>

      <div class="mt-4 flex flex-wrap gap-3">
        <div v-for="(image, index) in images" :key="index" class="group relative size-24 overflow-hidden rounded-2xl border border-line">
          <img :src="image.url" :alt="`Gambar ${index + 1}`" class="size-full object-cover" />
          <span v-if="index === 0" class="badge absolute left-1.5 top-1.5 bg-tertiary text-neutral">Utama</span>
          <button
            class="absolute inset-0 grid place-items-center bg-neutral/60 text-white opacity-0 transition group-hover:opacity-100"
            aria-label="Hapus gambar"
            @click="removeImage(image.id)"
          >
            <Trash2 class="size-5" />
          </button>
        </div>
        <div v-if="!images.length" class="grid size-24 place-items-center rounded-2xl border border-dashed border-line-strong text-muted">
          <ImageIcon class="size-6" />
        </div>
      </div>
    </section>

    <ProductForm :product="product" :categories="categories" :saving="saving" @submit="save" />
  </div>
</template>

<script setup lang="ts">
import { ArrowLeft, ExternalLink, Image as ImageIcon, Trash2, Upload } from 'lucide-vue-next';
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
const images = computed(() => product.value?.imageRecords ?? []);

const { data: categoryData } = await useAsyncData('product-categories-edit', () => api.get<{ categories: Category[] }>('/public/categories'));
const categories = computed(() => categoryData.value?.categories ?? []);

async function save(payload: Record<string, unknown>) {
  if (!product.value) return;
  saving.value = true;
  try {
    await api.patch(`/seller/products/${product.value.id}`, payload);
    await refresh();
    ui.toast('Produk diperbarui.', 'success');
  } catch (error) {
    ui.toast(apiError(error, 'Gagal menyimpan produk'), 'error');
  } finally {
    saving.value = false;
  }
}

async function uploadImages(event: Event) {
  const input = event.target as HTMLInputElement;
  const files = Array.from(input.files ?? []);
  if (!files.length || !product.value) return;
  const body = new FormData();
  for (const file of files) body.append('images', file);
  try {
    await api.upload(`/seller/products/${product.value.id}/images`, body);
    await refresh();
    ui.toast('Gambar diunggah.', 'success');
  } catch (error) {
    ui.toast(apiError(error, 'Gagal mengunggah gambar'), 'error');
  } finally {
    input.value = '';
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