<template>
  <div>
    <div class="text-center">
      <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-muted">Bergabung gratis</p>
      <h1 class="mt-2 text-2xl font-extrabold text-strong sm:text-3xl">Daftar sekarang</h1>
      <p class="mt-2 text-sm text-muted">Sudah punya akun? <NuxtLink to="/auth/login" class="font-bold text-primary hover:underline dark:text-tertiary">Masuk</NuxtLink></p>
    </div>

    <div class="mt-6 flex items-center gap-2 rounded-xl bg-surface-muted p-1">
      <button v-for="option in roles" :key="option.value" type="button" class="flex-1 rounded-lg px-3 py-2 text-xs font-bold transition" :class="form.role === option.value ? 'bg-surface-raised text-strong shadow-sm' : 'text-muted'" @click="form.role = option.value">
        {{ option.label }}
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
      <div>
        <label class="label" for="password">Password</label>
        <input id="password" v-model="form.password" type="password" class="input" placeholder="Minimal 8 karakter" autocomplete="new-password" required />
      </div>
      <div>
        <label class="label" for="confirmPassword">Konfirmasi password</label>
        <input id="confirmPassword" v-model="form.confirmPassword" type="password" class="input" placeholder="Ulangi password" autocomplete="new-password" required />
      </div>

      <div class="flex flex-wrap gap-1.5">
        <span v-for="check in passwordChecks" :key="check.label" class="inline-flex items-center gap-1 rounded-full px-2 py-1 text-[10px] font-semibold" :class="check.ok ? 'bg-tertiary/20 text-strong' : 'bg-surface-muted text-muted'">
          <Check class="size-3" :class="check.ok ? 'text-primary dark:text-tertiary' : 'opacity-40'" /> {{ check.label }}
        </span>
      </div>

      <div v-if="error" class="flex items-start gap-2 rounded-xl bg-rose-50 px-4 py-3 text-sm text-rose-700 dark:bg-rose-500/10 dark:text-rose-300">
        <AlertCircle class="mt-0.5 size-4 shrink-0" /> {{ error }}
      </div>

      <button class="btn btn-primary w-full !py-3" :disabled="loading">
        <Loader2 v-if="loading" class="size-4 animate-spin" />
        {{ loading ? 'Membuat akun…' : 'Daftar' }}
        <ArrowRight v-if="!loading" class="size-4" />
      </button>
    </form>

    <p class="mt-5 text-center text-xs leading-relaxed text-muted">
      Dengan mendaftar, kamu menyetujui ketentuan penggunaan Mahapreneur.
    </p>
  </div>
</template>

<script setup lang="ts">
import { AlertCircle, ArrowRight, Check, Loader2, Mail, ShoppingBag, Store, User } from 'lucide-vue-next';

definePageMeta({ layout: 'auth', guestOnly: true });

const api = useApi();
const ui = useUiStore();
const route = useRoute();
const router = useRouter();
const roles = [
  { value: 'CUSTOMER' as const, label: 'Customer', icon: ShoppingBag },
  { value: 'SELLER' as const, label: 'Seller', icon: Store },
];
const form = reactive({ name: '', email: '', password: '', confirmPassword: '', role: (route.query.role === 'SELLER' ? 'SELLER' : 'CUSTOMER') as 'CUSTOMER' | 'SELLER' });
const loading = ref(false);
const error = ref('');
const passwordChecks = computed(() => [
  { label: 'Min. 8 karakter', ok: form.password.length >= 8 },
  { label: 'Ada huruf', ok: /[A-Za-z]/.test(form.password) },
  { label: 'Ada angka', ok: /[0-9]/.test(form.password) },
]);

async function submit() {
  loading.value = true;
  error.value = '';
  try {
    const data = await api.post<{ verificationToken?: string }>('/auth/register', form);
    ui.toast('Akun dibuat. Verifikasi email untuk mulai masuk.', 'success');
    router.push({ path: '/auth/verify-email', query: { email: form.email, ...(data.verificationToken ? { token: data.verificationToken } : {}) } });
  } catch (err) {
    error.value = apiError(err, 'Registrasi gagal. Coba lagi.');
  } finally {
    loading.value = false;
  }
}

useSeoMeta({ title: 'Daftar' });
</script>
