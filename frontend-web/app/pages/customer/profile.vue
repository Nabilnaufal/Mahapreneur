<template>
  <div class="space-y-5">
    <header class="flex flex-wrap items-start justify-between gap-3">
      <div>
        <h1 class="text-xl font-extrabold text-strong">Profil saya</h1>
        <p class="mt-1 text-sm text-muted">Kelola data akun dan keamanan.</p>
      </div>
      <!-- Alamat tidak lagi punya menu di mobile, jadi tautannya ditaruh di sini. -->
      <NuxtLink to="/customer/addresses" class="btn btn-outline text-xs lg:hidden">
        <MapPin class="size-4" /> Alamat
      </NuxtLink>
    </header>

    <section class="card p-5">
      <h2 class="text-sm font-bold text-strong">Data akun</h2>
      <div class="mt-4 grid gap-4 sm:grid-cols-2">
        <div>
          <label class="label" for="name">Nama lengkap</label>
          <input id="name" v-model="form.name" class="input" />
        </div>
        <div>
          <label class="label" for="phone">Nomor telepon</label>
          <input id="phone" v-model="form.phone" class="input" placeholder="08xxxxxxxxxx" />
        </div>
        <div>
          <label class="label" for="email">Email</label>
          <input id="email" :value="auth.user?.email" class="input opacity-60" disabled />
          <p class="mt-1 flex items-center gap-1.5 text-[11px] text-muted">
            <BadgeCheck v-if="auth.user?.emailVerifiedAt" class="size-3.5 text-emerald-600" />
            <AlertCircle v-else class="size-3.5 text-amber-600" />
            {{ auth.user?.emailVerifiedAt ? 'Email terverifikasi' : 'Email belum diverifikasi' }}
          </p>
        </div>
        <div>
          <label class="label">Peran akun</label>
          <input :value="auth.isSeller ? 'Seller' : auth.isAdmin ? 'Superadmin' : 'Customer'" class="input opacity-60" disabled />
        </div>
      </div>

      <div class="mt-5 flex items-center gap-3">
        <button class="btn btn-primary" :disabled="saving" @click="saveProfile">
          <Loader2 v-if="saving" class="size-4 animate-spin" /> Simpan perubahan
        </button>
        <NuxtLink v-if="auth.isSeller" to="/seller" class="btn btn-outline">Buka panel seller</NuxtLink>
      </div>
    </section>

    <section class="card p-5">
      <h2 class="text-sm font-bold text-strong">Ubah password</h2>
      <div class="mt-4 grid gap-4 sm:grid-cols-3">
        <div>
          <label class="label" for="current">Password saat ini</label>
          <input id="current" v-model="passwordForm.currentPassword" type="password" class="input" autocomplete="current-password" />
        </div>
        <div>
          <label class="label" for="new">Password baru</label>
          <input id="new" v-model="passwordForm.newPassword" type="password" class="input" autocomplete="new-password" />
        </div>
        <div>
          <label class="label" for="confirm">Konfirmasi password baru</label>
          <input id="confirm" v-model="passwordForm.confirmPassword" type="password" class="input" autocomplete="new-password" />
        </div>
      </div>
      <button class="btn btn-outline mt-5" :disabled="changingPassword" @click="changePassword">
        <Loader2 v-if="changingPassword" class="size-4 animate-spin" /> Ubah password
      </button>
    </section>

    <section class="card p-5">
      <h2 class="text-sm font-bold text-strong">Preferensi tampilan</h2>
      <div class="mt-3 flex items-center justify-between gap-4">
        <p class="text-sm text-muted">Mode {{ ui.theme === 'dark' ? 'gelap' : 'terang' }} aktif. Preferensi disimpan di perangkat ini.</p>
        <button class="btn btn-outline" @click="ui.toggleTheme()">
          <Sun v-if="ui.theme === 'dark'" class="size-4" />
          <Moon v-else class="size-4" />
          Ganti mode
        </button>
      </div>
    </section>
  </div>
</template>

<script setup lang="ts">
import { AlertCircle, BadgeCheck, Loader2, MapPin, Moon, Sun } from 'lucide-vue-next';

definePageMeta({ layout: 'account', requiresAuth: true, roles: ['CUSTOMER'] });

const api = useApi();
const auth = useAuthStore();
const ui = useUiStore();

const saving = ref(false);
const changingPassword = ref(false);

const form = reactive({ name: auth.user?.name ?? '', phone: auth.user?.phone ?? '' });
const passwordForm = reactive({ currentPassword: '', newPassword: '', confirmPassword: '' });

async function saveProfile() {
  saving.value = true;
  try {
    await api.patch('/customer/profile', form);
    await auth.fetchUser();
    ui.toast('Profil diperbarui.', 'success');
  } catch (error) {
    ui.toast(apiError(error), 'error');
  } finally {
    saving.value = false;
  }
}

async function changePassword() {
  changingPassword.value = true;
  try {
    await api.patch('/customer/profile/password', passwordForm);
    ui.toast('Password berhasil diubah.', 'success');
    Object.assign(passwordForm, { currentPassword: '', newPassword: '', confirmPassword: '' });
  } catch (error) {
    ui.toast(apiError(error), 'error');
  } finally {
    changingPassword.value = false;
  }
}

useSeoMeta({ title: 'Profil' });
</script>