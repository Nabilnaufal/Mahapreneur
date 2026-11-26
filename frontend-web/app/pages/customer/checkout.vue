<template>
  <div class="page-container space-y-6 pt-6">
    <header>
      <p class="text-[11px] font-bold uppercase tracking-[0.14em] text-muted">Checkout</p>
      <h1 class="mt-1 text-2xl font-extrabold text-strong sm:text-3xl">Selesaikan pesananmu</h1>
      <p class="mt-1 text-sm text-muted">Isi info penerimaan, pilih metode pembayaran, lalu buat pesanan.</p>
    </header>

    <div v-if="items.length" class="grid items-start gap-6 lg:grid-cols-[1fr_380px]">
      <div class="space-y-5">
        <!-- Info penerimaan -->
        <section class="card p-5">
          <div class="flex flex-wrap items-center justify-between gap-2">
            <h2 class="flex items-center gap-2 text-base font-extrabold text-strong"><MapPin class="size-4" /> Lokasi penerimaan</h2>
            <button v-if="addresses.length" class="btn btn-outline !py-1.5 text-xs" @click="showAddresses = !showAddresses">
              <BookmarkCheck class="size-3.5" /> Pakai alamat tersimpan
            </button>
          </div>

          <div v-if="showAddresses && addresses.length" class="mt-4 grid gap-2 sm:grid-cols-2">
            <button
              v-for="address in addresses"
              :key="address.id"
              class="rounded-2xl border p-3 text-left text-xs transition hover:border-line-strong"
              :class="address.isDefault ? 'border-primary' : 'border-line'"
              @click="useAddress(address)"
            >
              <p class="font-bold text-strong">{{ address.label }}</p>
              <p class="mt-1 text-muted">{{ address.recipientName }} · {{ address.phone }}</p>
              <p class="mt-1 line-clamp-2 text-muted">{{ address.address }}</p>
            </button>
          </div>

          <div class="mt-4 grid gap-4 sm:grid-cols-2">
            <div>
              <label class="label" for="recipientName">Nama penerima</label>
              <input id="recipientName" v-model="form.recipientName" class="input" placeholder="Nama yang menerima pesanan" />
            </div>
            <div>
              <label class="label" for="recipientPhone">Nomor telepon</label>
              <input id="recipientPhone" v-model="form.recipientPhone" class="input" placeholder="08xxxxxxxxxx" />
            </div>
            <div class="sm:col-span-2">
              <label class="label" for="address">Alamat atau lokasi</label>
              <textarea id="address" v-model="form.address" rows="3" class="input" placeholder="Contoh: Kos Melati, Jl. Melati No. 21, Depok" />
            </div>
            <div>
              <label class="label" for="campus">Gedung / kampus <span class="font-normal text-muted">(opsional)</span></label>
              <input id="campus" v-model="form.campus" class="input" placeholder="Contoh: Gedung A - Fakultas Ekonomi" />
            </div>
            <div>
              <label class="label" for="room">Kelas / ruangan <span class="font-normal text-muted">(opsional)</span></label>
              <input id="room" v-model="form.room" class="input" placeholder="Contoh: Ruang 204" />
            </div>
            <div class="sm:col-span-2">
              <label class="label" for="note">Catatan untuk seller <span class="font-normal text-muted">(opsional)</span></label>
              <textarea id="note" v-model="form.note" rows="2" class="input" placeholder="Contoh: Tolong antar sebelum jam 12, ada kelas." />
            </div>
          </div>

          <label class="mt-4 flex cursor-pointer items-center gap-2.5 text-xs text-body">
            <input v-model="form.saveAddress" type="checkbox" class="size-4 accent-[#102C40]" />
            Simpan lokasi ini ke daftar alamat saya
          </label>
        </section>

        <!-- Metode pembayaran -->
        <section class="card p-5">
          <h2 class="flex items-center gap-2 text-base font-extrabold text-strong"><Wallet class="size-4" /> Metode pembayaran</h2>

          <p v-if="methods.length && !dokuConfigured" class="mt-3 flex items-start gap-2 rounded-xl bg-amber-50 p-3 text-[11px] leading-relaxed text-amber-800 dark:bg-amber-500/10 dark:text-amber-300">
            <Info class="mt-0.5 size-3.5 shrink-0" />
            Pembayaran online belum tersedia. Untuk sementara, pilih COD atau hubungi admin.
          </p>

          <div class="mt-4 space-y-3">
            <label
              v-for="method in methods"
              :key="method.code"
              class="flex cursor-pointer items-start gap-3 rounded-2xl border p-4 transition"
              :class="form.paymentMethod === method.code ? 'border-primary bg-primary/5 dark:bg-primary-soft/20' : 'border-line hover:border-line-strong'"
            >
              <input v-model="form.paymentMethod" type="radio" :value="method.code" class="mt-1 size-4 accent-[#102C40]" />
              <span class="grid size-9 shrink-0 place-items-center rounded-xl bg-surface-muted text-primary dark:text-tertiary">
                <component :is="methodIcon(method.code)" class="size-4.5" />
              </span>
              <span class="min-w-0">
                <span class="flex flex-wrap items-center gap-2">
                  <span class="text-sm font-bold text-strong">{{ method.label }}</span>
                  <span v-if="method.type === 'cod'" class="badge bg-tertiary text-neutral">Tanpa DOKU</span>
                </span>
                <span class="mt-1 block text-xs leading-relaxed text-muted">{{ method.description }}</span>
              </span>
            </label>
          </div>
        </section>
      </div>

      <!-- Ringkasan -->
      <aside class="card sticky top-24 p-5">
        <h2 class="text-base font-extrabold text-strong">Ringkasan pesanan</h2>

        <ul class="mt-4 max-h-64 space-y-3 overflow-y-auto pr-1">
          <li v-for="item in items" :key="item.id" class="flex gap-3">
            <span class="size-12 shrink-0 overflow-hidden rounded-xl bg-surface-muted">
              <img v-if="item.product.image" :src="item.product.image" :alt="item.product.name" class="size-full object-cover" />
            </span>
            <span class="min-w-0 flex-1">
              <span class="line-clamp-1 text-xs font-semibold text-strong">{{ item.product.name }}</span>
              <span class="block text-[11px] text-muted">{{ item.quantity }} × {{ formatRupiah(item.product.price) }}</span>
            </span>
            <span class="text-xs font-bold text-strong">{{ formatRupiah(item.subtotal) }}</span>
          </li>
        </ul>

        <dl class="mt-4 space-y-2.5 border-t border-line pt-4 text-sm">
          <div class="flex justify-between">
            <dt class="text-muted">Subtotal</dt>
            <dd class="font-semibold text-strong">{{ formatRupiah(subtotal) }}</dd>
          </div>
          <div class="flex justify-between">
            <dt class="text-muted">Ongkos kirim</dt>
            <dd class="font-semibold text-emerald-600 dark:text-emerald-400">Tidak ada</dd>
          </div>
          <div class="flex justify-between">
            <dt class="text-muted">Pesanan dibuat</dt>
            <dd class="font-semibold text-strong">{{ storeCount }} pesanan ({{ storeCount }} seller)</dd>
          </div>
          <div class="flex items-center justify-between border-t border-line pt-3">
            <dt class="font-semibold text-strong">Total bayar</dt>
            <dd class="text-xl font-extrabold text-strong">{{ formatRupiah(subtotal) }}</dd>
          </div>
        </dl>

        <button class="btn btn-primary mt-5 w-full" :disabled="submitting" @click="submit">
          <Loader2 v-if="submitting" class="size-4 animate-spin" />
          {{ submitting ? 'Membuat pesanan…' : 'Buat pesanan' }}
        </button>

        <p class="mt-3 text-center text-[11px] leading-relaxed text-muted">
          Total dihitung ulang di server saat pesanan dibuat, termasuk validasi stok.
        </p>
      </aside>
    </div>

    <EmptyState v-else title="Tidak ada produk untuk di-checkout" description="Pilih produk di keranjang terlebih dahulu.">
      <NuxtLink to="/customer/cart" class="btn btn-primary">Buka keranjang</NuxtLink>
    </EmptyState>
  </div>
</template>

<script setup lang="ts">
import { Banknote, BookmarkCheck, CreditCard, Info, Loader2, MapPin, QrCode, Wallet } from 'lucide-vue-next';
import type { Address, CartItem, PaymentMethod } from '~/types';

definePageMeta({ requiresAuth: true, roles: ['CUSTOMER'] });

const api = useApi();
const cart = useCartStore();
const auth = useAuthStore();
const ui = useUiStore();
const route = useRoute();
const router = useRouter();

const submitting = ref(false);
const showAddresses = ref(false);

const form = reactive({
  recipientName: auth.user?.name ?? '',
  recipientPhone: auth.user?.phone ?? '',
  address: '',
  campus: '',
  room: '',
  note: '',
  paymentMethod: 'OVO' as PaymentMethod,
  saveAddress: true,
});

const selectedIds = computed(() =>
  ((route.query.items as string) ?? '')
    .split(',')
    .map((value) => Number(value))
    .filter((value) => Number.isFinite(value) && value > 0),
);

const items = computed<CartItem[]>(() => cart.items.filter((item) => selectedIds.value.includes(item.id)));
const subtotal = computed(() => items.value.reduce((sum, item) => sum + item.subtotal, 0));
const storeCount = computed(() => new Set(items.value.map((item) => item.product.store?.id)).size);

const { data: methodData } = await useAsyncData('payment-methods', () =>
  api.get<{ methods: { code: PaymentMethod; label: string; description: string; type: string }[]; dokuConfigured: boolean }>('/payments/methods'),
);
const { data: addressData } = await useAsyncData('checkout-addresses', () =>
  api.get<{ addresses: Address[] }>('/customer/addresses').catch(() => ({ addresses: [] as Address[] })),
);

const methods = computed(() => methodData.value?.methods ?? []);
const dokuConfigured = computed(() => methodData.value?.dokuConfigured ?? false);
const addresses = computed(() => addressData.value?.addresses ?? []);

watch(methods, (availableMethods) => {
  if (!availableMethods.some((method) => method.code === form.paymentMethod)) {
    form.paymentMethod = availableMethods[0]?.code ?? 'COD';
  }
}, { immediate: true });

const methodIcon = (code: string) =>
  ({ OVO: Wallet, DANA: Wallet, SHOPEEPAY: Wallet, QRIS: QrCode, VIRTUAL_ACCOUNT: CreditCard, EWALLET: Wallet, COD: Banknote })[code] ?? Wallet;

function useAddress(address: Address) {
  form.recipientName = address.recipientName;
  form.recipientPhone = address.phone;
  form.address = address.address;
  form.campus = address.campus ?? '';
  form.room = address.room ?? '';
  showAddresses.value = false;
}

async function submit() {
  if (!form.recipientName.trim() || form.recipientPhone.trim().length < 8 || form.address.trim().length < 10) {
    return ui.toast('Lengkapi nama penerima, nomor telepon, dan alamat (min. 10 karakter).', 'error');
  }

  submitting.value = true;
  try {
    const data = await api.post<{ orders: { id: number; orderNumber: string; paymentMethod: string }[]; ordersCount: number }>(
      '/customer/checkout',
      { cartItemIds: selectedIds.value, ...form },
    );
    await cart.fetch();
    ui.toast(`${data.ordersCount} pesanan berhasil dibuat.`, 'success');

    // Pesanan online diarahkan ke halaman instruksi pembayaran pesanan pertama.
    const first = data.orders[0];
    if (first && first.paymentMethod !== 'COD') router.push(`/customer/orders/${first.id}/payment`);
    else router.push('/customer/orders');
  } catch (error) {
    ui.toast(apiError(error, 'Checkout gagal'), 'error');
  } finally {
    submitting.value = false;
  }
}

onMounted(async () => {
  if (!cart.items.length) await cart.fetch();
  // Prefill dari alamat default bila tersedia.
  const preferred = addresses.value.find((address) => address.isDefault) ?? addresses.value[0];
  if (preferred && !form.address) useAddress(preferred);
});

useSeoMeta({ title: 'Checkout' });
</script>
