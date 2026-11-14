<template>
  <Teleport to="body">
    <Transition
      enter-active-class="transition duration-150"
      enter-from-class="opacity-0"
      leave-active-class="transition duration-100"
      leave-to-class="opacity-0"
    >
      <div v-if="open" class="fixed inset-0 z-[70] grid place-items-center p-4">
        <div class="absolute inset-0 bg-neutral/60 backdrop-blur-sm" @click="emit('cancel')" />
        <div class="card relative w-full max-w-md p-6" role="dialog" aria-modal="true">
          <div class="flex items-start gap-3">
            <span class="grid size-10 shrink-0 place-items-center rounded-2xl" :class="toneClass">
              <component :is="icon" class="size-5" />
            </span>
            <div>
              <h3 class="text-base font-bold text-strong">{{ title }}</h3>
              <p class="mt-1 text-sm text-muted">{{ message }}</p>
            </div>
          </div>
          <div class="mt-6 flex justify-end gap-2">
            <button class="btn btn-ghost" @click="emit('cancel')">Batal</button>
            <button class="btn" :class="danger ? 'btn-danger' : 'btn-primary'" :disabled="loading" @click="emit('confirm')">
              <Loader2 v-if="loading" class="size-4 animate-spin" />
              {{ confirmLabel }}
            </button>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup lang="ts">
import { AlertTriangle, Loader2, ShieldCheck } from 'lucide-vue-next';

const props = withDefaults(
  defineProps<{
    open: boolean;
    title: string;
    message: string;
    confirmLabel?: string;
    danger?: boolean;
    loading?: boolean;
  }>(),
  { confirmLabel: 'Lanjutkan', danger: false, loading: false },
);
const emit = defineEmits<{ confirm: []; cancel: [] }>();

const icon = computed(() => (props.danger ? AlertTriangle : ShieldCheck));
const toneClass = computed(() =>
  props.danger
    ? 'bg-rose-100 text-rose-600 dark:bg-rose-500/15 dark:text-rose-300'
    : 'bg-tertiary text-neutral',
);
</script>