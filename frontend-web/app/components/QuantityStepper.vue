<template>
  <div class="inline-flex items-center rounded-full border border-line bg-surface">
    <button class="grid size-9 place-items-center rounded-full text-body hover:text-strong disabled:opacity-40" :disabled="modelValue <= min" aria-label="Kurangi" @click="setValue(modelValue - 1)">
      <Minus class="size-4" />
    </button>
    <span class="w-9 text-center text-sm font-bold text-strong">{{ modelValue }}</span>
    <button class="grid size-9 place-items-center rounded-full text-body hover:text-strong disabled:opacity-40" :disabled="modelValue >= max" aria-label="Tambah" @click="setValue(modelValue + 1)">
      <Plus class="size-4" />
    </button>
  </div>
</template>

<script setup lang="ts">
import { Minus, Plus } from 'lucide-vue-next';

const props = withDefaults(defineProps<{ modelValue: number; min?: number; max?: number }>(), { min: 1, max: 99 });
const emit = defineEmits<{ 'update:modelValue': [value: number] }>();

function setValue(value: number) {
  if (value < props.min || value > props.max) return;
  emit('update:modelValue', value);
}
</script>