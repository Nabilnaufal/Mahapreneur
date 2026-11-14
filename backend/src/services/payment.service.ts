import { prisma } from '../config/prisma.js';
import { dokuConfigured, env } from '../config/env.js';
import { doku } from '../config/doku.js';
import { invoiceNumber } from '../utils/security.js';
import { logActivity, notify } from '../utils/activity.js';

/**
 * Satu-satunya jalur yang boleh mengubah status pembayaran menjadi PAID.
 * Dipanggil dari webhook DOKU yang sudah tervalidasi, atau dari simulasi
 * pembayaran yang khusus pengembangan.
 */
export async function markPaymentPaid(input: {
  invoiceNumber: string;
  amount?: number | null;
  channel?: string | null;
  source: 'DOKU_WEBHOOK' | 'SIMULATION' | 'COD';
  rawPayload?: unknown;
}) {
  const payment = await prisma.payment.findUnique({
    where: { invoiceNumber: input.invoiceNumber },
    include: { order: { include: { store: true } } },
  });
  if (!payment) return { status: 'NOT_FOUND' as const };

  // Validasi nominal: tolak notifikasi yang jumlahnya tidak sesuai tagihan.
  if (input.amount != null && input.amount !== payment.amount) {
    await logActivity({
      userId: null,
      action: 'PAYMENT_AMOUNT_MISMATCH',
      entity: 'Payment',
      entityId: payment.id,
      description: `Nominal notifikasi (${input.amount}) tidak sama dengan tagihan (${payment.amount}) untuk ${payment.invoiceNumber}`,
      meta: { raw: input.rawPayload as Record<string, unknown> },
    });
    return { status: 'AMOUNT_MISMATCH' as const };
  }

  // Idempotency: pembayaran yang sudah PAID tidak diproses ulang.
  if (payment.status === 'PAID') return { status: 'ALREADY_PAID' as const };

  const order = payment.order;
  const now = new Date();

  await prisma.$transaction([
    prisma.payment.update({
      where: { id: payment.id },
      data: { status: 'PAID', paidAt: now, dokuChannel: input.channel ?? payment.dokuChannel },
    }),
    prisma.order.update({
      where: { id: order.id },
      data: {
        paymentStatus: 'PAID',
        // Pembayaran berhasil → pesanan diteruskan ke seller.
        status: order.status === 'WAITING_PAYMENT' ? 'CONFIRMED' : order.status,
        confirmedAt: order.confirmedAt ?? now,
      },
    }),
    prisma.orderStatusHistory.create({
      data: {
        orderId: order.id,
        status: 'CONFIRMED',
        note: `Pembayaran diterima${input.source === 'SIMULATION' ? ' (simulasi pengembangan)' : ''}`,
      },
    }),
  ]);

  await Promise.all([
    notify({
      userId: order.customerId,
      title: 'Pembayaran berhasil',
      message: `Pembayaran pesanan ${order.orderNumber} sudah kami terima. Pesanan diteruskan ke ${order.store.name}.`,
      type: 'success',
      link: `/customer/orders/${order.id}`,
    }),
    notify({
      userId: order.store.userId,
      title: 'Pesanan baru sudah dibayar',
      message: `Pesanan ${order.orderNumber} sudah dibayar. Siap diproses.`,
      type: 'success',
      link: `/seller/orders/${order.id}`,
    }),
    logActivity({
      userId: order.customerId,
      action: 'PAYMENT_PAID',
      entity: 'Payment',
      entityId: payment.id,
      description: `Pembayaran ${payment.invoiceNumber} sebesar ${payment.amount} berhasil via ${input.source}`,
    }),
  ]);

  return { status: 'PAID' as const, orderId: order.id, orderNumber: order.orderNumber };
}

/** Tandai pembayaran gagal/kedaluwarsa (tidak mengubah status pesanan). */
export async function markPaymentFailed(invoice: string, status: 'FAILED' | 'EXPIRED', note?: string) {
  const payment = await prisma.payment.findUnique({ where: { invoiceNumber: invoice }, include: { order: true } });
  if (!payment || payment.status === 'PAID') return;
  await prisma.$transaction([
    prisma.payment.update({ where: { id: payment.id }, data: { status } }),
    prisma.order.update({ where: { id: payment.orderId }, data: { paymentStatus: status } }),
  ]);
  await notify({
    userId: payment.order.customerId,
    title: status === 'EXPIRED' ? 'Pembayaran kedaluwarsa' : 'Pembayaran gagal',
    message: `${note ?? 'Silakan lakukan pembayaran ulang untuk pesanan ' + payment.order.orderNumber}`,
    type: 'error',
    link: `/customer/orders/${payment.orderId}`,
  });
}

export function paymentExpiry() {
  return new Date(Date.now() + env.doku.paymentTtlMinutes * 60 * 1000);
}

/**
 * Siapkan data pembayaran untuk satu pesanan.
 * Bila kredensial DOKU belum diisi, sistem memakai mode simulasi pengembangan
 * dan menandainya lewat flag `simulated` agar frontend bisa menampilkan jelas.
 */
export async function prepareOnlinePayment(orderId: number) {
  const order = await prisma.order.findUnique({ where: { id: orderId }, include: { payment: true, customer: true } });
  if (!order) return null;
  if (order.paymentMethod === 'COD') return null;

  const invoice = order.payment?.invoiceNumber ?? invoiceNumber(order.orderNumber);
  const expiresAt = order.payment?.expiresAt ?? paymentExpiry();

  let paymentUrl = order.payment?.dokuPaymentUrl ?? null;
  let tokenId: string | undefined;

  if (dokuConfigured) {
    try {
      const created = await doku.createInvoice({
        invoiceNumber: invoice,
        amount: order.total,
        customerName: order.customer.name,
        customerEmail: order.customer.email,
        description: `Pesanan ${order.orderNumber}`,
      });
      paymentUrl = created.paymentUrl ?? paymentUrl;
      tokenId = created.tokenId;
    } catch (err) {
      console.error('[doku] gagal membuat invoice', err);
    }
  } else {
    paymentUrl = `${env.appUrl}/payment/${order.orderNumber}`;
  }

  const payment = await prisma.payment.upsert({
    where: { orderId },
    create: {
      orderId,
      method: order.paymentMethod,
      status: 'PENDING',
      amount: order.total,
      invoiceNumber: invoice,
      dokuPaymentUrl: paymentUrl,
      dokuChannel: order.paymentMethod === 'QRIS' ? env.doku.qrisChannel : order.paymentMethod === 'VIRTUAL_ACCOUNT' ? env.doku.vaChannel : env.doku.ewalletChannel,
      expiresAt,
    },
    update: { dokuPaymentUrl: paymentUrl, expiresAt },
  });

  return { payment, tokenId, simulated: !dokuConfigured };
}

/** Daftar channel pembayaran yang tersedia pada konfigurasi DOKU. */
export async function availableChannels(method: string) {
  if (!dokuConfigured) {
    return [
      { channelCode: env.doku.qrisChannel, label: 'QRIS (semua e-wallet & m-banking)', method: 'QRIS' },
      { channelCode: env.doku.vaChannel, label: `Virtual Account ${env.doku.vaChannel}`, method: 'VIRTUAL_ACCOUNT' },
      { channelCode: env.doku.ewalletChannel, label: `E-Wallet ${env.doku.ewalletChannel}`, method: 'EWALLET' },
    ].filter((c) => c.method === method);
  }
  try {
    const channels = await doku.listChannels();
    return channels.filter((c) => c.method === method);
  } catch (err) {
    console.error('[doku] gagal memuat channel', err);
    return [];
  }
}
