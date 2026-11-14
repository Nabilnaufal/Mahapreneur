<template>
  <div class="space-y-5">
    <header class="flex flex-wrap items-end justify-between gap-3">
      <div>
        <h1 class="text-xl font-extrabold text-strong">Banner homepage</h1>
        <p class="mt-1 text-sm text-muted">{{ banners.length }} banner. Banner aktif tampil di halaman utama.</p>
      </div>
      <button class="btn btn-primary text-xs" @click="openCreate"><Plus class="size-4" /> Tambah banner</button>
    </header>

    <section v-if="editing" class="card p-5">
      <div class="flex items-start justify-between gap-3">
        <h2 class="text-sm font-bold text-strong">{{ editing.id ? 'Ubah banner' : 'Banner baru' }}</h2>
        <button class="btn btn-ghost !px-2" aria-label="Tutup formulir" @click="editing = null"><X class="size-4" /></button>
      </div>

      <form class="mt-4 grid gap-4 lg:grid-cols-2" @submit.prevent="save">
        <div class="space-y-3">
          <div>
            <label class="label" for="banner-title">Judul</label>
            <input id="banner-title" v-model="form.title" class="input" required maxlength="80" placeholder="Mis. Promo mahasiswa baru" />
          </div>
          <div>
            <label class="label" for="banner-desc">Deskripsi</label>
            <textarea id="banner-desc" v-model="form.description" class="input min-h-20" maxlength="300" placeholder="Penjelasan singkat promo" />
          </div>
          <div class="grid gap-3 sm:grid-cols-2">
            <div>
              <label class="label" for="banner-cta-label">Label tombol</label>
              <input id="banner-cta-label" v-model="form.ctaLabel" class="input" maxlength="40" placeholder="Lihat katalog" />
            </div>
            <div>
              <label class="label" for="banner-cta-url">URL tombol</label>
              <input id="banner-cta-url" v-model="form.ctaUrl" class="input" maxlength="200" placeholder="/catalog" />
            </div>
          </div>
          <div class="grid gap-3 sm:grid-cols-2">
            <div>
              <label class="label" for="banner-order">Urutan</label>
              <input id="banner-order" v-model.number="form.sortOrder" class="input" type="number" min="0" />
            </div>
            <div>
              <label class="label" for="banner-status">Status</label>
              <select id="banner-status" v-model="form.status" class="input">
                <option value="ACTIVE">Aktif</option>
                <option value="INACTIVE">Nonaktif</option>
              </select>
            </div>
          </div>
        </div>

        <div>
          <label class="label">Gambar banner</label>
          <div class="grid h-40 place-items-center overflow-hidden rounded-2xl border border-dashed border-line-strong bg-surface-muted">
            <img v-if="preview" :src="preview" alt="Pratinjau banner" class="size-full object-cover" />
            <span v-else class="text-xs text-muted">Belum ada gambar</span>
          </div>
          <label class="btn btn-outline mt-3 cursor-pointer text-xs">
            <Upload class="size-3.5" /> {{ uploading ? 'Mengunggah…' : 'Unggah gambar' }}
            <input type="file" accept="image/*" class="hidden" :disabled="uploading" @change="uploadImage" />
          </label>
          <p class="mt-2 text-[11px] text-muted">Format JPG/PNG, maksimal 5 MB. Rasio lebar disarankan 16:6.</p>
          <button class="btn btn-primary mt-4 w-full sm:w-auto" type="submit" :disabled="saving || uploading">
            <Loader2 v-if="saving" class="size-4 animate-spin" />
            Simpan banner
          </button>
        </div>
      </form>
    </section>

    <div v-if="banners.length" class="grid gap-4 md:grid-cols-2">
      <article v-for="banner in banners" :key="banner.id" class="card overflow-hidden">
        <div class="relative h-36 bg-surface-muted">
          <img :src="banner.image" :alt="banner.title" class="size-full object-cover" />
          <span class="badge absolute left-3 top-3" :class="banner.status === 'ACTIVE' ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-200 text-slate-700'">
            {{ banner.status === 'ACTIVE' ? 'Aktif' : 'Nonaktif' }}
          </span>
        </div>
        <div class="p-4">
          <div class="flex items-start justify-between gap-2">
            <div class="min-w-0">
              <h2 class="truncate text-sm font-bold text-strong">{{ banner.title }}</h2>
              <p class="line-clamp-2 text-xs text-muted">{{ banner.description || 'Tanpa deskripsi' }}</p>
            </div>
            <span class="shrink-0 text-[11px] text-muted">Urutan {{ banner.sortOrder }}</span>
          </div>
          <div class="mt-3 flex items-center gap-2">
            <button class="btn btn-outline !px-2.5 !py-1.5 text-xs" @click="openEdit(banner)"><Pencil class="size-3.5" /> Ubah</button>
            <button class="btn btn-ghost !px-2.5 !py-1.5 text-xs text-rose-600" @click="pendingDelete = banner"><Trash2 class="size-3.5" /> Hapus</button>
            <NuxtLink v-if="banner.ctaUrl" :to="banner.ctaUrl" target="_blank" class="ml-auto truncate text-[11px] font-semibold text-muted hover:text-strong">
              {{ banner.ctaUrl }}
            </NuxtLink>
          </div>
        </div>
      </article>
    </div>

    <EmptyState v-else title="Belum ada banner" description="Tambahkan banner untuk menyorot promo atau pengumuman di halaman utama." />

    <ConfirmDialog
      :open="Boolean(pendingDelete)"
      title="Hapus banner?"
      :message="pendingDelete ? `${pendingDelete.title} akan dihapus dari homepage.` : ''"
      confirm-label="Hapus banner"
      danger
      :loading="deleting"
      @confirm="removeBanner"
      @cancel="pendingDelete = null"
    />
  </div>
</template>

<script setup lang="ts">
import { Loader2, Pencil, Plus, Trash2, Upload, X } from 'lucide-vue-next';
import type { Banner } from '~/types';

definePageMeta({ layout: 'dashboard', requiresAuth: true, roles: ['SUPERADMIN'] });

const api = useApi();
const ui = useUiStore();
const config = useRuntimeConfig();

const { data, refresh } = await useAsyncData('admin-banners', () => api.get<{ banners: Banner[] }>('/admin/banners'));

const banners = computed(() => data.value?.banners ?? []);

const editing = ref<Banner | null>(null);
const saving = ref(false);
const deleting = ref(false);
const uploading = ref(false);
const pendingDelete = ref<Banner | null>(null);

const form = reactive({
  title: '',
  description: '',
  image: '',
  ctaLabel: '',
  ctaUrl: '',
  sortOrder: 0,
  status: 'ACTIVE' as 'ACTIVE' | 'INACTIVE',
});

const preview = computed(() => {
  if (!form.image) return '';
  return /^https?:\/\//.test(form.image)
    ? form.image
    : `${config.public.apiOrigin}/${form.image.replace(/^\/+/, '')}`;
});

function openCreate() {
  Object.assign(form, { title: '', description: '', image: '', ctaLabel: '', ctaUrl: '', sortOrder: banners.value.length, status: 'ACTIVE' });
  editing.value = { id: 0 } as Banner;
}

function openEdit(banner: Banner) {
  Object.assign(form, {
    title: banner.title,
    description: banner.description ?? '',
    image: banner.image,
    ctaLabel: banner.ctaLabel ?? '',
    ctaUrl: banner.ctaUrl ?? '',
    sortOrder: banner.sortOrder,
    status: banner.status,
  });
  editing.value = banner;
}

async function uploadImage(event: Event) {
  const input = event.target as HTMLInputElement;
  const file = input.files?.[0];
  if (!file) return;
  uploading.value = true;
  try {
    const body = new FormData();
    body.append('image', file);
    const result = await api.upload<{ url: string }>('/admin/banners/upload', body);
    form.image = result.url;
    ui.toast('Gambar diunggah.', 'success');
  } catch (error) {
    ui.toast(apiError(error, 'Gagal mengunggah gambar'), 'error');
  } finally {
    uploading.value = false;
    input.value = '';
  }
}

async function save() {
  if (!editing.value) return;
  if (!form.image) {
    ui.toast('Unggah gambar banner terlebih dahulu.', 'error');
    return;
  }
  saving.value = true;
  try {
    if (editing.value.id) await api.patch(`/admin/banners/${editing.value.id}`, form);
    else await api.post('/admin/banners', form);
    ui.toast(editing.value.id ? 'Banner diperbarui.' : 'Banner ditambahkan.', 'success');
    editing.value = null;
    await refresh();
  } catch (error) {
    ui.toast(apiError(error), 'error');
  } finally {
    saving.value = false;
  }
}

async function removeBanner() {
  if (!pendingDelete.value) return;
  deleting.value = true;
  try {
    await api.del(`/admin/banners/${pendingDelete.value.id}`);
    ui.toast('Banner dihapus.', 'success');
    await refresh();
  } catch (error) {
    ui.toast(apiError(error), 'error');
  } finally {
    deleting.value = false;
    pendingDelete.value = null;
  }
}

useSeoMeta({ title: 'Banner Homepage' });
</script>