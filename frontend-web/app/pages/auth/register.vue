<template>
  <div>
    <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-muted">Bergabung gratis</p>
    <h1 class="mt-2 text-2xl font-extrabold text-strong sm:text-3xl">Buat akun Mahapreneur</h1>
    <p class="mt-2 text-sm text-muted">Pilih peranmu — kamu bisa berjualan atau berbelanja.</p>

    <div class="mt-6 grid grid-cols-2 gap-3">
      <button
        v-for="option in roles"
        :key="option.value"
        type="button"
        class="relative rounded-2xl border p-4 text-left transition"
        :class="form.role === option.value
          ? 'border-primary bg-primary/[0.04] ring-1 ring-primary dark:bg-primary-soft/20'
          : 'border-line hover:border-line-strong hover:bg-surface-muted'"
        @click="form.role = option.value"
      >
        <span
          v-if="form.role === option.value"
          class="absolute right-3 top-3 grid size-5 place-items-center rounded-full bg-primary text-tertiary"
        >
          <Check class="size-3" />
        </span>
        <span
          class="grid size-10 place-items-center rounded-xl transition"
          :class="form.role === option.value ? 'bg-tertiary text-neutral' : 'bg-surface-muted text-muted'"
        >
          <component :is="option.icon" class="size-5" />
        </span>
        <p class="mt-3 text-sm font-bold text-strong">{{ option.label }}</p>
        <p class="mt-0.5 text-[11px] leading-snug text-muted">{{ option.desc }}</p>
      </button>
    </div>

    <form class="mt-5 space-y-4" @submit.prevent="submit">
      <div>
        <label class="label" for="name">Nama lengkap</label>
        <div class="relative">
          <User class="pointer-events-none absolute left-3.5 top-1/2 size-4 -translate-y-1/2 text-muted" />
          <input id="name" v-model="form.name" class="input pl-10" placeholder="Nama sesuai KTM" autocomplete="name" required />
        </div>
      </div>
      <div>
        <label class="label" for="email">Email</label>
        <div class="relative">
          <Mail class="pointer-events-none absolute left-3.5 top-1/2 size-4 -translate-y-1/2 text-muted" />
          <input id="email" v-model="form.email" type="email" class="input pl-10" placeholder="nama@kampus.ac.id" autocomplete="email" required />
        </div>
      </div>
      <div class="grid gap-4 sm:grid-cols-2">
        <div>
          <label class="label" for="password">Password</label>
          <div class="relative">
            <LockKeyhole class="pointer-events-none absolute left-3.5 top-1/2 size-4 -translate-y-1/2 text-muted" />
            <input id="password" v-model="form.password" type="password" class="input pl-10" placeholder="Min. 8 karakter" autocomplete="new-password" required />
          </div>
        </div>
        <div>
          <label class="label" for="confirmPassword">Konfirmasi password</label>
          <input id="confirmPassword" v-model="form.confirmPassword" type="password" class="input" placeholder="Ulangi password" autocomplete="new-password" required />
        </div>
      </div>

      <div class="flex flex-wrap gap-1.5">
        <span
          v-for="check in passwordChecks"
          :key="check.label"
          class="inline-flex items-center gap-1 rounded-full px-2 py-1 text-[10px] font-semibold transition"
          :class="check.ok ? 'bg-tertiary/20 text-strong' : 'bg-surface-muted text-muted'"
        >
          <Check class="size-3" :class="check.ok ? 'text-primary dark:text-tertiary' : 'opacity-40'" />
          {{ check.label }}
        </span>
      </div>

      <div v-if="error" class="flex items-start gap-2 rounded-xl bg-rose-50 px-4 py-3 text-sm text-rose-700 dark:bg-rose-500/10 dark:text-rose-300">
        <AlertCircle class="mt-0.5 size-4 shrink-0" /> {{ error }}
      </div>

      <button class="btn btn-primary w-full !py-3" :disabled="loading">
        <Loader2 v-if="loading" class="size-4 animate-spin" />
        {{ loading ? 'Membuat akun…' : 'Daftar sekarang' }}
        <ArrowRight v-if="!loading" class="size-4" />
      </button>
    </form>

    <p class="mt-6 text-center text-sm text-muted">
      Sudah punya akun?
      <NuxtLink to="/auth/login" class="font-semibold text-strong hover:underline">Masuk</NuxtLink>
    </p>
  </div>
</template>

<script setup lang="ts">
import { AlertCircle, ArrowRight, Check, Loader2, LockKeyhole, Mail, ShoppingBag, Store, User } from 'lucide-vue-next';

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

const passwordChecks = computed(() => [
  { label: 'Min. 8 karakter', ok: form.password.length >= 8 },
  { label: 'Mengandung huruf', ok: /[A-Za-z]/.test(form.password) },
  { label: 'Mengandung angka', ok: /[0-9]/.test(form.password) },
]);

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
