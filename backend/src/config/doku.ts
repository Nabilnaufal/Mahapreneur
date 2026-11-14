import crypto from 'node:crypto';
import { env } from './env.js';

/**
 * Klien DOKU SNAP (Sandbox). Menangani penandatanganan request dan verifikasi
 * signature notifikasi webhook. Tidak ada secret key yang pernah dikirim ke frontend.
 */

export const sha256Base64 = (data: string) => crypto.createHash('sha256').update(data).digest('base64');

export const hmacBase64 = (data: string, key: string) =>
  crypto.createHmac('sha256', key).update(data).digest('base64');

/** String to sign untuk request keluar: Client-Id, Request-Id, Timestamp, Target, Digest. */
function requestStringToSign(o: { clientId: string; requestId: string; timestamp: string; target: string; digest: string }) {
  return [
    `Client-Id:${o.clientId}`,
    `Request-Id:${o.requestId}`,
    `Request-Timestamp:${o.timestamp}`,
    `Request-Target:${o.target}`,
    `Digest:${o.digest}`,
  ].join('\n');
}

/**
 * String to sign untuk webhook masuk. DOKU mengirim Digest di header bila body ada;
 * bila tidak ada body, baris Digest dihilangkan.
 */
export function notificationStringToSign(o: {
  clientId: string;
  requestId: string;
  timestamp: string;
  target: string;
  digest?: string;
}) {
  const lines = [
    `Client-Id:${o.clientId}`,
    `Request-Id:${o.requestId}`,
    `Request-Timestamp:${o.timestamp}`,
    `Request-Target:${o.target}`,
  ];
  if (o.digest) lines.push(`Digest:${o.digest}`);
  return lines.join('\n');
}

/** Bandingkan signature dengan waktu konstan. */
export function signatureMatches(expected: string, received: string) {
  const a = Buffer.from(expected);
  const b = Buffer.from(received ?? '');
  return a.length === b.length && crypto.timingSafeEqual(a, b);
}

export function verifyNotificationSignature(headers: Record<string, any>, rawBody: string, targetPath: string) {
  const clientId = headers['client-id'];
  const requestId = headers['request-id'];
  const timestamp = headers['request-timestamp'];
  const signature = headers['signature'];
  if (!clientId || !requestId || !timestamp || !signature) return false;

  // DOKU mengirim signature dengan prefiks algoritma: "HMACSHA256=<base64>".
  const received = String(signature).replace(/^HMACSHA256=/i, '');

  // Tolak notifikasi yang timestamp-nya jauh dari sekarang (replay attack).
  const sentAt = Date.parse(timestamp);
  if (Number.isNaN(sentAt) || Math.abs(Date.now() - sentAt) > 5 * 60 * 1000) return false;

  const digest = rawBody ? sha256Base64(rawBody) : undefined;
  const expected = hmacBase64(
    notificationStringToSign({ clientId, requestId, timestamp, target: targetPath, digest }),
    env.doku.secretKey,
  );
  return signatureMatches(expected, received);
}

async function dokuRequest(method: 'GET' | 'POST', target: string, body?: unknown) {
  const requestId = crypto.randomUUID();
  const timestamp = new Date().toISOString().replace(/\.\d{3}Z$/, 'Z');
  const raw = body ? JSON.stringify(body) : '';
  const digest = raw ? sha256Base64(raw) : '';
  const signature = hmacBase64(
    requestStringToSign({ clientId: env.doku.clientId, requestId, timestamp, target, digest }),
    env.doku.secretKey,
  );

  const res = await fetch(`${env.doku.baseUrl}${target}`, {
    method,
    headers: {
      'Client-Id': env.doku.clientId,
      'Request-Id': requestId,
      'Request-Timestamp': timestamp,
      Signature: `HMACSHA256=${signature}`,
      'Content-Type': 'application/json',
      ...(digest ? { Digest: digest } : {}),
    },
    body: raw || undefined,
  });

  const text = await res.text();
  let json: any = null;
  try {
    json = text ? JSON.parse(text) : null;
  } catch {
    json = { raw: text };
  }
  if (!res.ok) {
    throw new Error(`DOKU ${method} ${target} gagal (${res.status}): ${text.slice(0, 300)}`);
  }
  return json;
}

export type DokuChannel = {
  channelCode: string;
  label: string;
  method: 'QRIS' | 'VIRTUAL_ACCOUNT' | 'EWALLET';
  qrString?: string;
  vaNumber?: string;
  paymentUrl?: string;
};

export const doku = {
  /** Buat invoice pembayaran. Mengembalikan URL halaman pembayaran DOKU. */
  async createInvoice(input: {
    invoiceNumber: string;
    amount: number;
    customerName: string;
    customerEmail: string;
    description: string;
  }) {
    const body = {
      order: {
        amount: input.amount,
        invoice_number: input.invoiceNumber,
        currency: 'IDR',
        callback_url: `${env.apiUrl}/api/payments/doku/notification`,
        line_items: [{ name: input.description, price: input.amount, quantity: 1 }],
      },
      payment: {
        payment_due_date: env.doku.paymentTtlMinutes,
        payment_method_types: ['QRIS', 'VIRTUAL_ACCOUNT', 'EMONEY'],
      },
      customer: { name: input.customerName, email: input.customerEmail },
    };
    const result: any = await dokuRequest('POST', '/checkout/v1/payment', body);
    return {
      tokenId: result?.response?.payment?.token_id as string | undefined,
      paymentUrl: result?.response?.payment?.url as string | undefined,
      expiredDate: result?.response?.payment?.expired_date as string | undefined,
    };
  },

  /** Daftar channel pembayaran yang aktif pada akun sandbox. */
  async listChannels(): Promise<DokuChannel[]> {
    const result: any = await dokuRequest('GET', '/checkout/v1/payment/methods', undefined);
    const methods: any[] = result?.response?.payment_methods ?? [];
    return methods.map((m) => ({
      channelCode: m.channel_code ?? m.payment_method_type,
      label: m.name ?? m.channel_code,
      method: m.payment_method_type === 'VIRTUAL_ACCOUNT' ? 'VIRTUAL_ACCOUNT' : m.payment_method_type === 'EMONEY' ? 'EWALLET' : 'QRIS',
    }));
  },

  /** Aktifkan satu channel pada invoice, lalu ambil data bayar (QR / nomor VA). */
  async requestChannel(tokenId: string, channelCode: string) {
    const result: any = await dokuRequest('POST', `/checkout/v1/payment/${tokenId}/channels`, {
      channel_code: channelCode,
    });
    const channel = result?.response?.channel ?? {};
    return {
      qrString: channel.qr_code as string | undefined,
      vaNumber: (channel.virtual_account_number ?? channel.pay_code) as string | undefined,
      paymentUrl: (channel.url ?? result?.response?.payment?.url) as string | undefined,
      channelCode,
    };
  },
};
