<template>
  <div class="space-y-5">
    <header>
      <h1 class="text-xl font-extrabold text-strong">Pengguna</h1>
      <p class="mt-1 text-sm text-muted">{{ meta.total }} akun terdaftar di Mahapreneur.</p>
    </header>

    <div class="card flex flex-wrap items-center gap-3 p-4">
      <label class="flex min-w-56 flex-1 items-center gap-2 rounded-full border border-line bg-surface-muted px-4 py-2">
        <Search class="size-4 text-muted" />
        <input v-model="keyword" type="search" placeholder="Cari nama atau email…" class="w-full bg-transparent text-sm text-strong outline-none" @keyup.enter="apply" />
      </label>
      <select v-model="role" class="input !w-auto !py-2 text-xs">
        <option value="">Semua peran</option>
        <option value="SUPERADMIN">Superadmin</option>
        <option value="SELLER">Seller</option>
        <option value="CUSTOMER">Customer</option>
      </select>
      <select v-model="status" class="input !w-auto !py-2 text-xs">
        <option value="">Semua status</option>
        <option value="active">Aktif</option>
        <option value="inactive">Nonaktif</option>
      </select>
      <select v-model="verified" class="input !w-auto !py-2 text-xs">
        <option value="">Semua verifikasi</option>
        <option value="true">Terverifikasi</option>
        <option value="false">Belum verifikasi</option>
      </select>
      <button class="btn btn-outline text-xs" @click="apply">Terapkan</button>
    </div>

    <div v-if="users.length" class="card overflow-hidden">
      <div class="hidden grid-cols-[1fr_110px_1fr_120px_100px_130px] gap-3 border-b border-line px-5 py-3 text-[11px] font-bold uppercase tracking-wide text-muted lg:grid">
        <span>Pengguna</span><span>Peran</span><span>Toko</span><span>Verifikasi</span><span>Status</span><span class="text-right">Aksi</span>
      </div>

      <div class="divide-y divide-[color:var(--line)]">
        <div v-for="user in users" :key="user.id" class="grid gap-3 px-5 py-4 lg:grid-cols-[1fr_110px_1fr_120px_100px_130px] lg:items-center">
          <div class="flex min-w-0 items-center gap-3">
            <span class="grid size-10 shrink-0 place-items-center overflow-hidden rounded-full bg-surface-muted text-xs font-bold text-muted">
              <img v-if="user.avatar" :src="user.avatar" :alt="user.name" class="size-full object-cover" />
              <template v-else>{{ initials(user.name) }}</template>
            </span>
            <div class="min-w-0">
              <p class="truncate text-sm font-semibold text-strong">{{ user.name }}</p>
              <p class="truncate text-[11px] text-muted">{{ user.email }}</p>
            </div>
          </div>
          <span class="badge w-fit bg-surface-muted text-body">{{ roleLabel(user.role) }}</span>
          <div class="min-w-0 text-xs">
            <NuxtLink v-if="user.store" :to="`/store/${user.store.slug}`" target="_blank" class="truncate font-semibold text-strong hover:underline">
              {{ user.store.name }}
            </NuxtLink>
            <span v-else class="text-muted">—</span>
          </div>
          <span class="badge w-fit" :class="user.emailVerifiedAt ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-500/15 dark:text-emerald-300' : 'bg-amber-100 text-amber-800 dark:bg-amber-500/15 dark:text-amber-300'">
            {{ user.emailVerifiedAt ? 'Terverifikasi' : 'Belum' }}
          </span>
          <span class="badge w-fit" :class="user.isActive ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-500/15 dark:text-emerald-300' : 'bg-rose-100 text-rose-800 dark:bg-rose-500/15 dark:text-rose-300'">
            {{ user.isActive ? 'Aktif' : 'Nonaktif' }}
          </span>
          <div class="flex justify-end">
            <button
              class="btn !px-2.5 !py-1.5 text-xs"
              :class="user.isActive ? 'btn-outline text-rose-600' : 'btn-tertiary'"
              :disabled="!canToggle(user)"
              :title="!canToggle(user) ? 'Tidak dapat mengubah status akun ini' : undefined"
              @click="pendingUser = user"
            >
              {{ user.isActive ? 'Nonaktifkan' : 'Aktifkan' }}
            </button>
          </div>
        </div>
      </div>
    </div>

    <EmptyState v-else title="Tidak ada pengguna" description="Ubah filter pencarian atau kata kunci untuk menemukan akun lain." />

    <PaginationBar :meta="meta" @change="changePage" />

    <ConfirmDialog
      :open="Boolean(pendingUser)"
      :title="pendingUser?.isActive ? 'Nonaktifkan akun?' : 'Aktifkan akun?'"
      :message="dialogMessage"
      :confirm-label="pendingUser?.isActive ? 'Nonaktifkan akun' : 'Aktifkan akun'"
      :danger="Boolean(pendingUser?.isActive)"
      :loading="updating"
      @confirm="toggleStatus"
      @cancel="pendingUser = null"
    />
  </div>
</template>

<script setup lang="ts">
import { Search } from 'lucide-vue-next';
import type { PaginationMeta, User } from '~/types';

definePageMeta({ layout: 'dashboard', requiresAuth: true, roles: ['SUPERADMIN'] });

const api = useApi();
const ui = useUiStore();
const auth = useAuthStore();

const keyword = ref('');
const role = ref('');
const status = ref('');
const verified = ref('');
const page = ref(1);
const pendingUser = ref<User | null>(null);
const updating = ref(false);

const query = computed(() => ({
  q: keyword.value || undefined,
  role: role.value || undefined,
  status: status.value || undefined,
  verified: verified.value || undefined,
  page: page.value,
  limit: 10,
}));

const { data, refresh } = await useAsyncData('admin-users', () => api.get<{ users: User[]; meta: PaginationMeta }>('/admin/users', query.value), {
  watch: [query],
});

const users = computed(() => data.value?.users ?? []);
const meta = computed(() => data.value?.meta ?? { total: 0, page: 1, limit: 10, totalPages: 1 });
const dialogMessage = computed(() => {
  const user = pendingUser.value;
  if (!user) return '';
  return user.isActive
    ? `${user.name} (${user.email}) tidak akan bisa login sampai akun diaktifkan kembali.`
    : `${user.name} (${user.email}) akan bisa login kembali seperti biasa.`;
});

const initials = (name: string) =>
  name.split(' ').slice(0, 2).map((part) => part[0]?.toUpperCase() ?? '').join('');

function canToggle(user: User) {
  if (user.id === auth.user?.id) return false;
  if (user.role === 'SUPERADMIN' && user.isActive) return false;
  return true;
}

function apply() {
  page.value = 1;
  refresh();
}

function changePage(next: number) {
  page.value = next;
}

async function toggleStatus() {
  if (!pendingUser.value) return;
  updating.value = true;
  try {
    await api.patch(`/admin/users/${pendingUser.value.id}/status`, { isActive: !pendingUser.value.isActive });
    ui.toast(pendingUser.value.isActive ? 'Akun dinonaktifkan.' : 'Akun diaktifkan.', 'success');
    await refresh();
  } catch (error) {
    ui.toast(apiError(error), 'error');
  } finally {
    updating.value = false;
    pendingUser.value = null;
  }
}

useSeoMeta({ title: 'Manajemen Pengguna' });
</script>