<template>
  <div class="space-y-5">
    <header>
      <h1 class="text-xl font-extrabold text-strong">{{ store ? 'Pengaturan toko' : 'Buat toko baru' }}</h1>
      <p class="mt-1 text-sm text-muted">
        Satu seller hanya boleh memiliki satu toko. Info ini tampil di halaman toko publikmu.
      </p>
    </header>

    <form class="grid gap-5 lg:grid-cols-[1fr_320px]" @submit.prevent="save">
      <div class="space-y-5">
        <section class="card p-5">
          <h2 class="text-sm font-bold text-strong">Identitas toko</h2>
          <div class="mt-4 grid gap-4 sm:grid-cols-2">
            <div>
              <label class="label" for="name">Nama toko</label>
              <input id="name" v-model="form.name" class="input" placeholder="Contoh: Dapur Nusantara" required />
            </div>
            <div>
              <label class="label" for="slug">Username toko (URL)</label>
              <div class="flex items-center rounded-xl border border-line-strong bg-surface px-3">
                <span class="text-xs text-muted">/store/</span>
                <input id="slug" v-model="form.slug" class="w-full bg-transparent py-2.5 text-sm text-strong outline-none" placeholder="dapur-nusantara" />
              </div>
              <p class="mt-1 text-[11px] text-muted">Hanya huruf kecil, angka, dan tanda hubung.</p>
            </div>
            <div class="sm:col-span-2">
              <label class="label" for="description">Deskripsi toko</label>
              <textarea id="description" v-model="form.description" rows="3" class="input" placeholder="Ceritakan produk dan keunikan tokomu." />
            </div>
          </div>
        </section>

        <section class="card p-5">
          <h2 class="text-sm font-bold text-strong">Kontak & lokasi</h2>
          <div class="mt-4 grid gap-4 sm:grid-cols-2">
            <div>
              <label class="label" for="phone">Nomor telepon</label>
              <input id="phone" v-model="form.phone" class="input" placeholder="08xxxxxxxxxx" />
            </div>
            <div>
              <label class="label" for="email">Email toko</label>
              <input id="email" v-model="form.email" type="email" class="input" placeholder="toko@email.com" />
            </div>
            <div class="sm:col-span-2">
              <label class="label" for="address">Alamat toko</label>
              <input id="address" v-model="form.address" class="input" placeholder="Jl. Kampus Raya No. 12, Depok" />
            </div>
            <div class="sm:col-span-2">
              <label class="label" for="campus">Gedung / area kampus</label>
              <input id="campus" v-model="form.campus" class="input" placeholder="Gedung A - Fakultas Ekonomi" />
            </div>
            <div>
              <label class="label" for="openTime">Jam buka</label>
              <input id="openTime" v-model="form.openTime" type="time" class="input" />
            </div>
            <div>
              <label class="label" for="closeTime">Jam tutup</label>
              <input id="closeTime" v-model="form.closeTime" type="time" class="input" />
            </div>
          </div>
        </section>

        <section v-if="store" class="card p-5">
          <h2 class="text-sm font-bold text-strong">Status toko</h2>
          <p class="mt-1 text-xs text-muted">Toko nonaktif tidak muncul di marketplace dan produknya tidak bisa dipesan.</p>
          <div class="mt-3 flex gap-2">
            <button
              v-for="option in statusOptions"
              :key="option.value"
              type="button"
              class="badge border px-3.5 py-2"
              :class="form.status === option.value ? 'border-primary bg-primary text-white' : 'border-line text-body'"
              @click="form.status = option.value"
            >
              {{ option.label }}
            </button>
          </div>
        </section>

        <div class="flex items-center gap-3">
          <button class="btn btn-primary" :disabled="saving">
            <Loader2 v-if="saving" class="size-4 animate-spin" />
            {{ store ? 'Simpan perubahan' : 'Buat toko' }}
          </button>
          <NuxtLink v-if="store" :to="`/store/${store.slug}`" class="btn btn-outline" target="_blank">
            <ExternalLink class="size-4" /> Lihat halaman toko
          </NuxtLink>
        </div>
      </div>

      <!-- Gambar toko -->
      <aside class="space-y-5">
        <section class="card p-5">
          <h2 class="text-sm font-bold text-strong">Logo toko</h2>
          <div class="mt-3 grid place-items-center overflow-hidden rounded-2xl border border-dashed border-line-strong bg-surface-muted p-4">
            <img v-if="store?.logo" :src="store.logo" alt="Logo toko" class="size-24 rounded-2xl object-cover" />
            <Store v-else class="size-10 text-muted" />
          </div>
          <label class="btn btn-outline mt-3 w-full cursor-pointer !py-2 text-xs">
            <Upload class="size-3.5" /> Unggah logo
            <input type="file" accept="image/*" class="hidden" :disabled="!store" @change="upload('logo', $event)" />
          </label>
          <p v-if="!store" class="mt-2 text-center text-[11px] text-muted">Buat toko dulu untuk mengunggah gambar.</p>
        </section>

        <section class="card p-5">
          <h2 class="text-sm font-bold text-strong">Banner toko</h2>
          <div class="mt-3 overflow-hidden rounded-2xl border border-dashed border-line-strong bg-surface-muted">
            <img v-if="store?.banner" :src="store.banner" alt="Banner toko" class="h-24 w-full object-cover" />
            <div v-else class="grid h-24 place-items-center"><ImageIcon class="size-8 text-muted" /></div>
          </div>
          <label class="btn btn-outline mt-3 w-full cursor-pointer !py-2 text-xs">
            <Upload class="size-3.5" /> Unggah banner
            <input type="file" accept="image/*" class="hidden" :disabled="!store" @change="upload('banner', $event)" />
          </label>
        </section>
      </aside>
    </form>
  </div>
</template>

<script setup lang="ts">
import { ExternalLink, Image as ImageIcon, Loader2, Store, Upload } from 'lucide-vue-next';
import type { Store as StoreType } from '~/types';

definePageMeta({ layout: 'dashboard', requiresAuth: true, roles: ['SELLER'] });

const api = useApi();
const auth = useAuthStore();
const ui = useUiStore();

const saving = ref(false);

const statusOptions = [
  { label: 'Aktif', value: 'ACTIVE' as const },
  { label: 'Tidak aktif', value: 'INACTIVE' as const },
];

const { data, refresh } = await useAsyncData('seller-store', () => api.get<{ store: StoreType | null }>('/seller/store'));
const store = computed(() => data.value?.store ?? null);

const form = reactive({
  name: store.value?.name ?? '',
  slug: store.value?.slug ?? '',
  description: store.value?.description ?? '',
  phone: store.value?.phone ?? '',
  email: store.value?.email ?? '',
  address: store.value?.address ?? '',
  campus: store.value?.campus ?? '',
  openTime: store.value?.openTime ?? '',
  closeTime: store.value?.closeTime ?? '',
  status: store.value?.status ?? ('ACTIVE' as 'ACTIVE' | 'INACTIVE'),
});

async function save() {
  saving.value = true;
  try {
    if (store.value) await api.patch('/seller/store', form);
    else await api.post('/seller/store', form);
    await refresh();
    await auth.fetchUser();
    ui.toast(store.value ? 'Toko diperbarui.' : 'Toko berhasil dibuat.', 'success');
  } catch (error) {
    ui.toast(apiError(error), 'error');
  } finally {
    saving.value = false;
  }
}

async function upload(kind: 'logo' | 'banner', event: Event) {
  const input = event.target as HTMLInputElement;
  const file = input.files?.[0];
  if (!file) return;
  const body = new FormData();
  body.append('image', file);
  try {
    await api.upload(`/seller/store/${kind}`, body);
    await refresh();
    ui.toast(`${kind === 'logo' ? 'Logo' : 'Banner'} diperbarui.`, 'success');
  } catch (error) {
    ui.toast(apiError(error, 'Gagal mengunggah gambar'), 'error');
  } finally {
    input.value = '';
  }
}

useSeoMeta({ title: 'Toko Saya' });
</script>