<template>
  <Teleport to="body">
    <Transition name="modal">
      <div v-if="ui.loginModalOpen" class="fixed inset-0 z-[70] grid place-items-center bg-neutral/60 px-4 backdrop-blur-sm" @click.self="ui.closeLogin">
        <section class="card relative w-full max-w-md rounded-3xl p-6 shadow-2xl sm:p-8" role="dialog" aria-modal="true" aria-labelledby="login-modal-title">
          <button class="absolute right-4 top-4 grid size-9 place-items-center rounded-full text-muted transition hover:bg-surface-muted hover:text-strong" aria-label="Tutup login" @click="ui.closeLogin">
            <X class="size-5" />
          </button>

          <div class="mb-7 flex items-center justify-between pr-10">
            <div>
              <p class="text-[10px] font-bold uppercase tracking-[0.18em] text-muted">Selamat datang kembali</p>
              <h2 id="login-modal-title" class="mt-1 text-2xl font-extrabold text-strong">Masuk</h2>
            </div>
            <NuxtLink to="/auth/register" class="text-sm font-semibold text-primary hover:underline dark:text-tertiary" @click="ui.closeLogin">Daftar</NuxtLink>
          </div>

          <form class="space-y-4" @submit.prevent="submit">
            <div>
              <label class="label" for="modal-email">Email</label>
              <div class="relative">
                <Mail class="pointer-events-none absolute left-3.5 top-1/2 size-4 -translate-y-1/2 text-muted" />
                <input id="modal-email" v-model="form.email" class="input pl-10" type="email" placeholder="nama@kampus.ac.id" autocomplete="email" required />
              </div>
            </div>
            <div>
              <div class="flex items-center justify-between">
                <label class="label" for="modal-password">Password</label>
                <NuxtLink to="/auth/forgot-password" class="mb-1.5 text-xs font-semibold text-muted hover:text-strong" @click="ui.closeLogin">Lupa password?</NuxtLink>
              </div>
              <div class="relative">
                <LockKeyhole class="pointer-events-none absolute left-3.5 top-1/2 size-4 -translate-y-1/2 text-muted" />
                <input id="modal-password" v-model="form.password" class="input px-10" :type="showPassword ? 'text' : 'password'" placeholder="Masukkan password" autocomplete="current-password" required />
                <button type="button" class="absolute inset-y-0 right-3 grid place-items-center text-muted hover:text-strong" :aria-label="showPassword ? 'Sembunyikan password' : 'Tampilkan password'" @click="showPassword = !showPassword">
                  <EyeOff v-if="showPassword" class="size-4" /><Eye v-else class="size-4" />
                </button>
              </div>
            </div>

            <p v-if="error" class="rounded-xl bg-rose-50 px-4 py-3 text-sm text-rose-700 dark:bg-rose-500/10 dark:text-rose-300">{{ error }}</p>
            <button class="btn btn-primary w-full !py-3" :disabled="loading">
              <Loader2 v-if="loading" class="size-4 animate-spin" />
              {{ loading ? 'Memproses…' : 'Masuk' }}
              <ArrowRight v-if="!loading" class="size-4" />
            </button>
          </form>

          <div class="mt-6 border-t border-line pt-5 text-center text-xs text-muted">
            Belum punya akun?
            <NuxtLink to="/auth/register" class="font-bold text-strong hover:underline" @click="ui.closeLogin">Daftar sekarang</NuxtLink>
          </div>
        </section>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup lang="ts">
import { ArrowRight, Eye, EyeOff, Loader2, LockKeyhole, Mail, X } from 'lucide-vue-next';

const auth = useAuthStore();
const ui = useUiStore();
const router = useRouter();
const form = reactive({ email: '', password: '' });
const showPassword = ref(false);
const loading = ref(false);
const error = ref('');

watch(() => ui.loginModalOpen, (open) => {
  if (open) {
    error.value = '';
    form.password = '';
  }
});

async function submit() {
  loading.value = true;
  error.value = '';
  try {
    const user = await auth.login(form.email, form.password);
    if (auth.isCustomer) await useCartStore().fetch();
    const redirect = ui.loginRedirect;
    ui.closeLogin();
    router.push(redirect || auth.homeRoute(user.role));
  } catch (err) {
    error.value = apiError(err, 'Gagal masuk. Periksa email dan password.');
  } finally {
    loading.value = false;
  }
}
</script>

<style scoped>
.modal-enter-active,
.modal-leave-active { transition: opacity 0.18s ease; }
.modal-enter-active section,
.modal-leave-active section { transition: transform 0.18s ease, opacity 0.18s ease; }
.modal-enter-from,
.modal-leave-to { opacity: 0; }
.modal-enter-from section,
.modal-leave-to section { opacity: 0; transform: translateY(12px) scale(0.98); }
</style>
