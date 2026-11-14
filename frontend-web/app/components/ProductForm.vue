<template>
  <form class="space-y-5" @submit.prevent="submit">
    <div class="grid gap-5 lg:grid-cols-[1fr_320px]">
      <div class="space-y-5">
        <section class="card p-5">
          <h2 class="text-sm font-bold text-strong">Informasi produk</h2>
          <div class="mt-4 space-y-4">
            <div>
              <label class="label" for="name">Nama produk / jasa</label>
              <input id="name" v-model="form.name" class="input" placeholder="Contoh: Nasi Ayam Geprek Sambal Bawang" required />
            </div>
            <div>
              <label class="label" for="description">Deskripsi</label>
              <textarea id="description" v-model="form.description" rows="5" class="input" placeholder="Jelaskan isi produk, porsi, bahan, atau detail layanan." required />
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
              <div>
                <label class="label" for="price">Harga (Rp)</label>
                <input id="price" v-model.number="form.price" type="number" min="0" step="500" class="input" required />
              </div>
              <div>
                <label class="label" for="stock">{{ form.type === 'SERVICE' ? 'Slot tersedia' : 'Stok' }}</label>
                <input id="stock" v-model.number="form.stock" type="number" min="0" class="input" required />
              </div>
            </div>
          </div>
        </section>

        <section v-if="form.type === 'SERVICE'" class="card p-5">
          <h2 class="flex items-center gap-2 text-sm font-bold text-strong"><Briefcase class="size-4" /> Detail layanan</h2>
          <div class="mt-4 space-y-4">
            <div>
              <label class="label" for="duration">Estimasi pengerjaan</label>
              <input id="duration" v-model="form.serviceDuration" class="input" placeholder="Contoh: 2–3 hari kerja" />
            </div>
            <div>
              <label class="label" for="terms">Ketentuan pemesanan</label>
              <textarea id="terms" v-model="form.serviceTerms" rows="4" class="input" placeholder="Contoh: Revisi maksimal 2 kali, file sumber dikirim setelah pembayaran." />
            </div>
          </div>
        </section>
      </div>

      <aside class="space-y-5">
        <section class="card p-5">
          <h2 class="text-sm font-bold text-strong">Kategori & jenis</h2>
          <div class="mt-4 space-y-4">
            <div>
              <label class="label" for="categoryId">Kategori</label>
              <select id="categoryId" v-model.number="form.categoryId" class="input" required>
                <option :value="0" disabled>Pilih kategori</option>
                <option v-for="category in categories" :key="category.id" :value="category.id">{{ category.name }}</option>
              </select>
            </div>
            <div>
              <label class="label">Jenis</label>
              <div class="flex gap-2">
                <button
                  v-for="option in typeOptions"
                  :key="option.value"
                  type="button"
                  class="badge flex-1 justify-center border px-3 py-2"
                  :class="form.type === option.value ? 'border-primary bg-primary text-white' : 'border-line text-body'"
                  @click="form.type = option.value"
                >
                  {{ option.label }}
                </button>
              </div>
            </div>
            <div>
              <label class="label">Status</label>
              <div class="flex gap-2">
                <button
                  v-for="option in statusOptions"
                  :key="option.value"
                  type="button"
                  class="badge flex-1 justify-center border px-3 py-2"
                  :class="form.status === option.value ? 'border-primary bg-primary text-white' : 'border-line text-body'"
                  @click="form.status = option.value"
                >
                  {{ option.label }}
                </button>
              </div>
            </div>
          </div>
        </section>

        <button class="btn btn-primary w-full" :disabled="saving">
          <Loader2 v-if="saving" class="size-4 animate-spin" />
          {{ saving ? 'Menyimpan…' : product ? 'Simpan perubahan' : 'Tambahkan produk' }}
        </button>
        <NuxtLink to="/seller/products" class="btn btn-ghost w-full text-xs">Kembali ke daftar produk</NuxtLink>
      </aside>
    </div>
  </form>
</template>

<script setup lang="ts">
import { Briefcase, Loader2 } from 'lucide-vue-next';
import type { Category, Product, ProductStatus, ProductType } from '~/types';

const props = withDefaults(defineProps<{ product?: Product | null; categories: Category[]; saving?: boolean }>(), {
  product: null,
  saving: false,
});
const emit = defineEmits<{ submit: [payload: Record<string, unknown>] }>();

const typeOptions: { label: string; value: ProductType }[] = [
  { label: 'Barang', value: 'GOODS' },
  { label: 'Jasa', value: 'SERVICE' },
];
const statusOptions: { label: string; value: ProductStatus }[] = [
  { label: 'Aktif', value: 'ACTIVE' },
  { label: 'Nonaktif', value: 'INACTIVE' },
];

const form = reactive({
  name: props.product?.name ?? '',
  description: props.product?.description ?? '',
  price: props.product?.price ?? 0,
  stock: props.product?.stock ?? 0,
  categoryId: props.product?.category?.id ?? 0,
  type: (props.product?.type ?? 'GOODS') as ProductType,
  status: (props.product?.status ?? 'ACTIVE') as ProductStatus,
  serviceDuration: props.product?.serviceDuration ?? '',
  serviceTerms: props.product?.serviceTerms ?? '',
});

function submit() {
  if (!form.categoryId) return;
  emit('submit', { ...form });
}
</script>