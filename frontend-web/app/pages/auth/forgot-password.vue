<template>
  <div>
    <h1 class="text-2xl font-extrabold text-strong">Lupa password</h1>
    <p class="mt-1 text-sm text-muted">Masukkan email akunmu. Kami akan mengirim tautan untuk membuat password baru.</p>

    <form class="mt-6 space-y-4" @submit.prevent="submit">
      <div>
        <label class="label" for="email">Email</label>
        <input id="email" v-model="email" type="email" class="input" placeholder="nama@kampus.ac.id" required />
      </div>

      <div v-if="message" class="flex items-start gap-2 rounded-xl bg-surface-muted px-4 py-3 text-sm text-body">
        <Info class="mt-0.5 size-4 shrink-0" /> {{ message }}
      </div>

      <div v-if="devToken" class="rounded-2xl border border-dashed border-line-strong p-4 text-xs">
        <p class="font-bold text-strong">Mode pengembangan</p>
        <p class="mt-1 text-muted">SMTP belum dikonfigurasi. Gunakan tautan ini untuk melanjutkan:</p>
        <NuxtLink :to="`/auth/reset-password?token=${devToken}`" class="mt-2 inline-block font-semibold text-strong underline">
          Buat password baru
        </NuxtLink>
      </div>

      <button class="btn btn-primary w-full" :disabled="loading">
        <Loader2 v-if="loading" class="size-4 animate-spin" /> Kirim tautan reset
      </button>
    </form>

    <NuxtLink to="/auth/login" class="mt-6 block text-center text-sm font-semibold text-muted hover:text-strong">Kembali ke halaman masuk</NuxtLink>
  </div>
</template>

<script setup lang="ts">
import { Info, Loader2 } from 'lucide-vue-next';

definePageMeta({ layout: 'auth', guestOnly: true });

const api = useApi();
const email = ref('');
const loading = ref(false);
const message = ref('');
const devToken = ref('');

async function submit() {
  loading.value = true;
  message.value = '';
  devToken.value = '';
  try {
    const data = await api.post<{ resetToken?: string }>('/auth/forgot-password', { email: email.value });
    message.value = 'Jika email terdaftar, tautan reset password sudah dikirim. Periksa inbox dan folder spam.';
    devToken.value = data?.resetToken ?? '';
  } catch (error) {
    message.value = apiError(error);
  } finally {
    loading.value = false;
  }
}

useSeoMeta({ title: 'Lupa Password' });
</script>