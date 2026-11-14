<template>
  <div>
    <h1 class="text-2xl font-extrabold text-strong">Masuk ke Mahapreneur</h1>
    <p class="mt-1 text-sm text-muted">Belanja dari toko mahasiswa, atau kelola tokomu sendiri.</p>

    <form class="mt-7 space-y-4" @submit.prevent="submit">
      <div>
        <label class="label" for="email">Email</label>
        <input id="email" v-model="form.email" type="email" class="input" placeholder="nama@kampus.ac.id" autocomplete="email" required />
      </div>

      <div>
        <div class="flex items-center justify-between">
          <label class="label" for="password">Password</label>
          <NuxtLink to="/auth/forgot-password" class="mb-1.5 text-xs font-semibold text-muted hover:text-strong">Lupa password?</NuxtLink>
        </div>
        <div class="relative">
          <input
            id="password"
            v-model="form.password"
            :type="showPassword ? 'text' : 'password'"
            class="input pr-11"
            placeholder="Masukkan password"
            autocomplete="current-password"
            required
          />
          <button type="button" class="absolute inset-y-0 right-3 grid place-items-center text-muted" :aria-label="showPassword ? 'Sembunyikan password' : 'Tampilkan password'" @click="showPassword = !showPassword">
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

      <button class="btn btn-primary w-full" :disabled="loading">
        <Loader2 v-if="loading" class="size-4 animate-spin" />
        {{ loading ? 'Memproses…' : 'Masuk' }}
      </button>
    </form>

    <p class="mt-6 text-center text-sm text-muted">
      Belum punya akun?
      <NuxtLink to="/auth/register" class="font-semibold text-strong hover:underline">Daftar sekarang</NuxtLink>
    </p>

    <div class="mt-8 rounded-2xl border border-line bg-surface-muted p-4 text-xs leading-relaxed text-muted">
      <p class="font-bold text-strong">Akun demo</p>
      <p class="mt-1">Customer: pembeli@mahapreneur.test · Seller: dapur@mahapreneur.test</p>
      <p>Admin: admin@mahapreneur.test — password semua: <span class="font-semibold text-strong">Mahapreneur1</span></p>
    </div>
  </div>
</template>

<script setup lang="ts">
import { AlertCircle, Eye, EyeOff, Loader2 } from 'lucide-vue-next';

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