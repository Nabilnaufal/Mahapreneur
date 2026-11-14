import type { Request, Response } from 'express';
import { z } from 'zod';
import type { PaymentMethod, Prisma } from '@prisma/client';
import { prisma } from '../config/prisma.js';
import { ApiError, ok, pageMeta, paginate } from '../utils/http.js';
import { hashPassword, orderNumber, verifyPassword } from '../utils/security.js';
import { serializeOrder, serializeProduct, serializeStore, serializeUser } from '../utils/serialize.js';
import { logActivity, notify } from '../utils/activity.js';
import { availableChannels, markPaymentPaid, prepareOnlinePayment } from '../services/payment.service.js';

const cartInclude = {
  product: {
    include: {
      images: { orderBy: { sortOrder: 'asc' }, take: 1 },
      store: { select: { id: true, name: true, slug: true, status: true } },
      category: { select: { id: true, name: true, slug: true, icon: true } },
    },
  },
} satisfies Prisma.CartItemInclude;

async function cartSummary(userId: number) {
  const items = await prisma.cartItem.findMany({ where: { cart: { userId } }, include: cartInclude, orderBy: { createdAt: 'asc' } });
  const mapped = items.map((item) => ({
    id: item.id,
    quantity: item.quantity,
    subtotal: item.product.price * item.quantity,
    product: serializeProduct(item.product),
  }));
  return {
    items: mapped,
    summary: {
      itemCount: mapped.reduce((n, i) => n + i.quantity, 0),
      storeCount: new Set(mapped.map((i) => i.product.store?.id)).size,
      subtotal: mapped.reduce((n, i) => n + i.subtotal, 0),
    },
  };
}

const checkoutSchema = z.object({
  cartItemIds: z.array(z.number().int().positive()).min(1, 'Pilih minimal satu produk'),
  recipientName: z.string().trim().min(3, 'Nama penerima minimal 3 karakter').max(80),
  recipientPhone: z.string().trim().min(8, 'Nomor telepon tidak valid').max(20),
  address: z.string().trim().min(10, 'Alamat penerimaan minimal 10 karakter'),
  campus: z.string().trim().max(120).optional().or(z.literal('')),
  room: z.string().trim().max(60).optional().or(z.literal('')),
  note: z.string().trim().max(500).optional().or(z.literal('')),
  paymentMethod: z.enum(['QRIS', 'VIRTUAL_ACCOUNT', 'EWALLET', 'COD']),
  saveAddress: z.boolean().optional(),
});

export const customerController = {
  async getCart(req: Request, res: Response) {
    ok(res, await cartSummary(req.user!.id));
  },

  async addToCart(req: Request, res: Response) {
    const { productId, quantity } = z
      .object({ productId: z.number().int().positive(), quantity: z.number().int().min(1).max(99).default(1) })
      .parse(req.body);

    const product = await prisma.product.findUnique({ where: { id: productId }, include: { store: true } });
    if (!product || product.status !== 'ACTIVE' || product.store.status !== 'ACTIVE') {
      throw ApiError.notFound('Produk tidak tersedia');
    }
    if (product.stock < quantity) throw ApiError.badRequest(`Stok ${product.name} tinggal ${product.stock}`);

    const cart = await prisma.cart.upsert({ where: { userId: req.user!.id }, create: { userId: req.user!.id }, update: {} });
    const existing = await prisma.cartItem.findUnique({ where: { cartId_productId: { cartId: cart.id, productId } } });
    const nextQty = (existing?.quantity ?? 0) + quantity;
    if (nextQty > product.stock) throw ApiError.badRequest(`Stok ${product.name} tinggal ${product.stock}`);

    await prisma.cartItem.upsert({
      where: { cartId_productId: { cartId: cart.id, productId } },
      create: { cartId: cart.id, productId, quantity },
      update: { quantity: nextQty },
    });
    ok(res, await cartSummary(req.user!.id), 'Produk ditambahkan ke keranjang');
  },

  async updateCartItem(req: Request, res: Response) {
    const { quantity } = z.object({ quantity: z.number().int().min(1).max(99) }).parse(req.body);
    const item = await prisma.cartItem.findFirst({
      where: { id: Number(req.params.itemId), cart: { userId: req.user!.id } },
      include: { product: true },
    });
    if (!item) throw ApiError.notFound('Item keranjang tidak ditemukan');
    if (item.product.stock < quantity) throw ApiError.badRequest(`Stok ${item.product.name} tinggal ${item.product.stock}`);

    await prisma.cartItem.update({ where: { id: item.id }, data: { quantity } });
    ok(res, await cartSummary(req.user!.id), 'Jumlah diperbarui');
  },

  async removeCartItem(req: Request, res: Response) {
    const item = await prisma.cartItem.findFirst({ where: { id: Number(req.params.itemId), cart: { userId: req.user!.id } } });
    if (!item) throw ApiError.notFound('Item keranjang tidak ditemukan');
    await prisma.cartItem.delete({ where: { id: item.id } });
    ok(res, await cartSummary(req.user!.id), 'Produk dihapus dari keranjang');
  },

  async getWishlist(req: Request, res: Response) {
    const items = await prisma.wishlist.findMany({
      where: { userId: req.user!.id },
      include: {
        product: {
          include: {
            images: { orderBy: { sortOrder: 'asc' }, take: 1 },
            store: { select: { id: true, name: true, slug: true, status: true } },
            category: { select: { id: true, name: true, slug: true, icon: true } },
          },
        },
      },
      orderBy: { createdAt: 'desc' },
    });
    ok(res, { items: items.map((w) => ({ id: w.id, product: serializeProduct(w.product) })) });
  },

  async addWishlist(req: Request, res: Response) {
    const { productId } = z.object({ productId: z.number().int().positive() }).parse(req.body);
    const product = await prisma.product.findUnique({ where: { id: productId } });
    if (!product) throw ApiError.notFound('Produk tidak ditemukan');
    await prisma.wishlist.upsert({
      where: { userId_productId: { userId: req.user!.id, productId } },
      create: { userId: req.user!.id, productId },
      update: {},
    });
    ok(res, { productId }, 'Ditambahkan ke wishlist');
  },

  async removeWishlist(req: Request, res: Response) {
    await prisma.wishlist.deleteMany({ where: { userId: req.user!.id, productId: Number(req.params.productId) } });
    ok(res, { productId: Number(req.params.productId) }, 'Dihapus dari wishlist');
  },

  async listAddresses(req: Request, res: Response) {
    const addresses = await prisma.address.findMany({ where: { userId: req.user!.id }, orderBy: [{ isDefault: 'desc' }, { createdAt: 'desc' }] });
    ok(res, { addresses });
  },

  async createAddress(req: Request, res: Response) {
    const data = addressSchema.parse(req.body);
    if (data.isDefault) await prisma.address.updateMany({ where: { userId: req.user!.id }, data: { isDefault: false } });
    const address = await prisma.address.create({ data: { ...data, userId: req.user!.id } });
    ok(res, { address }, 'Alamat ditambahkan');
  },

  async updateAddress(req: Request, res: Response) {
    const data = addressSchema.partial().parse(req.body);
    const existing = await prisma.address.findFirst({ where: { id: Number(req.params.id), userId: req.user!.id } });
    if (!existing) throw ApiError.notFound('Alamat tidak ditemukan');
    if (data.isDefault) await prisma.address.updateMany({ where: { userId: req.user!.id }, data: { isDefault: false } });
    const address = await prisma.address.update({ where: { id: existing.id }, data });
    ok(res, { address }, 'Alamat diperbarui');
  },

  async deleteAddress(req: Request, res: Response) {
    const existing = await prisma.address.findFirst({ where: { id: Number(req.params.id), userId: req.user!.id } });
    if (!existing) throw ApiError.notFound('Alamat tidak ditemukan');
    await prisma.address.delete({ where: { id: existing.id } });
    ok(res, null, 'Alamat dihapus');
  },

  /**
   * Checkout: satu checkout dapat menghasilkan beberapa pesanan (dipisah per seller).
   * Harga, stok, dan total dihitung ulang di backend di dalam satu transaksi.
   */
  async checkout(req: Request, res: Response) {
    const data = checkoutSchema.parse(req.body);
    const userId = req.user!.id;

    const cartItems = await prisma.cartItem.findMany({
      where: { id: { in: data.cartItemIds }, cart: { userId } },
      include: { product: { include: { store: true } } },
    });
    if (cartItems.length !== data.cartItemIds.length) throw ApiError.badRequest('Sebagian item keranjang tidak valid');

    const groups = new Map<number, typeof cartItems>();
    for (const item of cartItems) {
      if (item.product.status !== 'ACTIVE' || item.product.store.status !== 'ACTIVE') {
        throw ApiError.badRequest(`${item.product.name} sudah tidak dijual lagi`);
      }
      if (item.product.stock < item.quantity) throw ApiError.badRequest(`Stok ${item.product.name} tinggal ${item.product.stock}`);
      groups.set(item.product.storeId, [...(groups.get(item.product.storeId) ?? []), item]);
    }

    const isCod = data.paymentMethod === 'COD';
    const createdOrders = await prisma.$transaction(async (tx) => {
      const results: { id: number; orderNumber: string }[] = [];

      for (const [storeId, items] of groups) {
        const subtotal = items.reduce((n, i) => n + i.product.price * i.quantity, 0);

        const order = await tx.order.create({
          data: {
            orderNumber: orderNumber(),
            customerId: userId,
            storeId,
            status: isCod ? 'CONFIRMED' : 'WAITING_PAYMENT',
            paymentStatus: isCod ? 'UNPAID_COD' : 'PENDING',
            paymentMethod: data.paymentMethod as PaymentMethod,
            subtotal,
            total: subtotal,
            recipientName: data.recipientName,
            recipientPhone: data.recipientPhone,
            address: data.address,
            campus: data.campus || null,
            room: data.room || null,
            note: data.note || null,
            confirmedAt: isCod ? new Date() : null,
            items: {
              create: items.map((i) => ({
                productId: i.productId,
                productName: i.product.name,
                productPrice: i.product.price,
                quantity: i.quantity,
                subtotal: i.product.price * i.quantity,
              })),
            },
            histories: {
              create: {
                status: isCod ? 'CONFIRMED' : 'WAITING_PAYMENT',
                note: isCod ? 'Pesanan COD dibuat, menunggu diproses seller' : 'Menunggu pembayaran online',
                createdBy: userId,
              },
            },
          },
        });

        // Kunci stok secara aman: update hanya berhasil bila stok masih cukup.
        for (const item of items) {
          const updated = await tx.product.updateMany({
            where: { id: item.productId, stock: { gte: item.quantity }, status: 'ACTIVE' },
            data: { stock: { decrement: item.quantity }, sold: { increment: item.quantity } },
          });
          if (updated.count !== 1) throw ApiError.conflict(`Stok ${item.product.name} sudah habis. Silakan perbarui keranjang.`);
        }

        await tx.cartItem.deleteMany({ where: { id: { in: items.map((i) => i.id) } } });
        results.push({ id: order.id, orderNumber: order.orderNumber });
      }

      if (data.saveAddress) {
        await tx.address.create({
          data: {
            userId,
            label: data.campus ? `Kampus ${data.campus}` : 'Alamat utama',
            recipientName: data.recipientName,
            phone: data.recipientPhone,
            address: data.address,
            campus: data.campus || null,
            room: data.room || null,
          },
        });
      }

      return results;
    });

    // Setelah transaksi selesai: siapkan invoice DOKU / notifikasi seller.
    for (const created of createdOrders) {
      if (isCod) {
        const order = await prisma.order.findUnique({ where: { id: created.id }, include: { store: true } });
        await prisma.payment.create({
          data: { orderId: created.id, method: 'COD', status: 'UNPAID_COD', amount: order!.total },
        });
        await notify({
          userId: order!.store.userId,
          title: 'Pesanan COD baru',
          message: `Pesanan ${order!.orderNumber} masuk dengan metode COD. Segera proses dan antarkan sendiri ke penerima.`,
          type: 'info',
          link: `/seller/orders/${order!.id}`,
        });
      } else {
        await prepareOnlinePayment(created.id);
      }
    }

    const orders = await prisma.order.findMany({
      where: { id: { in: createdOrders.map((o) => o.id) } },
      include: { items: { include: { product: { include: { images: { take: 1 } } } } }, store: true, payment: true },
    });

    await logActivity({
      userId,
      action: 'CHECKOUT',
      entity: 'Order',
      entityId: createdOrders.map((o) => o.id).join(','),
      description: `Checkout ${createdOrders.length} pesanan dengan metode ${data.paymentMethod}`,
      meta: { orders: createdOrders.map((o) => o.orderNumber) },
    });

    ok(res, { orders: orders.map(serializeOrder), ordersCount: orders.length }, 'Pesanan berhasil dibuat');
  },

  async listOrders(req: Request, res: Response) {
    const { page, limit, skip, take } = paginate(req.query as Record<string, unknown>);
    const where: Prisma.OrderWhereInput = { customerId: req.user!.id };
    if (req.query.status) where.status = String(req.query.status) as never;

    const [total, orders] = await Promise.all([
      prisma.order.count({ where }),
      prisma.order.findMany({
        where,
        include: {
          store: { select: { id: true, name: true, slug: true, logo: true } },
          items: { include: { product: { include: { images: { take: 1 } } } } },
          payment: true,
        },
        orderBy: { createdAt: 'desc' },
        skip,
        take,
      }),
    ]);
    ok(res, { orders: orders.map(serializeOrder), meta: pageMeta(total, page, limit) });
  },

  async getOrder(req: Request, res: Response) {
    const order = await prisma.order.findFirst({
      where: { id: Number(req.params.id), customerId: req.user!.id },
      include: {
        store: true,
        items: { include: { product: { include: { images: { take: 1 } } } } },
        payment: true,
        histories: { orderBy: { createdAt: 'asc' } },
      },
    });
    if (!order) throw ApiError.notFound('Pesanan tidak ditemukan');
    ok(res, { order: serializeOrder(order), store: serializeStore(order.store) });
  },

  async cancelOrder(req: Request, res: Response) {
    const { reason } = z.object({ reason: z.string().trim().max(300).optional() }).parse(req.body ?? {});
    const order = await prisma.order.findFirst({
      where: { id: Number(req.params.id), customerId: req.user!.id },
      include: { items: true, store: true, payment: true },
    });
    if (!order) throw ApiError.notFound('Pesanan tidak ditemukan');
    if (!['WAITING_PAYMENT', 'CONFIRMED'].includes(order.status)) {
      throw ApiError.badRequest('Pesanan yang sudah diproses tidak dapat dibatalkan. Hubungi seller.');
    }
    if (order.paymentStatus === 'PAID') {
      throw ApiError.badRequest('Pesanan sudah dibayar. Ajukan pembatalan ke seller untuk proses refund.');
    }

    await prisma.$transaction([
      prisma.order.update({
        where: { id: order.id },
        data: {
          status: 'CANCELLED',
          cancelledAt: new Date(),
          paymentStatus: order.paymentStatus === 'PENDING' ? 'FAILED' : order.paymentStatus,
        },
      }),
      ...order.items
        .filter((i) => i.productId)
        .map((i) =>
          prisma.product.update({
            where: { id: i.productId! },
            data: { stock: { increment: i.quantity }, sold: { decrement: i.quantity } },
          }),
        ),
      prisma.orderStatusHistory.create({
        data: { orderId: order.id, status: 'CANCELLED', note: reason ?? 'Dibatalkan oleh customer', createdBy: req.user!.id },
      }),
      ...(order.payment && order.payment.status === 'PENDING'
        ? [prisma.payment.update({ where: { id: order.payment.id }, data: { status: 'FAILED' } })]
        : []),
    ]);

    await notify({
      userId: order.store.userId,
      title: 'Pesanan dibatalkan customer',
      message: `Pesanan ${order.orderNumber} dibatalkan oleh customer.${reason ? ` Alasan: ${reason}` : ''}`,
      type: 'warning',
      link: `/seller/orders/${order.id}`,
    });
    await logActivity({
      userId: req.user!.id,
      action: 'ORDER_CANCELLED',
      entity: 'Order',
      entityId: order.id,
      description: `Customer membatalkan pesanan ${order.orderNumber}`,
    });
    ok(res, { orderId: order.id }, 'Pesanan dibatalkan');
  },

  /** Instruksi pembayaran: QRIS / nomor VA / halaman DOKU. */
  async getPayment(req: Request, res: Response) {
    const order = await prisma.order.findFirst({
      where: { id: Number(req.params.id), customerId: req.user!.id },
      include: { payment: true },
    });
    if (!order) throw ApiError.notFound('Pesanan tidak ditemukan');
    if (order.paymentMethod === 'COD') throw ApiError.badRequest('Pesanan ini memakai metode COD');

    if (!order.payment) {
      const prepared = await prepareOnlinePayment(order.id);
      if (prepared) order.payment = prepared.payment;
    }
    // Tandai kedaluwarsa bila melewati batas waktu.
    if (order.payment?.status === 'PENDING' && order.payment.expiresAt && order.payment.expiresAt < new Date()) {
      await prisma.payment.update({ where: { id: order.payment.id }, data: { status: 'EXPIRED' } });
      await prisma.order.update({ where: { id: order.id }, data: { paymentStatus: 'EXPIRED' } });
      order.payment.status = 'EXPIRED';
    }

    const channels = await availableChannels(order.paymentMethod);
    ok(res, {
      order: { id: order.id, orderNumber: order.orderNumber, total: order.total, status: order.status, paymentStatus: order.paymentStatus, paymentMethod: order.paymentMethod },
      payment: order.payment,
      channels,
      simulated: !order.payment?.dokuPaymentUrl?.startsWith('http') || order.payment?.dokuPaymentUrl?.includes('/payment/'),
    });
  },

  /** Pilih channel pembayaran tertentu lalu ambil data bayarnya (QR / nomor VA). */
  async choosePaymentChannel(req: Request, res: Response) {
    const { channelCode } = z.object({ channelCode: z.string().min(2) }).parse(req.body);
    const order = await prisma.order.findFirst({
      where: { id: Number(req.params.id), customerId: req.user!.id },
      include: { payment: true },
    });
    if (!order?.payment) throw ApiError.notFound('Data pembayaran belum tersedia');
    if (order.payment.status === 'PAID') throw ApiError.badRequest('Pesanan ini sudah dibayar');
    if (order.payment.status === 'EXPIRED') throw ApiError.badRequest('Pembayaran sudah kedaluwarsa. Buat pembayaran baru.');

    const { doku } = await import('../config/doku.js');
    const { dokuConfigured, env } = await import('../config/env.js');

    if (!dokuConfigured || !order.payment.dokuTokenId) {
      // Mode simulasi: tampilkan data bayar buatan agar alur UI tetap utuh.
      const payment = await prisma.payment.update({
        where: { id: order.payment.id },
        data: {
          dokuChannel: channelCode,
          dokuQrString: order.paymentMethod === 'QRIS' ? `SIMULASI-QRIS-${order.orderNumber}` : null,
          dokuVaNumber: order.paymentMethod === 'VIRTUAL_ACCOUNT' ? `88${String(order.id).padStart(10, '0')}` : null,
        },
      });
      return ok(res, { payment, simulated: true }, 'Mode simulasi pembayaran aktif (kredensial DOKU belum diisi)');
    }

    const channel = await doku.requestChannel(order.payment.dokuTokenId, channelCode);
    const payment = await prisma.payment.update({
      where: { id: order.payment.id },
      data: { dokuChannel: channelCode, dokuQrString: channel.qrString ?? null, dokuVaNumber: channel.vaNumber ?? null, dokuPaymentUrl: channel.paymentUrl ?? order.payment.dokuPaymentUrl },
    });
    ok(res, { payment, simulated: false }, 'Instruksi pembayaran siap');
  },

  /** Buat ulang invoice bila pembayaran sebelumnya kedaluwarsa/gagal. */
  async retryPayment(req: Request, res: Response) {
    const order = await prisma.order.findFirst({ where: { id: Number(req.params.id), customerId: req.user!.id }, include: { payment: true } });
    if (!order) throw ApiError.notFound('Pesanan tidak ditemukan');
    if (order.paymentStatus === 'PAID') throw ApiError.badRequest('Pesanan ini sudah dibayar');
    if (order.paymentMethod === 'COD') throw ApiError.badRequest('Pesanan COD tidak memerlukan pembayaran online');

    if (order.payment) await prisma.payment.update({ where: { id: order.payment.id }, data: { status: 'PENDING' } });
    const prepared = await prepareOnlinePayment(order.id);
    ok(res, { payment: prepared?.payment ?? null }, 'Instruksi pembayaran baru dibuat');
  },

  /** Simulasi pembayaran — hanya aktif bila kredensial DOKU belum dikonfigurasi. */
  async simulatePayment(req: Request, res: Response) {
    const { dokuConfigured } = await import('../config/env.js');
    if (dokuConfigured) throw ApiError.forbidden('Simulasi pembayaran tidak tersedia saat DOKU aktif');

    const order = await prisma.order.findFirst({ where: { id: Number(req.params.id), customerId: req.user!.id }, include: { payment: true } });
    if (!order?.payment) throw ApiError.notFound('Data pembayaran tidak ditemukan');

    const result = await markPaymentPaid({
      invoiceNumber: order.payment.invoiceNumber!,
      amount: order.payment.amount,
      channel: order.payment.dokuChannel,
      source: 'SIMULATION',
    });
    ok(res, result, 'Pembayaran simulasi diproses');
  },

  async getProfile(req: Request, res: Response) {
    const user = await prisma.user.findUnique({ where: { id: req.user!.id } });
    ok(res, { user: serializeUser(user) });
  },

  async updateProfile(req: Request, res: Response) {
    const data = z
      .object({
        name: z.string().trim().min(3).max(80).optional(),
        phone: z.string().trim().max(20).optional().or(z.literal('')),
        avatar: z.string().trim().max(300).optional().or(z.literal('')),
      })
      .parse(req.body);

    const user = await prisma.user.update({
      where: { id: req.user!.id },
      data: { ...data, phone: data.phone || null, avatar: data.avatar || null },
    });
    ok(res, { user: serializeUser(user) }, 'Profil diperbarui');
  },

  async changePassword(req: Request, res: Response) {
    const data = z
      .object({
        currentPassword: z.string().min(1, 'Password saat ini wajib diisi'),
        newPassword: z.string().min(8, 'Password minimal 8 karakter'),
        confirmPassword: z.string(),
      })
      .refine((d) => d.newPassword === d.confirmPassword, { message: 'Konfirmasi password tidak sama', path: ['confirmPassword'] })
      .parse(req.body);

    const user = await prisma.user.findUnique({ where: { id: req.user!.id } });
    if (!user || !(await verifyPassword(data.currentPassword, user.password))) {
      throw ApiError.badRequest('Password saat ini salah');
    }
    await prisma.user.update({ where: { id: user.id }, data: { password: await hashPassword(data.newPassword) } });
    await logActivity({ userId: user.id, action: 'CHANGE_PASSWORD', entity: 'User', entityId: user.id, description: 'Password diubah dari halaman profil' });
    ok(res, null, 'Password berhasil diubah');
  },

  async notifications(req: Request, res: Response) {
    const { page, limit, skip, take } = paginate(req.query as Record<string, unknown>);
    const [items, unread, total] = await Promise.all([
      prisma.notification.findMany({ where: { userId: req.user!.id }, orderBy: { createdAt: 'desc' }, skip, take }),
      prisma.notification.count({ where: { userId: req.user!.id, isRead: false } }),
      prisma.notification.count({ where: { userId: req.user!.id } }),
    ]);
    ok(res, { notifications: items, unread, meta: pageMeta(total, page, limit) });
  },

  async readNotification(req: Request, res: Response) {
    await prisma.notification.updateMany({ where: { id: Number(req.params.id), userId: req.user!.id }, data: { isRead: true } });
    ok(res, null, 'Notifikasi dibaca');
  },

  async readAllNotifications(req: Request, res: Response) {
    await prisma.notification.updateMany({ where: { userId: req.user!.id, isRead: false }, data: { isRead: true } });
    ok(res, null, 'Semua notifikasi ditandai sudah dibaca');
  },
};

const addressSchema = z.object({
  label: z.string().trim().min(2, 'Label minimal 2 karakter').max(40),
  recipientName: z.string().trim().min(3).max(80),
  phone: z.string().trim().min(8, 'Nomor telepon tidak valid').max(20),
  address: z.string().trim().min(10, 'Alamat minimal 10 karakter'),
  campus: z.string().trim().max(120).optional().or(z.literal('')),
  room: z.string().trim().max(60).optional().or(z.literal('')),
  isDefault: z.boolean().optional(),
});
