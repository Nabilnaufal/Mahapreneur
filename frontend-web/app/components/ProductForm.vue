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

        <section class="card p-5">
          <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
              <h2 class="text-sm font-bold text-strong">Gambar produk</h2>
              <p class="mt-1 text-xs text-muted">{{ totalImageCount }}/5 foto dipilih. Foto pertama menjadi thumbnail.</p>
            </div>
            <label v-if="canAddImages" class="btn btn-outline cursor-pointer !py-2 text-xs">
              <Upload class="size-3.5" /> Pilih foto
              <input ref="imageInput" type="file" accept="image/jpeg,image/png,image/webp,image/gif" multiple class="hidden" @change="selectImages" />
            </label>
            <span v-else class="badge bg-surface-muted text-muted">Maksimal tercapai</span>
          </div>

          <div v-if="existingImages.length || selectedImages.length" class="mt-4 grid grid-cols-3 gap-3 sm:grid-cols-5">
            <div v-for="(image, index) in existingImages" :key="'existing-' + image.id" class="group relative aspect-square overflow-hidden rounded-2xl border border-line">
              <img :src="image.url" :alt="'Gambar ' + (index + 1)" class="size-full object-cover" />
              <span v-if="index === 0" class="badge absolute left-1.5 top-1.5 bg-tertiary text-neutral">Utama</span>
              <button type="button" class="absolute right-1.5 top-1.5 grid size-6 place-items-center rounded-full bg-neutral/70 text-white opacity-0 transition group-hover:opacity-100" :aria-label="'Hapus gambar ' + (index + 1)" @click="emit('removeImage', image.id)">
                <X class="size-3.5" />
              </button>
            </div>
            <div v-for="(image, index) in selectedImages" :key="image.url" class="group relative aspect-square overflow-hidden rounded-2xl border border-dashed border-primary">
              <img :src="image.url" :alt="'Preview foto baru ' + (index + 1)" class="size-full object-cover" />
              <span class="badge absolute left-1.5 top-1.5 bg-primary text-white">Baru</span>
              <button type="button" class="absolute right-1.5 top-1.5 grid size-6 place-items-center rounded-full bg-neutral/70 text-white opacity-0 transition group-hover:opacity-100" :aria-label="'Hapus foto baru ' + (index + 1)" @click="removeImage(index)">
                <X class="size-3.5" />
              </button>
            </div>
          </div>

          <label v-if="!totalImageCount" class="mt-4 grid min-h-28 cursor-pointer place-items-center rounded-2xl border border-dashed border-line-strong bg-surface-muted p-5 text-center transition hover:border-primary">
            <span>
              <ImageIcon class="mx-auto size-7 text-muted" />
              <span class="mt-2 block text-xs font-semibold text-body">Klik untuk memilih beberapa foto</span>
              <span class="mt-1 block text-[11px] text-muted">JPG, PNG, WEBP, atau GIF</span>
            </span>
            <input type="file" accept="image/jpeg,image/png,image/webp,image/gif" multiple class="hidden" @change="selectImages" />
          </label>
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
import { Briefcase, Image as ImageIcon, Loader2, Upload, X } from 'lucide-vue-next';
import type { Category, Product, ProductStatus, ProductType } from '~/types';

const props = withDefaults(defineProps<{ product?: Product | null; categories: Category[]; saving?: boolean }>(), {
  product: null,
  saving: false,
});
const emit = defineEmits<{
  submit: [payload: Record<string, unknown>];
  removeImage: [imageId: number];
}>();
const ui = useUiStore();

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

const selectedImages = ref<{ file: File; url: string }[]>([]);
const existingImages = computed(() => props.product?.imageRecords ?? []);
const totalImageCount = computed(() => existingImages.value.length + selectedImages.value.length);
const canAddImages = computed(() => totalImageCount.value < 5);

function selectImages(event: Event) {
  const input = event.target as HTMLInputElement;
  const files = Array.from(input.files ?? []);
  const remaining = 5 - totalImageCount.value;

  if (files.length > remaining) {
    ui.toast(
      remaining
        ? 'Maksimal 5 foto. Hanya ' + remaining + ' foto yang ditambahkan.'
        : 'Maksimal 5 foto. Hapus foto terlebih dahulu.',
      'info',
    );
  }

  const images = files
    .filter((file) => file.type.startsWith('image/'))
    .slice(0, Math.max(remaining, 0))
    .map((file) => ({ file, url: URL.createObjectURL(file) }));

  selectedImages.value = [...selectedImages.value, ...images];
  input.value = '';
}

function removeImage(index: number) {
  const image = selectedImages.value[index];
  if (image) URL.revokeObjectURL(image.url);
  selectedImages.value.splice(index, 1);
}

function submit() {
  if (!form.categoryId) return;
  emit('submit', {
    ...form,
    ...(selectedImages.value.length ? { images: selectedImages.value.map((image) => image.file) } : {}),
  });
}

onBeforeUnmount(() => {
  selectedImages.value.forEach((image) => URL.revokeObjectURL(image.url));
});
</script>
