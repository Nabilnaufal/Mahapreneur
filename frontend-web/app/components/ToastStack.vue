<template>
  <div class="pointer-events-none fixed inset-x-0 top-3 z-[60] flex flex-col items-center gap-2 px-4">
    <TransitionGroup
      enter-active-class="transition duration-200"
      enter-from-class="opacity-0 -translate-y-2"
      leave-active-class="transition duration-150"
      leave-to-class="opacity-0"
    >
      <button
        v-for="toast in ui.toasts"
        :key="toast.id"
        class="pointer-events-auto flex w-full max-w-sm items-start gap-3 rounded-2xl border px-4 py-3 text-left text-sm shadow-lg backdrop-blur"
        :class="tone(toast.type)"
        @click="ui.dismissToast(toast.id)"
      >
        <component :is="icon(toast.type)" class="mt-0.5 size-4 shrink-0" />
        <span class="font-medium">{{ toast.message }}</span>
      </button>
    </TransitionGroup>
  </div>
</template>

<script setup lang="ts">
import { AlertCircle, CheckCircle2, Info } from 'lucide-vue-next';

const ui = useUiStore();

const tone = (type: string) =>
  ({
    success: 'border-emerald-200 bg-emerald-50 text-emerald-900 dark:border-emerald-500/30 dark:bg-emerald-500/15 dark:text-emerald-200',
    error: 'border-rose-200 bg-rose-50 text-rose-900 dark:border-rose-500/30 dark:bg-rose-500/15 dark:text-rose-200',
    info: 'border-line-strong bg-surface text-strong',
  })[type] ?? 'border-line-strong bg-surface text-strong';

const icon = (type: string) => ({ success: CheckCircle2, error: AlertCircle, info: Info })[type] ?? Info;
</script>