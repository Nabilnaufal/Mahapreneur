<template>
  <div>
    <h1 class="text-2xl font-extrabold text-strong">Buat password baru</h1>
    <p class="mt-1 text-sm text-muted">Masukkan password baru untuk akunmu. Minimal 8 karakter, mengandung huruf dan angka.</p>

    <form class="mt-6 space-y-4" @submit.prevent="submit">
      <div>
        <label class="label" for="password">Password baru</label>
        <input id="password" v-model="password" type="password" class="input" placeholder="Password baru" autocomplete="new-password" required />
      </div>
      <div>
        <label class="label" for="confirm">Konfirmasi password</label>
        <input id="confirm" v-model="confirmPassword" type="password" class="input" placeholder="Ulangi password baru" autocomplete="new-password" required />
      </div>

      <div v-if="error" class="flex items-start gap-2 rounded-xl bg-rose-50 px-4 py-3 text-sm text-rose-700 dark:bg-rose-500/10 dark:text-rose-300">
        <AlertCircle class="mt-0.5 size-4 shrink-0" /> {{ error }}
      </div>

      <button class="btn btn-primary w-full" :disabled="loading || !token">
        <Loader2 v-if="loading" class="size-4 animate-spin" /> Simpan password baru
      </button>

      <p v-if="!token" class="rounded-xl bg-amber-50 px-4 py-3 text-xs text-amber-800 dark:bg-amber-500/10 dark:text-amber-300">
        Token reset tidak ditemukan. Buka tautan dari email, atau minta tautan baru di halaman lupa password.
      </p>
    </form>

    <NuxtLink to="/auth/login" class="mt-6 block text-center text-sm font-semibold text-muted hover:text-strong">Kembali ke halaman masuk</NuxtLink>
  </div>
</template>

<script setup lang="ts">
import { AlertCircle, Loader2 } from 'lucide-vue-next';

definePageMeta({ layout: 'auth', guestOnly: true });

const api = useApi();
const route = useRoute();
const router = useRouter();
const ui = useUiStore();

const token = ref((route.query.token as string) ?? '');
const password = ref('');
const confirmPassword = ref('');
const loading = ref(false);
const error = ref('');

async function submit() {
  loading.value = true;
  error.value = '';
  try {
    await api.post('/auth/reset-password', { token: token.value, password: password.value, confirmPassword: confirmPassword.value });
    ui.toast('Password berhasil diubah. Silakan masuk.', 'success');
    router.push('/auth/login');
  } catch (err) {
    error.value = apiError(err, 'Gagal mengubah password.');
  } finally {
    loading.value = false;
  }
}

useSeoMeta({ title: 'Reset Password' });
</script>