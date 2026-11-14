<template>
  <div>
    <div class="grid size-12 place-items-center rounded-2xl" :class="status === 'success' ? 'bg-emerald-100 text-emerald-600 dark:bg-emerald-500/15 dark:text-emerald-300' : 'bg-surface-muted text-primary dark:text-tertiary'">
      <Loader2 v-if="status === 'verifying'" class="size-6 animate-spin" />
      <CheckCircle2 v-else-if="status === 'success'" class="size-6" />
      <MailCheck v-else class="size-6" />
    </div>

    <h1 class="mt-5 text-2xl font-extrabold text-strong">
      {{ status === 'success' ? 'Email terverifikasi' : status === 'verifying' ? 'Memverifikasi email…' : 'Verifikasi email kamu' }}
    </h1>

    <p v-if="status === 'success'" class="mt-2 text-sm text-muted">
      Akunmu sudah aktif. Silakan masuk untuk mulai menggunakan Mahapreneur.
    </p>
    <p v-else-if="status === 'verifying'" class="mt-2 text-sm text-muted">Mohon tunggu sebentar, kami sedang memeriksa token verifikasi.</p>
    <p v-else class="mt-2 text-sm text-muted">
      Kami mengirim tautan verifikasi ke
      <span class="font-semibold text-strong">{{ email || 'emailmu' }}</span>.
      Klik tautan tersebut sebelum masuk. Token berlaku 24 jam.
    </p>

    <div v-if="message" class="mt-5 flex items-start gap-2 rounded-xl px-4 py-3 text-sm" :class="status === 'success' ? 'bg-emerald-50 text-emerald-800 dark:bg-emerald-500/10 dark:text-emerald-300' : 'bg-amber-50 text-amber-800 dark:bg-amber-500/10 dark:text-amber-300'">
      <Info class="mt-0.5 size-4 shrink-0" /> {{ message }}
    </div>

    <NuxtLink v-if="status === 'success'" to="/auth/login" class="btn btn-primary mt-6 w-full">Masuk sekarang</NuxtLink>

    <template v-else>
      <form class="mt-6 space-y-3" @submit.prevent="resend">
        <label class="label" for="email">Kirim ulang email verifikasi</label>
        <div class="flex gap-2">
          <input id="email" v-model="email" type="email" class="input" placeholder="nama@kampus.ac.id" required />
          <button class="btn btn-outline shrink-0" :disabled="sending">
            <Loader2 v-if="sending" class="size-4 animate-spin" />
            Kirim
          </button>
        </div>
      </form>

      <div v-if="devToken" class="mt-5 rounded-2xl border border-dashed border-line-strong bg-surface-muted p-4 text-xs">
        <p class="font-bold text-strong">Mode pengembangan</p>
        <p class="mt-1 text-muted">SMTP belum dikonfigurasi, jadi token ditampilkan di sini:</p>
        <NuxtLink :to="`/auth/verify-email?token=${devToken}`" class="mt-2 inline-block font-semibold text-strong underline">
          Verifikasi sekarang
        </NuxtLink>
      </div>

      <NuxtLink to="/auth/login" class="mt-6 block text-center text-sm font-semibold text-muted hover:text-strong">Kembali ke halaman masuk</NuxtLink>
    </template>
  </div>
</template>

<script setup lang="ts">
import { CheckCircle2, Info, Loader2, MailCheck } from 'lucide-vue-next';

definePageMeta({ layout: 'auth' });

const api = useApi();
const route = useRoute();

const email = ref((route.query.email as string) ?? '');
const token = ref((route.query.token as string) ?? '');
const status = ref<'idle' | 'verifying' | 'success'>('idle');
const message = ref('');
const sending = ref(false);
const devToken = ref('');

async function verify(value: string) {
  status.value = 'verifying';
  try {
    await api.post('/auth/verify-email', { token: value });
    status.value = 'success';
    message.value = 'Email berhasil diverifikasi.';
  } catch (error) {
    status.value = 'idle';
    message.value = apiError(error, 'Token tidak valid atau kedaluwarsa. Minta email baru di bawah.');
  }
}

async function resend() {
  sending.value = true;
  try {
    const data = await api.post<{ verificationToken?: string }>('/auth/resend-verification', { email: email.value });
    devToken.value = data?.verificationToken ?? '';
    message.value = 'Jika email terdaftar dan belum diverifikasi, tautan baru sudah dikirim.';
  } catch (error) {
    message.value = apiError(error);
  } finally {
    sending.value = false;
  }
}

onMounted(() => {
  if (token.value) verify(token.value);
});
</script>