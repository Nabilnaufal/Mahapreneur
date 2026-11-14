<template>
  <div>
    <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-muted">Selamat datang kembali</p>
    <h1 class="mt-2 text-2xl font-extrabold text-strong sm:text-3xl">Masuk ke akunmu</h1>
    <p class="mt-2 text-sm text-muted">Belanja dari toko mahasiswa, atau kelola tokomu sendiri.</p>

    <form class="mt-7 space-y-4" @submit.prevent="submit">
      <div>
        <label class="label" for="email">Email</label>
        <div class="relative">
          <Mail class="pointer-events-none absolute left-3.5 top-1/2 size-4 -translate-y-1/2 text-muted" />
          <input id="email" v-model="form.email" type="email" class="input pl-10" placeholder="nama@kampus.ac.id" autocomplete="email" required />
        </div>
      </div>

      <div>
        <div class="flex items-center justify-between">
          <label class="label" for="password">Password</label>
          <NuxtLink to="/auth/forgot-password" class="mb-1.5 text-xs font-semibold text-muted transition hover:text-strong">Lupa password?</NuxtLink>
        </div>
        <div class="relative">
          <LockKeyhole class="pointer-events-none absolute left-3.5 top-1/2 size-4 -translate-y-1/2 text-muted" />
          <input
            id="password"
            v-model="form.password"
            :type="showPassword ? 'text' : 'password'"
            class="input px-10"
            placeholder="Masukkan password"
            autocomplete="current-password"
            required
          />
          <button type="button" class="absolute inset-y-0 right-3 grid place-items-center text-muted transition hover:text-strong" :aria-label="showPassword ? 'Sembunyikan password' : 'Tampilkan password'" @click="showPassword = !showPassword">
            <EyeOff v-if="showPassword" class="size-4" />
            <Eye v-else class="size-4" />
          </button>
        </div>
      </div>

      <div v-if="error" class="flex items-start gap-2 rounded-xl bg-rose-50 px-4 py-3 text-sm text-rose-700 dark:bg-rose-500/10 dark:text-rose-300">
        <AlertCircle class="mt-0.5 size-4 shrink-0" />
        <div>
          <p>{{ error }}</p>
          <NuxtLink v-if="needsVerification" to="/auth/verify-email" class="mt-1 inline-block font-semibold underline">
            Kirim ulang email verifikasi
          </NuxtLink>
        </div>
      </div>

      <button class="btn btn-primary w-full !py-3" :disabled="loading">
        <Loader2 v-if="loading" class="size-4 animate-spin" />
        {{ loading ? 'Memproses…' : 'Masuk' }}
        <ArrowRight v-if="!loading" class="size-4" />
      </button>
    </form>

    <p class="mt-6 text-center text-sm text-muted">
      Belum punya akun?
      <NuxtLink to="/auth/register" class="font-semibold text-strong hover:underline">Daftar sekarang</NuxtLink>
    </p>

    <div class="mt-7 rounded-2xl border border-line bg-surface-muted p-4">
      <div class="flex flex-wrap items-center justify-between gap-1">
        <p class="text-xs font-bold text-strong">Isi cepat akun demo</p>
        <p class="text-[10px] text-muted">Password: <span class="font-semibold text-strong">Mahapreneur1</span></p>
      </div>
      <div class="mt-3 grid grid-cols-3 gap-2">
        <button
          v-for="demo in demoAccounts"
          :key="demo.label"
          type="button"
          class="group rounded-xl border border-line bg-surface-raised px-2 py-2.5 text-center transition hover:-translate-y-0.5 hover:border-line-strong hover:shadow-sm"
          @click="fillDemo(demo.email)"
        >
          <component :is="demo.icon" class="mx-auto size-4 text-muted transition group-hover:text-strong" />
          <span class="mt-1 block text-[10px] font-bold uppercase tracking-wide text-body">{{ demo.label }}</span>
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { AlertCircle, ArrowRight, Eye, EyeOff, Loader2, LockKeyhole, Mail, ShieldCheck, ShoppingBag, Store } from 'lucide-vue-next';

definePageMeta({ layout: 'auth', guestOnly: true });

const auth = useAuthStore();
const ui = useUiStore();
const route = useRoute();
const router = useRouter();

const form = reactive({ email: '', password: '' });
const loading = ref(false);
const error = ref('');
const needsVerification = ref(false);
const showPassword = ref(false);

const demoAccounts = [
  { label: 'Customer', email: 'pembeli@mahapreneur.test', icon: ShoppingBag },
  { label: 'Seller', email: 'kopi@mahapreneur.test', icon: Store },
  { label: 'Admin', email: 'admin@mahapreneur.test', icon: ShieldCheck },
];

function fillDemo(email: string) {
  form.email = email;
  form.password = 'Mahapreneur1';
  error.value = '';
}

async function submit() {
  loading.value = true;
  error.value = '';
  needsVerification.value = false;
  try {
    const user = await auth.login(form.email, form.password);
    ui.toast(`Selamat datang, ${user.name.split(' ')[0]}!`, 'success');
    if (auth.isCustomer) await useCartStore().fetch();
    const redirect = route.query.redirect as string | undefined;
    router.push(redirect || auth.homeRoute(user.role));
  } catch (err) {
    error.value = apiError(err, 'Gagal masuk. Periksa email dan password.');
    needsVerification.value = (err as { data?: { errors?: { code?: string } } })?.data?.errors?.code === 'EMAIL_NOT_VERIFIED';
  } finally {
    loading.value = false;
  }
}

useSeoMeta({ title: 'Masuk' });
</script>
