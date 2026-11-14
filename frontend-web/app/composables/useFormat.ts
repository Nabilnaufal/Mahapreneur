import type { OrderStatus, PaymentStatus } from '~/types';

export const formatRupiah = (value: number) =>
  new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(value ?? 0);

export const formatNumber = (value: number) => new Intl.NumberFormat('id-ID').format(value ?? 0);

export const formatDate = (value?: string | null, withTime = false) => {
  if (!value) return '-';
  const date = new Date(value);
  return date.toLocaleDateString('id-ID', {
    day: 'numeric',
    month: 'long',
    year: 'numeric',
    ...(withTime ? { hour: '2-digit', minute: '2-digit' } : {}),
  });
};

export const formatRelative = (value?: string | null) => {
  if (!value) return '-';
  const diff = Date.now() - new Date(value).getTime();
  const minutes = Math.round(diff / 60000);
  if (minutes < 1) return 'baru saja';
  if (minutes < 60) return `${minutes} menit lalu`;
  const hours = Math.round(minutes / 60);
  if (hours < 24) return `${hours} jam lalu`;
  const days = Math.round(hours / 24);
  if (days < 30) return `${days} hari lalu`;
  return formatDate(value);
};

export const ORDER_STATUS_LABEL: Record<OrderStatus, string> = {
  WAITING_PAYMENT: 'Menunggu pembayaran',
  CONFIRMED: 'Dikonfirmasi',
  PROCESSING: 'Diproses',
  READY: 'Siap diserahkan',
  ON_DELIVERY: 'Sedang diantarkan',
  COMPLETED: 'Selesai',
  CANCELLED: 'Dibatalkan',
};

export const PAYMENT_STATUS_LABEL: Record<PaymentStatus, string> = {
  PENDING: 'Menunggu pembayaran',
  PAID: 'Lunas',
  FAILED: 'Gagal',
  EXPIRED: 'Kedaluwarsa',
  UNPAID_COD: 'Bayar di tempat (belum lunas)',
  REFUNDED: 'Dana dikembalikan',
};

export const PAYMENT_METHOD_LABEL: Record<string, string> = {
  QRIS: 'QRIS',
  VIRTUAL_ACCOUNT: 'Virtual Account',
  EWALLET: 'E-Wallet',
  COD: 'Bayar di Tempat (COD)',
};

/** Warna badge status agar konsisten di seluruh aplikasi. */
export const ORDER_STATUS_TONE: Record<OrderStatus, string> = {
  WAITING_PAYMENT: 'bg-amber-100 text-amber-800 dark:bg-amber-500/15 dark:text-amber-300',
  CONFIRMED: 'bg-sky-100 text-sky-800 dark:bg-sky-500/15 dark:text-sky-300',
  PROCESSING: 'bg-indigo-100 text-indigo-800 dark:bg-indigo-500/15 dark:text-indigo-300',
  READY: 'bg-violet-100 text-violet-800 dark:bg-violet-500/15 dark:text-violet-300',
  ON_DELIVERY: 'bg-blue-100 text-blue-800 dark:bg-blue-500/15 dark:text-blue-300',
  COMPLETED: 'bg-emerald-100 text-emerald-800 dark:bg-emerald-500/15 dark:text-emerald-300',
  CANCELLED: 'bg-rose-100 text-rose-800 dark:bg-rose-500/15 dark:text-rose-300',
};

export const PAYMENT_STATUS_TONE: Record<PaymentStatus, string> = {
  PENDING: 'bg-amber-100 text-amber-800 dark:bg-amber-500/15 dark:text-amber-300',
  PAID: 'bg-emerald-100 text-emerald-800 dark:bg-emerald-500/15 dark:text-emerald-300',
  FAILED: 'bg-rose-100 text-rose-800 dark:bg-rose-500/15 dark:text-rose-300',
  EXPIRED: 'bg-slate-200 text-slate-700 dark:bg-slate-500/20 dark:text-slate-300',
  UNPAID_COD: 'bg-orange-100 text-orange-800 dark:bg-orange-500/15 dark:text-orange-300',
  REFUNDED: 'bg-cyan-100 text-cyan-800 dark:bg-cyan-500/15 dark:text-cyan-300',
};

export const orderStatusLabel = (status: OrderStatus) => ORDER_STATUS_LABEL[status] ?? status;
export const paymentStatusLabel = (status: PaymentStatus) => PAYMENT_STATUS_LABEL[status] ?? status;
export const paymentMethodLabel = (method: string) => PAYMENT_METHOD_LABEL[method] ?? method;

export const ROLE_LABEL: Record<string, string> = {
  SUPERADMIN: 'Superadmin',
  SELLER: 'Seller',
  CUSTOMER: 'Customer',
};

export const STORE_STATUS_LABEL: Record<string, string> = {
  ACTIVE: 'Aktif',
  INACTIVE: 'Nonaktif',
  SUSPENDED: 'Ditangguhkan',
};

export const STORE_STATUS_TONE: Record<string, string> = {
  ACTIVE: 'bg-emerald-100 text-emerald-800 dark:bg-emerald-500/15 dark:text-emerald-300',
  INACTIVE: 'bg-slate-200 text-slate-700 dark:bg-slate-500/20 dark:text-slate-300',
  SUSPENDED: 'bg-rose-100 text-rose-800 dark:bg-rose-500/15 dark:text-rose-300',
};

export const roleLabel = (role: string) => ROLE_LABEL[role] ?? role;
export const storeStatusLabel = (status: string) => STORE_STATUS_LABEL[status] ?? status;
