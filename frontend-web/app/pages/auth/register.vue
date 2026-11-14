<template>
  <div>
    <h1 class="text-2xl font-extrabold text-strong">Buat akun Mahapreneur</h1>
    <p class="mt-1 text-sm text-muted">Pilih peranmu — kamu bisa berjualan atau berbelanja.</p>

    <div class="mt-6 grid grid-cols-2 gap-3">
      <button
        v-for="option in roles"
        :key="option.value"
        type="button"
        class="rounded-2xl border p-4 text-left transition"
        :class="form.role === option.value ? 'border-primary bg-primary/5 dark:bg-primary-soft/20' : 'border-line hover:border-line-strong'"
        @click="form.role = option.value"
      >
        <component :is="option.icon" class="size-5" :class="form.role === option.value ? 'text-primary dark:text-tertiary' : 'text-muted'" />
        <p class="mt-2 text-sm font-bold text-strong">{{ option.label }}</p>
        <p class="mt-0.5 text-[11px] leading-snug text-muted">{{ option.desc }}</p>
      </button>
    </div>

    <form class="mt-5 space-y-4" @submit.prevent="submit">
      <div>
        <label class="label" for="name">Nama lengkap</label>
        <input id="name" v-model="form.name" class="input" placeholder="Nama sesuai KTM" autocomplete="name" required />
      </div>
      <div>
        <label class="label" for="email">Email</label>
        <input id="email" v-model="form.email" type="email" class="input" placeholder="nama@kampus.ac.id" autocomplete="email" required />
      </div>
      <div class="grid gap-4 sm:grid-cols-2">
        <div>
          <label class="label" for="password">Password</label>
          <input id="password" v-model="form.password" type="password" class="input" placeholder="Min. 8 karakter" autocomplete="new-password" required />
        </div>
        <div>
          <label class="label" for="confirmPassword">Konfirmasi password</label>
          <input id="confirmPassword" v-model="form.confirmPassword" type="password" class="input" placeholder="Ulangi password" autocomplete="new-password" required />
        </div>
      </div>
      <p class="text-[11px] text-muted">Password minimal 8 karakter dan mengandung huruf serta angka.</p>

      <div v-if="error" class="flex items-start gap-2 rounded-xl bg-rose-50 px-4 py-3 text-sm text-rose-700 dark:bg-rose-500/10 dark:text-rose-300">
        <AlertCircle class="mt-0.5 size-4 shrink-0" /> {{ error }}
      </div>

      <button class="btn btn-primary w-full" :disabled="loading">
        <Loader2 v-if="loading" class="size-4 animate-spin" />
        {{ loading ? 'Membuat akun…' : 'Daftar' }}
      </button>
    </form>

    <p class="mt-6 text-center text-sm text-muted">
      Sudah punya akun?
      <NuxtLink to="/auth/login" class="font-semibold text-strong hover:underline">Masuk</NuxtLink>
    </p>
  </div>
</template>

<script setup lang="ts">
import { AlertCircle, Loader2, ShoppingBag, Store } from 'lucide-vue-next';

definePageMeta({ layout: 'auth', guestOnly: true });

const api = useApi();
const ui = useUiStore();
const route = useRoute();
const router = useRouter();

const roles = [
  { value: 'CUSTOMER' as const, label: 'Customer', desc: 'Belanja produk dan jasa', icon: ShoppingBag },
  { value: 'SELLER' as const, label: 'Seller', desc: 'Buka toko dan berjualan', icon: Store },
];

const form = reactive({
  name: '',
  email: '',
  password: '',
  confirmPassword: '',
  role: (route.query.role === 'SELLER' ? 'SELLER' : 'CUSTOMER') as 'CUSTOMER' | 'SELLER',
});

const loading = ref(false);
const error = ref('');

async function submit() {
  loading.value = true;
  error.value = '';
  try {
    const data = await api.post<{ verificationToken?: string }>('/auth/register', form);
    ui.toast('Akun dibuat. Verifikasi email untuk mulai masuk.', 'success');
    router.push({
      path: '/auth/verify-email',
      query: {
        email: form.email,
        ...(data.verificationToken ? { token: data.verificationToken } : {}),
      },
    });
  } catch (err) {
    error.value = apiError(err, 'Registrasi gagal. Coba lagi.');
  } finally {
    loading.value = false;
  }
}

useSeoMeta({ title: 'Daftar' });
</script>