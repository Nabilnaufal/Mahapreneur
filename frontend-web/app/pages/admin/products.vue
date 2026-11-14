<template>
  <div class="space-y-5">
    <header>
      <h1 class="text-xl font-extrabold text-strong">Monitoring produk</h1>
      <p class="mt-1 text-sm text-muted">{{ meta.total }} produk dari seluruh toko. Hapus produk yang melanggar ketentuan.</p>
    </header>

    <div class="card flex flex-wrap items-center gap-3 p-4">
      <label class="flex min-w-56 flex-1 items-center gap-2 rounded-full border border-line bg-surface-muted px-4 py-2">
        <Search class="size-4 text-muted" />
        <input v-model="keyword" type="search" placeholder="Cari nama produk…" class="w-full bg-transparent text-sm text-strong outline-none" @keyup.enter="apply" />
      </label>
      <select v-model="status" class="input !w-auto !py-2 text-xs">
        <option value="">Semua status</option>
        <option value="ACTIVE">Aktif</option>
        <option value="INACTIVE">Nonaktif</option>
      </select>
      <button class="btn btn-outline text-xs" @click="apply">Terapkan</button>
    </div>

    <div v-if="products.length" class="card overflow-hidden">
      <div class="hidden grid-cols-[1fr_1fr_120px_90px_100px_120px] gap-3 border-b border-line px-5 py-3 text-[11px] font-bold uppercase tracking-wide text-muted lg:grid">
        <span>Produk</span><span>Toko</span><span>Harga</span><span>Stok</span><span>Status</span><span class="text-right">Aksi</span>
      </div>
      <div class="divide-y divide-[color:var(--line)]">
        <div v-for="product in products" :key="product.id" class="grid gap-3 px-5 py-4 lg:grid-cols-[1fr_1fr_120px_90px_100px_120px] lg:items-center">
          <div class="flex min-w-0 items-center gap-3">
            <span class="size-12 shrink-0 overflow-hidden rounded-xl bg-surface-muted">
              <img v-if="product.image" :src="product.image" :alt="product.name" class="size-full object-cover" />
            </span>
            <div class="min-w-0">
              <NuxtLink :to="`/product/${product.slug}`" target="_blank" class="line-clamp-1 text-sm font-semibold text-strong hover:underline">{{ product.name }}</NuxtLink>
              <p class="text-[11px] text-muted">{{ product.category?.name }} · {{ product.type === 'SERVICE' ? 'Jasa' : 'Barang' }}</p>
            </div>
          </div>
          <div class="min-w-0 text-xs">
            <NuxtLink v-if="product.store" :to="`/store/${product.store.slug}`" target="_blank" class="truncate font-semibold text-body hover:underline">{{ product.store.name }}</NuxtLink>
            <span v-else class="text-muted">—</span>
          </div>
          <span class="text-sm font-bold text-strong">{{ formatRupiah(product.price) }}</span>
          <span class="text-sm" :class="product.stock === 0 ? 'font-semibold text-rose-600' : 'text-body'">{{ product.stock }}</span>
          <span class="badge w-fit" :class="product.status === 'ACTIVE' ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-500/15 dark:text-emerald-300' : 'bg-slate-200 text-slate-700 dark:bg-slate-500/20 dark:text-slate-300'">
            {{ product.status === 'ACTIVE' ? 'Aktif' : 'Nonaktif' }}
          </span>
          <div class="flex justify-end">
            <button class="btn btn-ghost !px-2.5 !py-1.5 text-xs text-rose-600" aria-label="Hapus produk" @click="pendingDelete = product"><Trash2 class="size-3.5" /></button>
          </div>
        </div>
      </div>
    </div>

    <EmptyState v-else title="Tidak ada produk" description="Ubah filter atau kata kunci untuk menemukan produk lain." />

    <PaginationBar :meta="meta" @change="changePage" />

    <ConfirmDialog
      :open="Boolean(pendingDelete)"
      title="Hapus produk?"
      :message="pendingDelete ? `${pendingDelete.name} dari toko ${pendingDelete.store?.name ?? ''} akan dihapus permanen dan tindakan ini tercatat di log aktivitas.` : ''"
      confirm-label="Hapus produk"
      danger
      :loading="deleting"
      @confirm="removeProduct"
      @cancel="pendingDelete = null"
    />
  </div>
</template>

<script setup lang="ts">
import { Search, Trash2 } from 'lucide-vue-next';
import type { PaginationMeta, Product } from '~/types';

definePageMeta({ layout: 'dashboard', requiresAuth: true, roles: ['SUPERADMIN'] });

const api = useApi();
const ui = useUiStore();

const keyword = ref('');
const status = ref('');
const page = ref(1);
const pendingDelete = ref<Product | null>(null);
const deleting = ref(false);

const query = computed(() => ({
  q: keyword.value || undefined,
  status: status.value || undefined,
  page: page.value,
  limit: 10,
}));

const { data, refresh } = await useAsyncData('admin-products', () => api.get<{ products: Product[]; meta: PaginationMeta }>('/admin/products', query.value), {
  watch: [query],
});

const products = computed(() => data.value?.products ?? []);
const meta = computed(() => data.value?.meta ?? { total: 0, page: 1, limit: 10, totalPages: 1 });

function apply() {
  page.value = 1;
  refresh();
}

function changePage(next: number) {
  page.value = next;
}

async function removeProduct() {
  if (!pendingDelete.value) return;
  deleting.value = true;
  try {
    await api.del(`/admin/products/${pendingDelete.value.id}`, { reason: 'Dihapus admin karena melanggar ketentuan' });
    ui.toast('Produk dihapus.', 'success');
    await refresh();
  } catch (error) {
    ui.toast(apiError(error), 'error');
  } finally {
    deleting.value = false;
    pendingDelete.value = null;
  }
}

useSeoMeta({ title: 'Monitoring Produk' });
</script>