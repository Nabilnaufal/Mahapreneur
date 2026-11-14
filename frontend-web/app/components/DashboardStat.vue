<template>
  <div class="card p-5">
    <div class="flex items-start justify-between gap-3">
      <p class="text-xs font-bold uppercase tracking-wide text-muted">{{ label }}</p>
      <span class="grid size-9 place-items-center rounded-xl" :class="tone">
        <component :is="icon" class="size-4.5" />
      </span>
    </div>
    <p class="mt-3 text-2xl font-extrabold text-strong">{{ value }}</p>
    <p v-if="hint" class="mt-1 text-xs text-muted">{{ hint }}</p>
  </div>
</template>

<script setup lang="ts">
withDefaults(
  defineProps<{
    label: string;
    value: string | number;
    hint?: string;
    icon?: unknown;
    tone?: string;
  }>(),
  { tone: 'bg-surface-muted text-primary dark:text-tertiary' },
);
</script>