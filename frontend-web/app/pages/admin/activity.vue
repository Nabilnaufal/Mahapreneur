<template>
  <div class="space-y-5">
    <header>
      <h1 class="text-xl font-extrabold text-strong">Riwayat aktivitas</h1>
      <p class="mt-1 text-sm text-muted">{{ meta.total }} catatan tindakan penting: perubahan status, pembayaran, dan moderasi.</p>
    </header>

    <div class="card flex flex-wrap items-center gap-3 p-4">
      <label class="flex min-w-56 flex-1 items-center gap-2 rounded-full border border-line bg-surface-muted px-4 py-2">
        <Search class="size-4 text-muted" />
        <input v-model="keyword" type="search" placeholder="Cari deskripsi aktivitas…" class="w-full bg-transparent text-sm text-strong outline-none" @keyup.enter="apply" />
      </label>
      <select v-model="entity" class="input !w-auto !py-2 text-xs">
        <option value="">Semua entitas</option>
        <option v-for="item in ENTITIES" :key="item" :value="item">{{ item }}</option>
      </select>
      <button class="btn btn-outline text-xs" @click="apply">Terapkan</button>
    </div>

    <div v-if="logs.length" class="card overflow-hidden">
      <div class="hidden grid-cols-[170px_1fr_180px_160px] gap-3 border-b border-line px-5 py-3 text-[11px] font-bold uppercase tracking-wide text-muted lg:grid">
        <span>Waktu</span><span>Aktivitas</span><span>Pelaku</span><span>Entitas</span>
      </div>
      <div class="divide-y divide-[color:var(--line)]">
        <div v-for="log in logs" :key="log.id" class="grid gap-3 px-5 py-4 lg:grid-cols-[170px_1fr_180px_160px] lg:items-start">
          <div class="text-xs">
            <p class="font-semibold text-strong">{{ formatDate(log.createdAt, true) }}</p>
            <p class="text-[11px] text-muted">{{ formatRelative(log.createdAt) }}</p>
          </div>
          <div class="min-w-0">
            <p class="text-sm text-body">{{ log.description }}</p>
            <span class="mt-1 inline-block font-mono text-[10px] uppercase tracking-wide text-muted">{{ log.action.replaceAll('_', ' ') }}</span>
          </div>
          <div class="min-w-0 text-xs">
            <template v-if="log.user">
              <p class="truncate font-semibold text-body">{{ log.user.name }}</p>
              <p class="truncate text-muted">{{ roleLabel(log.user.role) }} · {{ log.user.email }}</p>
            </template>
            <span v-else class="text-muted">Sistem</span>
          </div>
          <div class="text-xs">
            <span class="badge bg-surface-muted text-body">{{ log.entity }}</span>
            <p v-if="log.entityId" class="mt-1 truncate font-mono text-[10px] text-muted">#{{ log.entityId }}</p>
          </div>
        </div>
      </div>
    </div>

    <EmptyState v-else title="Belum ada aktivitas" description="Tindakan admin, perubahan status pesanan, dan pembayaran akan tercatat di sini." />

    <PaginationBar :meta="meta" @change="changePage" />
  </div>
</template>

<script setup lang="ts">
import { Search } from 'lucide-vue-next';
import type { ActivityLog, PaginationMeta } from '~/types';

definePageMeta({ layout: 'dashboard', requiresAuth: true, roles: ['SUPERADMIN'] });

const api = useApi();

const ENTITIES = ['User', 'Category', 'Store', 'Product', 'Order', 'Payment', 'Banner'];

const keyword = ref('');
const entity = ref('');
const page = ref(1);

const query = computed(() => ({
  q: keyword.value || undefined,
  entity: entity.value || undefined,
  page: page.value,
  limit: 20,
}));

const { data, refresh } = await useAsyncData('admin-activity', () => api.get<{ logs: ActivityLog[]; meta: PaginationMeta }>('/admin/activity', query.value), {
  watch: [query],
});

const logs = computed(() => data.value?.logs ?? []);
const meta = computed(() => data.value?.meta ?? { total: 0, page: 1, limit: 20, totalPages: 1 });

function apply() {
  page.value = 1;
  refresh();
}

function changePage(next: number) {
  page.value = next;
}

useSeoMeta({ title: 'Riwayat Aktivitas' });
</script>