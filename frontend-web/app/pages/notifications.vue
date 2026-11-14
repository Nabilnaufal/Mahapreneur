<template>
  <div class="page-container max-w-3xl space-y-5 pt-6">
    <header class="flex flex-wrap items-end justify-between gap-3">
      <div>
        <h1 class="text-2xl font-extrabold text-strong">Notifikasi</h1>
        <p class="mt-1 text-sm text-muted">{{ unread }} notifikasi belum dibaca.</p>
      </div>
      <button v-if="unread" class="btn btn-outline text-xs" @click="readAll">Tandai semua dibaca</button>
    </header>

    <div v-if="notifications.length" class="space-y-3">
      <article
        v-for="item in notifications"
        :key="item.id"
        class="card flex items-start gap-3 p-4 transition"
        :class="item.isRead ? '' : 'border-primary/30'"
      >
        <span class="mt-0.5 grid size-9 shrink-0 place-items-center rounded-xl" :class="tone(item.type)">
          <component :is="iconFor(item.type)" class="size-4" />
        </span>
        <div class="min-w-0 flex-1">
          <div class="flex items-start justify-between gap-3">
            <p class="text-sm font-bold text-strong">{{ item.title }}</p>
            <span v-if="!item.isRead" class="mt-1.5 size-2 shrink-0 rounded-full bg-tertiary" />
          </div>
          <p class="mt-1 text-sm leading-relaxed text-body">{{ item.message }}</p>
          <div class="mt-2 flex items-center gap-3">
            <span class="text-[11px] text-muted">{{ formatRelative(item.createdAt) }}</span>
            <NuxtLink v-if="item.link" :to="item.link" class="text-[11px] font-semibold text-strong underline" @click="markRead(item.id)">
              Lihat detail
            </NuxtLink>
          </div>
        </div>
      </article>
    </div>

    <PaginationBar v-if="notifications.length" :meta="meta" @change="changePage" />

    <EmptyState v-else title="Belum ada notifikasi" description="Pemberitahuan tentang pesanan dan pembayaran akan muncul di sini." />
  </div>
</template>

<script setup lang="ts">
import { AlertTriangle, Bell, CheckCircle2, Info } from 'lucide-vue-next';
import type { Notification, PaginationMeta } from '~/types';

definePageMeta({ requiresAuth: true });

const api = useApi();
const ui = useUiStore();
const route = useRoute();
const router = useRouter();
const page = ref(Number(route.query.page) || 1);

const { data, refresh } = await useAsyncData(
  'notifications-page',
  () => api.get<{ notifications: Notification[]; unread: number; meta: PaginationMeta }>('/notifications', { page: page.value }),
  { watch: [page] },
);

const notifications = computed(() => data.value?.notifications ?? []);
const unread = computed(() => data.value?.unread ?? 0);
const meta = computed(() => data.value?.meta ?? { total: 0, page: 1, limit: 12, totalPages: 1 });

function changePage(next: number) {
  page.value = next;
  router.replace({ query: { ...route.query, page: next > 1 ? next : undefined } });
  if (import.meta.client) window.scrollTo({ top: 0, behavior: 'smooth' });
}

const tone = (type: string) =>
  ({
    success: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300',
    warning: 'bg-amber-100 text-amber-700 dark:bg-amber-500/15 dark:text-amber-300',
    error: 'bg-rose-100 text-rose-700 dark:bg-rose-500/15 dark:text-rose-300',
    info: 'bg-surface-muted text-primary dark:text-tertiary',
  })[type] ?? 'bg-surface-muted text-primary';

const iconFor = (type: string) =>
  ({ success: CheckCircle2, warning: AlertTriangle, error: AlertTriangle, info: Info })[type] ?? Bell;

async function markRead(id: number) {
  await api.patch(`/notifications/${id}/read`).catch(() => null);
  await refresh();
}

async function readAll() {
  try {
    await api.patch('/notifications/read-all');
    ui.toast('Semua notifikasi ditandai dibaca.', 'success');
    await refresh();
  } catch (error) {
    ui.toast(apiError(error), 'error');
  }
}

useSeoMeta({ title: 'Notifikasi' });
</script>