import type { Request, Response } from 'express';
import { prisma } from '../config/prisma.js';
import { dokuConfigured, env } from '../config/env.js';
import { verifyNotificationSignature } from '../config/doku.js';
import { ApiError, ok } from '../utils/http.js';
import { logActivity } from '../utils/activity.js';
import { markPaymentFailed, markPaymentPaid } from '../services/payment.service.js';

const SUCCESS_STATES = ['SUCCESS', 'SUCCESSFUL', 'PAID', 'SETTLED', 'CAPTURE'];
const FAILED_STATES = ['FAILED', 'FAILURE', 'DENIED', 'CANCELLED'];
const EXPIRED_STATES = ['EXPIRED', 'TIMEOUT'];

export const paymentController = {
  /** Metode pembayaran yang disiapkan platform (dipakai halaman checkout). */
  async methods(_req: Request, res: Response) {
    ok(res, {
      simulated: !dokuConfigured,
      methods: [
        { code: 'QRIS', label: 'QRIS', description: 'Scan sekali pakai untuk semua e-wallet dan m-banking', type: 'online' },
        { code: 'VIRTUAL_ACCOUNT', label: 'Virtual Account', description: `Transfer ke nomor Virtual Account ${env.doku.vaChannel}`, type: 'online' },
        { code: 'EWALLET', label: 'E-Wallet', description: `Bayar melalui ${env.doku.ewalletChannel} dan e-wallet lain`, type: 'online' },
        { code: 'COD', label: 'Bayar di Tempat (COD)', description: 'Bayar langsung ke seller saat pesanan diterima', type: 'cod' },
      ],
    });
  },

  /**
   * Webhook notifikasi pembayaran DOKU.
   * Status pembayaran hanya berubah di sini (atau pada simulasi pengembangan),
   * setelah signature dan nominal diverifikasi.
   */
  async dokuNotification(req: Request, res: Response) {
    const rawBody = (req as Request & { rawBody?: string }).rawBody ?? '';
    const target = '/api/payments/doku/notification';

    if (!dokuConfigured) {
      throw ApiError.unauthorized('DOKU belum dikonfigurasi pada server ini');
    }

    const signatureValid = verifyNotificationSignature(req.headers as Record<string, any>, rawBody, target);
    const body: any = req.body ?? {};
    const order = body.order ?? {};
    const transaction = body.transaction ?? {};

    const invoice = String(order.invoice_number ?? body.invoice_number ?? '');
    const orderNumber = String(body.order_number ?? invoice.split('-').slice(0, 3).join('-'));
    const status = String(transaction.status ?? body.transaction_status ?? '').toUpperCase();
    const amount = Number(order.amount ?? body.amount ?? 0) || null;

    // Signature diperiksa lebih dulu: notifikasi palsu tidak boleh menulis apa pun
    // ke tabel idempotency, agar tidak bisa memblokir notifikasi asli.
    if (!signatureValid) {
      await logActivity({
        userId: null,
        action: 'DOKU_SIGNATURE_INVALID',
        entity: 'Payment',
        entityId: invoice || null,
        description: `Notifikasi DOKU dengan signature tidak valid ditolak untuk ${invoice || 'invoice tidak dikenal'}`,
      });
      return res.status(401).json({ success: false, message: 'Signature tidak valid' });
    }

    // Kunci idempotency: notifikasi yang sama tidak pernah diproses dua kali.
    const notificationKey = [invoice, status, amount ?? '-', transaction.date ?? body.transaction_date ?? '-'].join('|');

    let notificationId: number;
    try {
      const created = await prisma.paymentNotification.create({
        data: {
          notificationKey,
          invoiceNumber: invoice || null,
          orderNumber: orderNumber || null,
          transactionStatus: status || null,
          amount,
          signatureValid,
          rawPayload: body as never,
        },
      });
      notificationId = created.id;
    } catch (err: any) {
      if (err?.code === 'P2002') {
        // Sudah pernah diterima — balas sukses agar DOKU berhenti mencoba ulang.
        return res.json({ success: true, message: 'Notifikasi duplikat diabaikan' });
      }
      throw err;
    }

    let result: Record<string, unknown> = { status: 'IGNORED' };
    if (SUCCESS_STATES.includes(status)) {
      result = await markPaymentPaid({
        invoiceNumber: invoice,
        amount,
        channel: body.channel?.id ?? body.acquirer?.id ?? null,
        source: 'DOKU_WEBHOOK',
        rawPayload: body,
      });
    } else if (EXPIRED_STATES.includes(status)) {
      await markPaymentFailed(invoice, 'EXPIRED');
      result = { status: 'EXPIRED' };
    } else if (FAILED_STATES.includes(status)) {
      await markPaymentFailed(invoice, 'FAILED');
      result = { status: 'FAILED' };
    }

    await prisma.paymentNotification.update({
      where: { id: notificationId },
      data: { processed: true, note: JSON.stringify(result) },
    });

    res.json({ success: true, message: 'Notifikasi diproses', data: result });
  },

  /** Riwayat notifikasi pembayaran — untuk pemantauan admin. */
  async notifications(req: Request, res: Response) {
    const logs = await prisma.paymentNotification.findMany({ orderBy: { createdAt: 'desc' }, take: 50 });
    ok(res, { notifications: logs });
  },
};
