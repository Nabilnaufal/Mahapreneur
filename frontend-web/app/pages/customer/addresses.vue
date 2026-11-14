<template>
  <div class="space-y-5">
    <header class="flex flex-wrap items-end justify-between gap-3">
      <div>
        <h1 class="text-xl font-extrabold text-strong">Alamat penerimaan</h1>
        <p class="mt-1 text-sm text-muted">Simpan lokasi seperti kos, kelas, atau rumah agar checkout lebih cepat.</p>
      </div>
      <button class="btn btn-primary" @click="openForm()">
        <Plus class="size-4" /> Tambah alamat
      </button>
    </header>

    <div v-if="addresses.length" class="grid gap-4 sm:grid-cols-2">
      <article v-for="address in addresses" :key="address.id" class="card p-5">
        <div class="flex items-start justify-between gap-3">
          <div>
            <p class="flex items-center gap-2 text-sm font-bold text-strong">
              {{ address.label }}
              <span v-if="address.isDefault" class="badge bg-tertiary text-neutral">Utama</span>
            </p>
            <p class="mt-1 text-xs text-muted">{{ address.recipientName }} · {{ address.phone }}</p>
          </div>
          <div class="flex gap-1">
            <button class="btn btn-ghost !px-2" aria-label="Ubah alamat" @click="openForm(address)"><Pencil class="size-4" /></button>
            <button class="btn btn-ghost !px-2 text-rose-600" aria-label="Hapus alamat" @click="pendingDelete = address"><Trash2 class="size-4" /></button>
          </div>
        </div>
        <p class="mt-3 text-sm text-body">{{ address.address }}</p>
        <p v-if="address.campus || address.room" class="mt-1 text-xs text-muted">
          {{ [address.campus, address.room].filter(Boolean).join(' · ') }}
        </p>
        <button v-if="!address.isDefault" class="btn btn-outline mt-4 !py-1.5 text-xs" @click="setDefault(address)">Jadikan alamat utama</button>
      </article>
    </div>

    <EmptyState v-else title="Belum ada alamat tersimpan" description="Tambahkan alamat agar kamu tidak perlu mengetiknya lagi saat checkout.">
      <button class="btn btn-primary" @click="openForm()">Tambah alamat</button>
    </EmptyState>

    <!-- Form alamat -->
    <Teleport to="body">
      <div v-if="showForm" class="fixed inset-0 z-[70] grid place-items-center p-4">
        <div class="absolute inset-0 bg-neutral/60 backdrop-blur-sm" @click="showForm = false" />
        <form class="card relative max-h-[90vh] w-full max-w-lg overflow-y-auto p-6" @submit.prevent="save">
          <h2 class="text-base font-bold text-strong">{{ editing ? 'Ubah alamat' : 'Tambah alamat baru' }}</h2>
          <div class="mt-4 grid gap-4 sm:grid-cols-2">
            <div class="sm:col-span-2">
              <label class="label" for="label">Label alamat</label>
              <input id="label" v-model="form.label" class="input" placeholder="Contoh: Kos, Rumah, Kelas" required />
            </div>
            <div>
              <label class="label" for="recipientName">Nama penerima</label>
              <input id="recipientName" v-model="form.recipientName" class="input" required />
            </div>
            <div>
              <label class="label" for="phone">Nomor telepon</label>
              <input id="phone" v-model="form.phone" class="input" required />
            </div>
            <div class="sm:col-span-2">
              <label class="label" for="address">Alamat lengkap</label>
              <textarea id="address" v-model="form.address" rows="3" class="input" required />
            </div>
            <div>
              <label class="label" for="campus">Gedung / kampus</label>
              <input id="campus" v-model="form.campus" class="input" placeholder="Opsional" />
            </div>
            <div>
              <label class="label" for="room">Kelas / ruangan</label>
              <input id="room" v-model="form.room" class="input" placeholder="Opsional" />
            </div>
          </div>
          <label class="mt-4 flex cursor-pointer items-center gap-2.5 text-sm text-body">
            <input v-model="form.isDefault" type="checkbox" class="size-4 accent-[#102C40]" /> Jadikan alamat utama
          </label>
          <div class="mt-6 flex justify-end gap-2">
            <button type="button" class="btn btn-ghost" @click="showForm = false">Batal</button>
            <button class="btn btn-primary" :disabled="saving">
              <Loader2 v-if="saving" class="size-4 animate-spin" /> Simpan alamat
            </button>
          </div>
        </form>
      </div>
    </Teleport>

    <ConfirmDialog
      :open="Boolean(pendingDelete)"
      title="Hapus alamat?"
      :message="pendingDelete ? `Alamat “${pendingDelete.label}” akan dihapus permanen.` : ''"
      confirm-label="Hapus"
      danger
      @confirm="removeAddress"
      @cancel="pendingDelete = null"
    />
  </div>
</template>

<script setup lang="ts">
import { Loader2, Pencil, Plus, Trash2 } from 'lucide-vue-next';
import type { Address } from '~/types';

definePageMeta({ layout: 'account', requiresAuth: true, roles: ['CUSTOMER'] });

const api = useApi();
const ui = useUiStore();

const showForm = ref(false);
const saving = ref(false);
const editing = ref<Address | null>(null);
const pendingDelete = ref<Address | null>(null);

const { data, refresh } = await useAsyncData('addresses', () => api.get<{ addresses: Address[] }>('/customer/addresses'));
const addresses = computed(() => data.value?.addresses ?? []);

const form = reactive({
  label: '',
  recipientName: '',
  phone: '',
  address: '',
  campus: '',
  room: '',
  isDefault: false,
});

function openForm(address?: Address) {
  editing.value = address ?? null;
  Object.assign(form, {
    label: address?.label ?? '',
    recipientName: address?.recipientName ?? '',
    phone: address?.phone ?? '',
    address: address?.address ?? '',
    campus: address?.campus ?? '',
    room: address?.room ?? '',
    isDefault: address?.isDefault ?? addresses.value.length === 0,
  });
  showForm.value = true;
}

async function save() {
  saving.value = true;
  try {
    if (editing.value) await api.patch(`/customer/addresses/${editing.value.id}`, form);
    else await api.post('/customer/addresses', form);
    ui.toast('Alamat disimpan.', 'success');
    showForm.value = false;
    await refresh();
  } catch (error) {
    ui.toast(apiError(error), 'error');
  } finally {
    saving.value = false;
  }
}

async function setDefault(address: Address) {
  try {
    await api.patch(`/customer/addresses/${address.id}`, { isDefault: true });
    ui.toast('Alamat utama diperbarui.', 'success');
    await refresh();
  } catch (error) {
    ui.toast(apiError(error), 'error');
  }
}

async function removeAddress() {
  if (!pendingDelete.value) return;
  try {
    await api.del(`/customer/addresses/${pendingDelete.value.id}`);
    ui.toast('Alamat dihapus.', 'info');
    await refresh();
  } catch (error) {
    ui.toast(apiError(error), 'error');
  } finally {
    pendingDelete.value = null;
  }
}

useSeoMeta({ title: 'Alamat' });
</script>