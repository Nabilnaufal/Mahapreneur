import type { Request, Response } from 'express';
import { z } from 'zod';
import type { OrderStatus, Prisma } from '@prisma/client';
import { prisma } from '../config/prisma.js';
import { ApiError, ok, pageMeta, paginate } from '../utils/http.js';
import { slugify } from '../utils/security.js';
import { serializeOrder, serializeProduct, serializeStore } from '../utils/serialize.js';
import { logActivity, notify } from '../utils/activity.js';

const productInclude = {
  images: { orderBy: { sortOrder: 'asc' } },
  category: { select: { id: true, name: true, slug: true, icon: true } },
  store: { select: { id: true, name: true, slug: true, logo: true } },
} satisfies Prisma.ProductInclude;

/** Urutan status yang boleh ditempuh seller pada satu langkah. */
const NEXT_STATUS: Record<string, OrderStatus[]> = {
  CONFIRMED: ['PROCESSING', 'CANCELLED'],
  PROCESSING: ['READY', 'CANCELLED'],
  READY: ['ON_DELIVERY'],
  ON_DELIVERY: ['COMPLETED'],
};

const STATUS_LABEL: Record<string, string> = {
  PROCESSING: 'sedang diproses',
  READY: 'siap diserahkan',
  ON_DELIVERY: 'sedang diantarkan',
  COMPLETED: 'selesai',
  CANCELLED: 'dibatalkan',
};

const storeId = (req: Request) => req.user!.storeId!;

export const sellerController = {
  async dashboard(req: Request, res: Response) {
    const id = storeId(req);
    const [productCount, orders, revenue] = await Promise.all([
      prisma.product.count({ where: { storeId: id } }),
      prisma.order.findMany({
        where: { storeId: id },
        select: { id: true, orderNumber: true, status: true, paymentStatus: true, total: true, createdAt: true, recipientName: true },
        orderBy: { createdAt: 'desc' },
      }),
      prisma.order.aggregate({ where: { storeId: id, paymentStatus: 'PAID' }, _sum: { total: true } }),
    ]);

    const count = (fn: (o: (typeof orders)[number]) => boolean) => orders.filter(fn).length;
    const recent = orders.slice(0, 5);

    // Grafik penjualan 7 hari terakhir berdasarkan pesanan yang sudah dibayar.
    const since = new Date(Date.now() - 6 * 86400000);
    const paid = await prisma.order.findMany({
      where: { storeId: id, paymentStatus: 'PAID', createdAt: { gte: since } },
      select: { total: true, createdAt: true },
    });
    const chart = Array.from({ length: 7 }, (_, i) => {
      const day = new Date(Date.now() - (6 - i) * 86400000);
      const key = day.toISOString().slice(0, 10);
      return {
        date: key,
        label: day.toLocaleDateString('id-ID', { weekday: 'short' }),
        revenue: paid.filter((o) => o.createdAt.toISOString().slice(0, 10) === key).reduce((n, o) => n + o.total, 0),
      };
    });

    const topProducts = await prisma.product.findMany({
      where: { storeId: id },
      orderBy: { sold: 'desc' },
      take: 5,
      include: productInclude,
    });

    ok(res, {
      stats: {
        productCount,
        totalOrders: orders.length,
        waitingPayment: count((o) => o.status === 'WAITING_PAYMENT'),
        needProcessing: count((o) => ['CONFIRMED', 'PROCESSING', 'READY'].includes(o.status)),
        completed: count((o) => o.status === 'COMPLETED'),
        revenue: revenue._sum.total ?? 0,
      },
      chart,
      recentOrders: recent,
      topProducts: topProducts.map(serializeProduct),
    });
  },

  async getStore(req: Request, res: Response) {
    const store = await prisma.store.findUnique({ where: { id: storeId(req) }, include: { _count: { select: { products: true } } } });
    ok(res, { store: store ? serializeStore(store) : null });
  },

  async createStore(req: Request, res: Response) {
    if (req.user!.storeId) throw ApiError.conflict('Kamu sudah memiliki toko. Satu seller hanya boleh punya satu toko.');
    const data = storeSchema.parse(req.body);

    let slug = data.slug ? slugify(data.slug) : slugify(data.name);
    if (await prisma.store.findUnique({ where: { slug } })) {
      if (data.slug) throw ApiError.conflict('Username toko sudah dipakai');
      slug = `${slug}-${Date.now().toString(36).slice(-4)}`;
    }

    const store = await prisma.store.create({ data: { ...data, slug, userId: req.user!.id } });
    await logActivity({ userId: req.user!.id, action: 'STORE_CREATED', entity: 'Store', entityId: store.id, description: `Toko ${store.name} dibuat` });
    ok(res, { store: serializeStore(store) }, 'Toko berhasil dibuat');
  },

  async updateStore(req: Request, res: Response) {
    const data = storeSchema.partial().parse(req.body);
    const store = await prisma.store.update({
      where: { id: storeId(req) },
      data: { ...data, ...(data.slug ? { slug: slugify(data.slug) } : {}) },
    });
    ok(res, { store: serializeStore(store) }, 'Toko diperbarui');
  },

  async uploadStoreImage(req: Request, res: Response) {
    const file = req.file;
    if (!file) throw ApiError.badRequest('Pilih file gambar terlebih dahulu');
    const kind = req.params.kind === 'banner' ? 'banner' : 'logo';
    const path = `uploads/${req.uploadFolder}/${file.filename}`;
    const store = await prisma.store.update({ where: { id: storeId(req) }, data: { [kind]: path } });
    ok(res, { store: serializeStore(store) }, `${kind === 'logo' ? 'Logo' : 'Banner'} toko diperbarui`);
  },

  async listProducts(req: Request, res: Response) {
    const { page, limit, skip, take } = paginate(req.query as Record<string, unknown>);
    const where: Prisma.ProductWhereInput = { storeId: storeId(req) };
    if (req.query.q) where.name = { contains: String(req.query.q) };
    if (req.query.status) where.status = String(req.query.status) as never;
    if (req.query.type) where.type = String(req.query.type) as never;

    const [total, products] = await Promise.all([
      prisma.product.count({ where }),
      prisma.product.findMany({ where, include: productInclude, orderBy: { createdAt: 'desc' }, skip, take }),
    ]);
    ok(res, { products: products.map(serializeProduct), meta: pageMeta(total, page, limit) });
  },

  async getProduct(req: Request, res: Response) {
    const product = await prisma.product.findFirst({ where: { id: Number(req.params.id), storeId: storeId(req) }, include: productInclude });
    if (!product) throw ApiError.notFound('Produk tidak ditemukan');
    ok(res, { product: serializeProduct(product) });
  },

  async createProduct(req: Request, res: Response) {
    const data = productSchema.parse(req.body);
    const category = await prisma.category.findUnique({ where: { id: data.categoryId } });
    if (!category || !category.isActive) throw ApiError.badRequest('Kategori tidak valid');

    let slug = slugify(data.name);
    if (await prisma.product.findUnique({ where: { slug } })) slug = `${slug}-${Date.now().toString(36).slice(-4)}`;

    const product = await prisma.product.create({
      data: { ...data, slug, storeId: storeId(req), serviceTerms: data.serviceTerms || null, serviceDuration: data.serviceDuration || null },
      include: productInclude,
    });
    await logActivity({ userId: req.user!.id, action: 'PRODUCT_CREATED', entity: 'Product', entityId: product.id, description: `Produk ${product.name} ditambahkan` });
    ok(res, { product: serializeProduct(product) }, 'Produk berhasil ditambahkan');
  },

  async updateProduct(req: Request, res: Response) {
    const existing = await prisma.product.findFirst({ where: { id: Number(req.params.id), storeId: storeId(req) } });
    if (!existing) throw ApiError.notFound('Produk tidak ditemukan');
    const data = productSchema.partial().parse(req.body);

    const product = await prisma.product.update({
      where: { id: existing.id },
      data: { ...data, serviceTerms: data.serviceTerms ?? existing.serviceTerms, serviceDuration: data.serviceDuration ?? existing.serviceDuration },
      include: productInclude,
    });
    ok(res, { product: serializeProduct(product) }, 'Produk diperbarui');
  },

  async deleteProduct(req: Request, res: Response) {
    const existing = await prisma.product.findFirst({ where: { id: Number(req.params.id), storeId: storeId(req) } });
    if (!existing) throw ApiError.notFound('Produk tidak ditemukan');
    await prisma.product.delete({ where: { id: existing.id } });
    await logActivity({ userId: req.user!.id, action: 'PRODUCT_DELETED', entity: 'Product', entityId: existing.id, description: `Produk ${existing.name} dihapus` });
    ok(res, null, 'Produk dihapus');
  },

  async addProductImages(req: Request, res: Response) {
    const product = await prisma.product.findFirst({ where: { id: Number(req.params.id), storeId: storeId(req) }, include: { images: true } });
    if (!product) throw ApiError.notFound('Produk tidak ditemukan');
    const files = (req.files as Express.Multer.File[] | undefined) ?? [];
    if (!files.length) throw ApiError.badRequest('Pilih minimal satu gambar');
    if (product.images.length + files.length > 5) throw ApiError.badRequest('Maksimal 5 gambar per produk');

    await prisma.productImage.createMany({
      data: files.map((file, index) => ({
        productId: product.id,
        url: `uploads/${req.uploadFolder}/${file.filename}`,
        sortOrder: product.images.length + index,
      })),
    });
    const updated = await prisma.product.findUnique({ where: { id: product.id }, include: productInclude });
    ok(res, { product: serializeProduct(updated!) }, 'Gambar produk diunggah');
  },

  async deleteProductImage(req: Request, res: Response) {
    const product = await prisma.product.findFirst({ where: { id: Number(req.params.id), storeId: storeId(req) } });
    if (!product) throw ApiError.notFound('Produk tidak ditemukan');
    await prisma.productImage.deleteMany({ where: { id: Number(req.params.imageId), productId: product.id } });
    const updated = await prisma.product.findUnique({ where: { id: product.id }, include: productInclude });
    ok(res, { product: serializeProduct(updated!) }, 'Gambar dihapus');
  },

  async listOrders(req: Request, res: Response) {
    const { page, limit, skip, take } = paginate(req.query as Record<string, unknown>);
    const where: Prisma.OrderWhereInput = { storeId: storeId(req) };
    if (req.query.status) where.status = String(req.query.status) as never;
    if (req.query.q) where.OR = [{ orderNumber: { contains: String(req.query.q) } }, { recipientName: { contains: String(req.query.q) } }];

    const [total, orders] = await Promise.all([
      prisma.order.count({ where }),
      prisma.order.findMany({
        where,
        include: { customer: { select: { name: true, email: true, phone: true } }, items: { include: { product: { include: { images: { take: 1 } } } } }, payment: true },
        orderBy: { createdAt: 'desc' },
        skip,
        take,
      }),
    ]);
    ok(res, { orders: orders.map(serializeOrder), meta: pageMeta(total, page, limit) });
  },

  async getOrder(req: Request, res: Response) {
    const order = await prisma.order.findFirst({
      where: { id: Number(req.params.id), storeId: storeId(req) },
      include: {
        customer: { select: { name: true, email: true, phone: true } },
        items: { include: { product: { include: { images: { take: 1 } } } } },
        payment: true,
        histories: { orderBy: { createdAt: 'asc' } },
      },
    });
    if (!order) throw ApiError.notFound('Pesanan tidak ditemukan');
    ok(res, { order: serializeOrder(order) });
  },

  /** Seller memperbarui status pesanan sesuai alur pengantaran mandiri. */
  async updateOrderStatus(req: Request, res: Response) {
    const { status, note } = z
      .object({ status: z.enum(['PROCESSING', 'READY', 'ON_DELIVERY', 'COMPLETED', 'CANCELLED']), note: z.string().trim().max(300).optional() })
      .parse(req.body);

    const order = await prisma.order.findFirst({ where: { id: Number(req.params.id), storeId: storeId(req) }, include: { items: true } });
    if (!order) throw ApiError.notFound('Pesanan tidak ditemukan');

    const allowed = NEXT_STATUS[order.status] ?? [];
    if (!allowed.includes(status)) {
      throw ApiError.badRequest(`Status tidak bisa diubah dari ${order.status} ke ${status}`);
    }
    if (order.paymentStatus !== 'PAID' && order.paymentStatus !== 'UNPAID_COD') {
      throw ApiError.badRequest('Pesanan belum dibayar');
    }

    const now = new Date();
    await prisma.$transaction(async (tx) => {
      await tx.order.update({
        where: { id: order.id },
        data: {
          status,
          ...(status === 'COMPLETED' ? { completedAt: now } : {}),
          ...(status === 'CANCELLED' ? { cancelledAt: now } : {}),
        },
      });
      await tx.orderStatusHistory.create({ data: { orderId: order.id, status, note: note ?? null, createdBy: req.user!.id } });
      // Batal setelah stok terpotong → kembalikan stok.
      if (status === 'CANCELLED') {
        for (const item of order.items.filter((i) => i.productId)) {
          await tx.product.update({ where: { id: item.productId! }, data: { stock: { increment: item.quantity }, sold: { decrement: item.quantity } } });
        }
      }
    });

    await notify({
      userId: order.customerId,
      title: `Pesanan ${STATUS_LABEL[status] ?? status}`,
      message: `Pesanan ${order.orderNumber} ${STATUS_LABEL[status] ?? 'diperbarui'}.${note ? ` Catatan seller: ${note}` : ''}`,
      type: status === 'CANCELLED' ? 'warning' : 'info',
      link: `/customer/orders/${order.id}`,
    });
    await logActivity({
      userId: req.user!.id,
      action: 'ORDER_STATUS_UPDATED',
      entity: 'Order',
      entityId: order.id,
      description: `Status ${order.orderNumber} diubah ke ${status}`,
    });
    ok(res, { orderId: order.id, status }, `Status pesanan diperbarui menjadi ${status}`);
  },

  /** Seller menandai pembayaran COD sudah diterima langsung dari customer. */
  async markCodPaid(req: Request, res: Response) {
    const order = await prisma.order.findFirst({ where: { id: Number(req.params.id), storeId: storeId(req) }, include: { payment: true } });
    if (!order) throw ApiError.notFound('Pesanan tidak ditemukan');
    if (order.paymentMethod !== 'COD') throw ApiError.badRequest('Pesanan ini bukan pesanan COD');
    if (order.paymentStatus === 'PAID') throw ApiError.badRequest('Pembayaran COD sudah ditandai lunas');

    await prisma.$transaction([
      prisma.order.update({ where: { id: order.id }, data: { paymentStatus: 'PAID' } }),
      ...(order.payment ? [prisma.payment.update({ where: { id: order.payment.id }, data: { status: 'PAID', paidAt: new Date() } })] : []),
    ]);

    await notify({
      userId: order.customerId,
      title: 'Pembayaran COD diterima',
      message: `Seller sudah menerima pembayaran untuk pesanan ${order.orderNumber}. Terima kasih!`,
      type: 'success',
      link: `/customer/orders/${order.id}`,
    });
    await logActivity({
      userId: req.user!.id,
      action: 'COD_PAID',
      entity: 'Order',
      entityId: order.id,
      description: `Pembayaran COD ${order.orderNumber} ditandai lunas`,
    });
    ok(res, { orderId: order.id }, 'Pembayaran COD ditandai lunas');
  },
};

const storeSchema = z.object({
  name: z.string().trim().min(3, 'Nama toko minimal 3 karakter').max(60),
  slug: z.string().trim().max(60).optional(),
  description: z.string().trim().max(1000).optional().or(z.literal('')),
  phone: z.string().trim().max(20).optional().or(z.literal('')),
  email: z.string().trim().email('Email toko tidak valid').optional().or(z.literal('')),
  address: z.string().trim().max(300).optional().or(z.literal('')),
  campus: z.string().trim().max(120).optional().or(z.literal('')),
  openTime: z.string().trim().max(10).optional().or(z.literal('')),
  closeTime: z.string().trim().max(10).optional().or(z.literal('')),
  status: z.enum(['ACTIVE', 'INACTIVE']).optional(),
});

const productSchema = z.object({
  name: z.string().trim().min(3, 'Nama produk minimal 3 karakter').max(120),
  description: z.string().trim().min(10, 'Deskripsi minimal 10 karakter'),
  price: z.coerce.number().int().min(0, 'Harga tidak boleh negatif').max(2_000_000_000),
  stock: z.coerce.number().int().min(0).max(1_000_000),
  categoryId: z.coerce.number().int().positive(),
  type: z.enum(['GOODS', 'SERVICE']).default('GOODS'),
  status: z.enum(['ACTIVE', 'INACTIVE']).default('ACTIVE'),
  serviceTerms: z.string().trim().max(1000).optional(),
  serviceDuration: z.string().trim().max(80).optional(),
});
