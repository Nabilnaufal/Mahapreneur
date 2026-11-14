<template>
  <div class="card p-5">
    <div class="flex items-center justify-between">
      <p class="flex items-center gap-2 text-sm font-bold text-strong"><ListFilter class="size-4" /> Filter</p>
      <button class="text-xs font-semibold text-muted hover:text-strong" @click="emit('reset')">Reset</button>
    </div>

    <div class="mt-5 space-y-6">
      <div>
        <p class="text-xs font-bold uppercase tracking-wide text-muted">Kategori</p>
        <div class="mt-3 space-y-2">
          <label v-for="category in categories" :key="category.id" class="flex cursor-pointer items-center gap-2.5 text-sm">
            <input
              type="radio"
              class="size-4 accent-[#102C40]"
              :checked="modelValue.category === category.slug"
              @change="emit('update:modelValue', { ...modelValue, category: category.slug })"
            />
            <span class="text-body">{{ category.name }}</span>
            <span v-if="category.productCount !== undefined" class="ml-auto text-xs text-muted">{{ category.productCount }}</span>
          </label>
        </div>
      </div>

      <div>
        <p class="text-xs font-bold uppercase tracking-wide text-muted">Rentang harga</p>
        <div class="mt-3 flex items-center gap-2">
          <input
            :value="modelValue.minPrice"
            type="number"
            min="0"
            placeholder="Min"
            class="input !py-2 text-xs"
            aria-label="Harga minimum"
            @input="update('minPrice', ($event.target as HTMLInputElement).value)"
          />
          <span class="text-muted">–</span>
          <input
            :value="modelValue.maxPrice"
            type="number"
            min="0"
            placeholder="Max"
            class="input !py-2 text-xs"
            aria-label="Harga maksimum"
            @input="update('maxPrice', ($event.target as HTMLInputElement).value)"
          />
        </div>
        <div class="mt-3 flex flex-wrap gap-2">
          <button
            v-for="range in priceRanges"
            :key="range.label"
            class="badge border border-line px-2.5 py-1.5 text-[10px] text-body hover:border-line-strong"
            @click="emit('update:modelValue', { ...modelValue, minPrice: range.min, maxPrice: range.max })"
          >
            {{ range.label }}
          </button>
        </div>
      </div>

      <div v-if="stores.length">
        <p class="text-xs font-bold uppercase tracking-wide text-muted">Toko</p>
        <div class="mt-3 space-y-2">
          <label v-for="store in stores" :key="store.id" class="flex cursor-pointer items-center gap-2.5 text-sm">
            <input
              type="radio"
              class="size-4 accent-[#102C40]"
              :checked="modelValue.store === store.slug"
              @change="emit('update:modelValue', { ...modelValue, store: store.slug })"
            />
            <span class="truncate text-body">{{ store.name }}</span>
          </label>
        </div>
      </div>

      <div>
        <p class="text-xs font-bold uppercase tracking-wide text-muted">Jenis</p>
        <div class="mt-3 flex gap-2">
          <button
            v-for="option in typeOptions"
            :key="option.value"
            class="badge flex-1 justify-center border px-3 py-2 text-[10px]"
            :class="modelValue.type === option.value ? 'border-primary bg-primary text-white' : 'border-line text-body'"
            @click="emit('update:modelValue', { ...modelValue, type: modelValue.type === option.value ? '' : option.value })"
          >
            {{ option.label }}
          </button>
        </div>
      </div>

      <label class="flex cursor-pointer items-center gap-2.5 text-sm">
        <input
          type="checkbox"
          class="size-4 accent-[#102C40]"
          :checked="modelValue.inStock === 'true'"
          @change="emit('update:modelValue', { ...modelValue, inStock: modelValue.inStock === 'true' ? '' : 'true' })"
        />
        <span class="text-body">Hanya yang tersedia</span>
      </label>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ListFilter } from 'lucide-vue-next';
import type { Category, Store } from '~/types';

interface Filters {
  q: string;
  category: string;
  store: string;
  minPrice: string;
  maxPrice: string;
  type: string;
  sort: string;
  inStock?: string;
}

const props = defineProps<{ categories: Category[]; stores: Store[]; modelValue: Filters }>();
const emit = defineEmits<{ 'update:modelValue': [value: Filters]; reset: [] }>();

const priceRanges = [
  { label: '< Rp25rb', min: '0', max: '25000' },
  { label: 'Rp25rb–50rb', min: '25000', max: '50000' },
  { label: 'Rp50rb–100rb', min: '50000', max: '100000' },
  { label: '> Rp100rb', min: '100000', max: '' },
];

const typeOptions = [
  { label: 'Barang', value: 'GOODS' },
  { label: 'Jasa', value: 'SERVICE' },
];

const update = (key: keyof Filters, value: string) => emit('update:modelValue', { ...props.modelValue, [key]: value });
</script>