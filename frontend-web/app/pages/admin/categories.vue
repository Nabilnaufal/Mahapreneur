<template>
  <div class="space-y-5">
    <header class="flex flex-wrap items-end justify-between gap-3">
      <div>
        <h1 class="text-xl font-extrabold text-strong">Kategori</h1>
        <p class="mt-1 text-sm text-muted">{{ categories.length }} kategori dipakai untuk menyusun katalog produk.</p>
      </div>
      <button class="btn btn-primary text-xs" @click="openCreate"><Plus class="size-4" /> Tambah kategori</button>
    </header>

    <section v-if="editing" class="card p-5">
      <div class="flex items-start justify-between gap-3">
        <h2 class="text-sm font-bold text-strong">{{ editing.id ? 'Ubah kategori' : 'Kategori baru' }}</h2>
        <button class="btn btn-ghost !px-2" aria-label="Tutup formulir" @click="editing = null"><X class="size-4" /></button>
      </div>
      <form class="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-4" @submit.prevent="save">
        <div>
          <label class="label" for="cat-name">Nama</label>
          <input id="cat-name" v-model="form.name" class="input" required maxlength="40" placeholder="Mis. Makanan" />
        </div>
        <div>
          <label class="label" for="cat-icon">Ikon</label>
          <select id="cat-icon" v-model="form.icon" class="input">
            <option v-for="icon in ICONS" :key="icon" :value="icon">{{ icon }}</option>
          </select>
        </div>
        <div>
          <label class="label" for="cat-order">Urutan</label>
          <input id="cat-order" v-model.number="form.sortOrder" class="input" type="number" min="0" />
        </div>
        <div class="flex items-end gap-3">
          <label class="flex items-center gap-2 text-sm font-semibold text-body">
            <input v-model="form.isActive" type="checkbox" class="size-4 accent-[color:var(--color-primary)]" />
            Aktif
          </label>
          <button class="btn btn-primary ml-auto" type="submit" :disabled="saving">
            <Loader2 v-if="saving" class="size-4 animate-spin" />
            Simpan
          </button>
        </div>
      </form>
      <p class="mt-2 text-[11px] text-muted">Slug dibuat otomatis dari nama kategori.</p>
    </section>

    <div v-if="categories.length" class="card overflow-hidden">
      <div class="hidden grid-cols-[1fr_120px_110px_100px_120px] gap-3 border-b border-line px-5 py-3 text-[11px] font-bold uppercase tracking-wide text-muted lg:grid">
        <span>Kategori</span><span>Slug</span><span>Produk</span><span>Status</span><span class="text-right">Aksi</span>
      </div>
      <div class="divide-y divide-[color:var(--line)]">
        <div v-for="category in categories" :key="category.id" class="grid gap-3 px-5 py-4 lg:grid-cols-[1fr_120px_110px_100px_120px] lg:items-center">
          <div class="flex items-center gap-3">
            <span class="grid size-10 shrink-0 place-items-center rounded-xl bg-surface-muted text-primary dark:text-tertiary">
              <CategoryIcon :name="category.icon" />
            </span>
            <div class="min-w-0">
              <p class="truncate text-sm font-semibold text-strong">{{ category.name }}</p>
              <p class="text-[11px] text-muted">Urutan {{ category.sortOrder }}</p>
            </div>
          </div>
          <span class="truncate font-mono text-xs text-muted">{{ category.slug }}</span>
          <span class="text-sm text-body">{{ category.productCount ?? 0 }}</span>
          <span class="badge w-fit" :class="category.isActive ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-500/15 dark:text-emerald-300' : 'bg-slate-200 text-slate-700 dark:bg-slate-500/20 dark:text-slate-300'">
            {{ category.isActive ? 'Aktif' : 'Nonaktif' }}
          </span>
          <div class="flex justify-end gap-1">
            <button class="btn btn-outline !px-2.5 !py-1.5 text-xs" aria-label="Ubah kategori" @click="openEdit(category)"><Pencil class="size-3.5" /></button>
            <button class="btn btn-ghost !px-2.5 !py-1.5 text-xs text-rose-600" aria-label="Hapus kategori" @click="pendingDelete = category"><Trash2 class="size-3.5" /></button>
          </div>
        </div>
      </div>
    </div>

    <EmptyState v-else title="Belum ada kategori" description="Tambahkan kategori agar produk bisa dikelompokkan dengan rapi." />

    <ConfirmDialog
      :open="Boolean(pendingDelete)"
      title="Hapus kategori?"
      :message="pendingDelete ? `${pendingDelete.name} akan dihapus. Kategori yang masih dipakai produk tidak bisa dihapus.` : ''"
      confirm-label="Hapus kategori"
      danger
      :loading="deleting"
      @confirm="removeCategory"
      @cancel="pendingDelete = null"
    />
  </div>
</template>

<script setup lang="ts">
import { Loader2, Pencil, Plus, Trash2, X } from 'lucide-vue-next';
import type { Category } from '~/types';

definePageMeta({ layout: 'dashboard', requiresAuth: true, roles: ['SUPERADMIN'] });

const api = useApi();
const ui = useUiStore();

const ICONS = ['UtensilsCrossed', 'Coffee', 'Apple', 'Shirt', 'Watch', 'Laptop', 'Briefcase', 'Package', 'Boxes', 'Store'];

const { data, refresh } = await useAsyncData('admin-categories', () =>
  api.get<{ categories: Category[] }>('/admin/categories'),
);

const categories = computed(() => data.value?.categories ?? []);

const editing = ref<Category | null>(null);
const saving = ref(false);
const deleting = ref(false);
const pendingDelete = ref<Category | null>(null);
const form = reactive({ name: '', icon: 'Package', sortOrder: 0, isActive: true });

function openCreate() {
  Object.assign(form, { name: '', icon: 'Package', sortOrder: categories.value.length, isActive: true });
  editing.value = { id: 0 } as Category;
}

function openEdit(category: Category) {
  Object.assign(form, {
    name: category.name,
    icon: category.icon ?? 'Package',
    sortOrder: category.sortOrder,
    isActive: category.isActive,
  });
  editing.value = category;
}

async function save() {
  if (!editing.value) return;
  saving.value = true;
  try {
    if (editing.value.id) await api.patch(`/admin/categories/${editing.value.id}`, form);
    else await api.post('/admin/categories', form);
    ui.toast(editing.value.id ? 'Kategori diperbarui.' : 'Kategori ditambahkan.', 'success');
    editing.value = null;
    await refresh();
  } catch (error) {
    ui.toast(apiError(error), 'error');
  } finally {
    saving.value = false;
  }
}

async function removeCategory() {
  if (!pendingDelete.value) return;
  deleting.value = true;
  try {
    await api.del(`/admin/categories/${pendingDelete.value.id}`);
    ui.toast('Kategori dihapus.', 'success');
    await refresh();
  } catch (error) {
    ui.toast(apiError(error), 'error');
  } finally {
    deleting.value = false;
    pendingDelete.value = null;
  }
}

useSeoMeta({ title: 'Manajemen Kategori' });
</script>