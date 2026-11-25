<template>
  <div class="space-y-5">
    <header>
      <h1 class="text-xl font-extrabold text-strong">Saran Pengguna</h1>
      <p class="mt-1 text-sm text-muted">Dengarkan pengalaman customer dan seller untuk mengembangkan Mahapreneur.</p>
    </header>

    <section class="grid gap-4 sm:grid-cols-2 lg:grid-cols-[minmax(280px,0.75fr)_minmax(0,2.25fr)]">
      <div class="card flex min-w-0 flex-col justify-center p-5">
        <p class="text-xs font-bold uppercase tracking-wide text-muted">Rating rata-rata</p>
        <div class="mt-3 flex flex-wrap items-center gap-x-3 gap-y-1">
          <span class="text-4xl font-extrabold text-strong">{{ summary.average.toFixed(1) }}</span>
          <RatingStars :rating="summary.average || null" size="size-5" :show-count="false" />
        </div>
        <p class="mt-2 text-sm text-muted">{{ summary.total }} masukan dari pengguna</p>
      </div>

      <div class="card p-5">
        <div class="flex flex-wrap items-center justify-between gap-2">
          <h2 class="text-sm font-bold text-strong">Distribusi rating</h2>
          <select v-model="ratingFilter" class="input !w-auto !py-2 text-xs" @change="applyFilter">
            <option value="">Semua rating</option>
            <option v-for="rating in [5, 4, 3, 2, 1]" :key="rating" :value="rating">{{ rating }} bintang</option>
          </select>
        </div>
        <div class="mt-4 space-y-2">
          <div v-for="rating in [5, 4, 3, 2, 1]" :key="rating" class="flex items-center gap-3 text-xs">
            <span class="w-5 shrink-0 font-semibold text-body">{{ rating }}</span>
            <div class="h-2 flex-1 overflow-hidden rounded-full bg-surface-muted">
              <div class="h-full rounded-full bg-amber-400 transition-all" :style="{ width: distributionWidth(rating) }" />
            </div>
            <span class="w-8 text-right text-muted">{{ summary.distribution[rating] ?? 0 }}</span>
          </div>
        </div>
      </div>
    </section>

    <section v-if="feedback.length" class="space-y-3">
      <div class="flex items-center justify-between">
        <h2 class="text-base font-bold text-strong">Masukan terbaru</h2>
        <span class="text-xs text-muted">{{ meta.total }} total</span>
      </div>

      <article v-for="item in feedback" :key="item.id" class="card p-5">
        <div class="flex flex-wrap items-start justify-between gap-3">
          <div class="flex min-w-0 items-center gap-3">
            <span class="grid size-10 shrink-0 place-items-center rounded-full bg-primary text-sm font-bold text-tertiary">
              {{ initials(item.user?.name ?? 'Pengguna') }}
            </span>
            <div class="min-w-0">
              <p class="truncate text-sm font-bold text-strong">{{ item.user?.name ?? 'Pengguna' }}</p>
              <p class="truncate text-xs text-muted">{{ roleLabel(item.user?.role ?? 'CUSTOMER') }} · {{ item.user?.email ?? '-' }}</p>
            </div>
          </div>
          <div class="text-right">
            <RatingStars :rating="item.rating" size="size-4" :show-count="false" />
            <p class="mt-1 text-[11px] text-muted">{{ formatDate(item.createdAt, true) }}</p>
          </div>
        </div>
        <p class="mt-4 whitespace-pre-line text-sm leading-6 text-body">{{ item.comment }}</p>
      </article>
    </section>

    <EmptyState v-else title="Belum ada saran pengguna" description="Masukan yang dikirim dari customer dan seller akan tampil di sini." />

    <PaginationBar :meta="meta" @change="changePage" />
  </div>
</template>

<script setup lang="ts">
import type { PaginationMeta, PlatformFeedback } from '~/types';

definePageMeta({ layout: 'dashboard', requiresAuth: true, roles: ['SUPERADMIN'] });

const api = useApi();
const page = ref(1);
const ratingFilter = ref('');

const query = computed(() => ({
  page: page.value,
  limit: 20,
  rating: ratingFilter.value || undefined,
}));

const { data, refresh } = await useAsyncData(
  'admin-platform-feedback',
  () => api.get<{
    summary: { total: number; average: number; distribution: Record<number, number> };
    feedback: PlatformFeedback[];
    meta: PaginationMeta;
  }>('/admin/feedback', query.value),
  { watch: [query] },
);

const summary = computed(() => data.value?.summary ?? { total: 0, average: 0, distribution: {} as Record<number, number> });
const feedback = computed(() => data.value?.feedback ?? []);
const meta = computed(() => data.value?.meta ?? { total: 0, page: 1, limit: 20, totalPages: 1 });

function distributionWidth(rating: number) {
  if (!summary.value.total) return '0%';
  return `${Math.round(((summary.value.distribution[rating] ?? 0) / summary.value.total) * 100)}%`;
}

function initials(name: string) {
  return name.split(' ').map((part) => part[0]).join('').slice(0, 2).toUpperCase();
}

function applyFilter() {
  page.value = 1;
  refresh();
}

function changePage(next: number) {
  page.value = next;
}

useSeoMeta({ title: 'Saran Pengguna' });
</script>
