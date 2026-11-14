<template>
  <div class="page-container space-y-6 pt-6">
    <header>
      <p class="text-[11px] font-bold uppercase tracking-[0.14em] text-muted">Keranjang</p>
      <h1 class="mt-1 text-2xl font-extrabold text-strong sm:text-3xl">Tinjau keranjangmu</h1>
      <p class="mt-1 text-sm text-muted">
        Produk dari beberapa toko sekaligus diperbolehkan. Saat checkout, pesanan dipisah otomatis per seller.
      </p>
    </header>

    <div v-if="cart.loading" class="grid gap-4 lg:grid-cols-[1fr_360px]">
      <div class="card h-64 animate-pulse" />
      <div class="card h-64 animate-pulse" />
    </div>

    <div v-else-if="cart.items.length" class="grid items-start gap-6 lg:grid-cols-[1fr_360px]">
      <div class="space-y-4">
        <section v-for="group in cart.groups" :key="group.store?.id ?? 0" class="card overflow-hidden">
          <header class="flex items-center gap-3 border-b border-line px-5 py-4">
            <label class="flex items-center gap-2.5">
              <input
                type="checkbox"
                class="size-4 accent-[#102C40]"
                :checked="group.items.every((item) => selected.includes(item.id))"
                :aria-label="`Pilih semua produk dari ${group.store?.name}`"
                @change="toggleGroup(group.items.map((item) => item.id), ($event.target as HTMLInputElement).checked)"
              />
            </label>
            <NuxtLink v-if="group.store" :to="`/store/${group.store.slug}`" class="flex items-center gap-2 text-sm font-bold text-strong hover:underline">
              <Store class="size-4" /> {{ group.store.name }}
            </NuxtLink>
            <span v-else class="text-sm font-bold text-strong">Toko tidak diketahui</span>
            <span class="badge ml-auto bg-surface-muted text-muted">{{ group.items.length }} produk</span>
          </header>

          <ul class="divide-y divide-[color:var(--line)]">
            <li v-for="item in group.items" :key="item.id" class="flex gap-4 p-5">
              <input
                type="checkbox"
                class="mt-1 size-4 shrink-0 accent-[#102C40]"
                :checked="selected.includes(item.id)"
                :aria-label="`Pilih ${item.product.name}`"
                @change="toggleItem(item.id, ($event.target as HTMLInputElement).checked)"
              />
              <NuxtLink :to="`/product/${item.product.slug}`" class="size-20 shrink-0 overflow-hidden rounded-2xl bg-surface-muted">
                <img v-if="item.product.image" :src="item.product.image" :alt="item.product.name" class="size-full object-cover" />
              </NuxtLink>

              <div class="min-w-0 flex-1">
                <NuxtLink :to="`/product/${item.product.slug}`" class="line-clamp-2 text-sm font-bold text-strong hover:underline">
                  {{ item.product.name }}
                </NuxtLink>
                <p class="mt-0.5 text-xs text-muted">
                  {{ item.product.type === 'SERVICE' ? 'Jasa' : 'Barang' }} · {{ formatRupiah(item.product.price) }}
                </p>
                <p v-if="item.product.stock === 0" class="mt-1 text-xs font-semibold text-rose-600">Stok habis — hapus untuk melanjutkan</p>

                <div class="mt-3 flex flex-wrap items-center justify-between gap-3">
                  <QuantityStepper
                    :model-value="item.quantity"
                    :max="Math.max(1, item.product.stock)"
                    @update:model-value="(value) => changeQuantity(item.id, value)"
                  />
                  <div class="flex items-center gap-3">
                    <p class="text-sm font-extrabold text-strong">{{ formatRupiah(item.subtotal) }}</p>
                    <button class="text-muted hover:text-rose-600" :aria-label="`Hapus ${item.product.name}`" @click="pendingRemove = item">
                      <Trash2 class="size-4" />
                    </button>
                  </div>
                </div>
              </div>
            </li>
          </ul>
        </section>

        <NuxtLink to="/catalog" class="btn btn-ghost text-xs">
          <ArrowLeft class="size-4" /> Lanjut belanja
        </NuxtLink>
      </div>

      <!-- Ringkasan -->
      <aside class="card sticky top-24 p-5">
        <h2 class="text-base font-extrabold text-strong">Ringkasan pesanan</h2>

        <dl class="mt-4 space-y-3 text-sm">
          <div class="flex justify-between">
            <dt class="text-muted">Produk dipilih</dt>
            <dd class="font-semibold text-strong">{{ selectedItems.length }} item</dd>
          </div>
          <div class="flex justify-between">
            <dt class="text-muted">Jumlah toko</dt>
            <dd class="font-semibold text-strong">{{ selectedStoreCount }}</dd>
          </div>
          <div class="flex justify-between">
            <dt class="text-muted">Ongkos kirim</dt>
            <dd class="font-semibold text-emerald-600 dark:text-emerald-400">Tidak ada</dd>
          </div>
          <div class="flex items-center justify-between border-t border-line pt-3">
            <dt class="font-semibold text-strong">Total</dt>
            <dd class="text-xl font-extrabold text-strong">{{ formatRupiah(selectedTotal) }}</dd>
          </div>
        </dl>

        <p class="mt-4 flex items-start gap-2 rounded-xl bg-surface-muted p-3 text-[11px] leading-relaxed text-muted">
          <Bike class="mt-0.5 size-3.5 shrink-0" />
          Pesanan diantar sendiri oleh seller. Kamu mengisi alamat penerimaan pada langkah checkout.
        </p>

        <button class="btn btn-primary mt-5 w-full" :disabled="!selectedItems.length" @click="goCheckout">
          Lanjut ke checkout
          <ArrowRight class="size-4" />
        </button>
      </aside>
    </div>

    <EmptyState v-else title="Keranjangmu masih kosong" description="Jelajahi produk dan jasa dari toko mahasiswa, lalu tambahkan ke keranjang.">
      <NuxtLink to="/catalog" class="btn btn-primary">Mulai belanja</NuxtLink>
    </EmptyState>

    <ConfirmDialog
      :open="Boolean(pendingRemove)"
      title="Hapus produk dari keranjang?"
      :message="pendingRemove ? `${pendingRemove.product.name} akan dihapus dari keranjangmu.` : ''"
      confirm-label="Hapus"
      danger
      @confirm="removeItem"
      @cancel="pendingRemove = null"
    />
  </div>
</template>

<script setup lang="ts">
import { ArrowLeft, ArrowRight, Bike, Store, Trash2 } from 'lucide-vue-next';
import type { CartItem } from '~/types';

definePageMeta({ requiresAuth: true, roles: ['CUSTOMER'] });

const cart = useCartStore();
const ui = useUiStore();
const router = useRouter();

const selected = ref<number[]>([]);
const pendingRemove = ref<CartItem | null>(null);

const selectedItems = computed(() => cart.items.filter((item) => selected.value.includes(item.id)));
const selectedTotal = computed(() => selectedItems.value.reduce((sum, item) => sum + item.subtotal, 0));
const selectedStoreCount = computed(() => new Set(selectedItems.value.map((item) => item.product.store?.id)).size);

// Secara default semua item yang tersedia ikut terpilih.
function selectAvailable() {
  selected.value = cart.items.filter((item) => item.product.stock > 0).map((item) => item.id);
}

function toggleItem(id: number, checked: boolean) {
  selected.value = checked ? [...selected.value, id] : selected.value.filter((value) => value !== id);
}

function toggleGroup(ids: number[], checked: boolean) {
  selected.value = checked
    ? [...new Set([...selected.value, ...ids])]
    : selected.value.filter((value) => !ids.includes(value));
}

async function changeQuantity(itemId: number, quantity: number) {
  try {
    await cart.update(itemId, quantity);
  } catch (error) {
    ui.toast(apiError(error), 'error');
    cart.fetch();
  }
}

async function removeItem() {
  if (!pendingRemove.value) return;
  try {
    await cart.remove(pendingRemove.value.id);
    selected.value = selected.value.filter((value) => value !== pendingRemove.value?.id);
  } catch (error) {
    ui.toast(apiError(error), 'error');
  } finally {
    pendingRemove.value = null;
  }
}

function goCheckout() {
  if (!selectedItems.value.length) return;
  router.push({ path: '/customer/checkout', query: { items: selected.value.join(',') } });
}

watch(
  () => cart.items.map((item) => item.id).join(','),
  () => selectAvailable(),
  { immediate: true },
);

useSeoMeta({ title: 'Keranjang' });
</script>