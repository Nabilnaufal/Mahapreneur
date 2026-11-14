import type { Request, Response } from 'express';
import { z } from 'zod';
import type { Prisma } from '@prisma/client';
import { prisma } from '../config/prisma.js';
import { ApiError, ok, pageMeta, paginate } from '../utils/http.js';
import { slugify } from '../utils/security.js';
import { PUBLIC_USER_SELECT, serializeBanner, serializeOrder, serializeProduct, serializeStore } from '../utils/serialize.js';
import { logActivity } from '../utils/activity.js';

export const adminController = {
  async dashboard(_req: Request, res: Response) {
    const [
      totalUsers, totalSellers, totalCustomers, totalStores, totalProducts, totalOrders,
      paidOrders, revenue, recentOrders, pendingStores, topStores,
    ] = await Promise.all([
      prisma.user.count(),
      prisma.user.count({ where: { role: 'SELLER' } }),
      prisma.user.count({ where: { role: 'CUSTOMER' } }),
      prisma.store.count(),
      prisma.product.count(),
      prisma.order.count(),
      prisma.order.count({ where: { paymentStatus: 'PAID' } }),
      prisma.order.aggregate({ where: { paymentStatus: 'PAID' }, _sum: { total: true } }),
      prisma.order.findMany({
        include: { store: { select: { name: true, slug: true } }, customer: { select: { name: true } } },
        orderBy: { createdAt: 'desc' },
        take: 8,
      }),
      prisma.order.count({ where: { status: 'WAITING_PAYMENT' } }),
      prisma.store.findMany({
        select: { id: true, name: true, slug: true, logo: true, _count: { select: { products: true, orders: true } } },
        orderBy: { orders: { _count: 'desc' } },
        take: 5,
      }),
    ]);

    // Aktivitas 7 hari terakhir untuk grafik statistik marketplace.
    const since = new Date(Date.now() - 6 * 86400000);
    const recent = await prisma.order.findMany({ where: { createdAt: { gte: since } }, select: { total: true, createdAt: true, paymentStatus: true } });
    const chart = Array.from({ length: 7 }, (_, i) => {
      const day = new Date(Date.now() - (6 - i) * 86400000);
      const key = day.toISOString().slice(0, 10);
      const dayOrders = recent.filter((o) => o.createdAt.toISOString().slice(0, 10) === key);
      return {
        date: key,
        label: day.toLocaleDateString('id-ID', { weekday: 'short' }),
        orders: dayOrders.length,
        revenue: dayOrders.filter((o) => o.paymentStatus === 'PAID').reduce((n, o) => n + o.total, 0),
      };
    });

    const categoryBreakdown = await prisma.category.findMany({
      select: { name: true, _count: { select: { products: true } } },
      orderBy: { sortOrder: 'asc' },
    });

    ok(res, {
      stats: {
        totalUsers, totalSellers, totalCustomers, totalStores, totalProducts, totalOrders,
        paidOrders, pendingPayment: pendingStores, revenue: revenue._sum.total ?? 0,
      },
      chart,
      categoryBreakdown: categoryBreakdown.map((c) => ({ name: c.name, products: c._count.products })),
      recentOrders,
      topStores: topStores.map(serializeStore),
    });
  },

  async listUsers(req: Request, res: Response) {
    const { page, limit, skip, take } = paginate(req.query as Record<string, unknown>);
    const where: Prisma.UserWhereInput = {};
    if (req.query.q) where.OR = [{ name: { contains: String(req.query.q) } }, { email: { contains: String(req.query.q) } }];
    if (req.query.role) where.role = String(req.query.role) as never;
    if (req.query.status === 'active') where.isActive = true;
    if (req.query.status === 'inactive') where.isActive = false;
    if (req.query.verified === 'true') where.emailVerifiedAt = { not: null };
    if (req.query.verified === 'false') where.emailVerifiedAt = null;

    const [total, users] = await Promise.all([
      prisma.user.count({ where }),
      prisma.user.findMany({
        where,
        select: { ...PUBLIC_USER_SELECT, store: { select: { id: true, name: true, slug: true, status: true } }, _count: { select: { orders: true } } },
        orderBy: { createdAt: 'desc' },
        skip,
        take,
      }),
    ]);
    ok(res, { users, meta: pageMeta(total, page, limit) });
  },

  async getUser(req: Request, res: Response) {
    const user = await prisma.user.findUnique({
      where: { id: Number(req.params.id) },
      select: {
        ...PUBLIC_USER_SELECT,
        store: { select: { id: true, name: true, slug: true, status: true, _count: { select: { products: true, orders: true } } } },
        _count: { select: { orders: true, addresses: true, wishlists: true } },
      },
    });
    if (!user) throw ApiError.notFound('Pengguna tidak ditemukan');
    ok(res, { user });
  },

  /** Aktif/nonaktifkan akun. Superadmin tidak boleh menonaktifkan dirinya sendiri. */
  async setUserStatus(req: Request, res: Response) {
    const { isActive, reason } = z
      .object({ isActive: z.boolean(), reason: z.string().trim().max(300).optional() })
      .parse(req.body);
    const targetId = Number(req.params.id);

    const target = await prisma.user.findUnique({ where: { id: targetId } });
    if (!target) throw ApiError.notFound('Pengguna tidak ditemukan');
    if (target.id === req.user!.id) throw ApiError.badRequest('Kamu tidak dapat menonaktifkan akunmu sendiri');
    if (!isActive && target.role === 'SUPERADMIN') throw ApiError.forbidden('Akun superadmin lain tidak dapat dinonaktifkan');

    const user = await prisma.user.update({ where: { id: targetId }, data: { isActive }, select: PUBLIC_USER_SELECT });
    await logActivity({
      userId: req.user!.id,
      action: isActive ? 'USER_ACTIVATED' : 'USER_DEACTIVATED',
      entity: 'User',
      entityId: targetId,
      description: `Akun ${target.name} (${target.email}) ${isActive ? 'diaktifkan' : 'dinonaktifkan'}`,
      meta: reason ? { reason } : undefined,
    });
    ok(res, { user }, `Akun berhasil ${isActive ? 'diaktifkan' : 'dinonaktifkan'}`);
  },

  async listCategories(_req: Request, res: Response) {
    const categories = await prisma.category.findMany({
      orderBy: { sortOrder: 'asc' },
      include: { _count: { select: { products: true } } },
    });
    ok(res, { categories: categories.map((c) => ({ ...c, productCount: c._count.products })) });
  },

  async createCategory(req: Request, res: Response) {
    const data = categorySchema.parse(req.body);
    const category = await prisma.category.create({ data: { ...data, slug: data.slug ? slugify(data.slug) : slugify(data.name) } });
    await logActivity({ userId: req.user!.id, action: 'CATEGORY_CREATED', entity: 'Category', entityId: category.id, description: `Kategori ${category.name} dibuat` });
    ok(res, { category }, 'Kategori ditambahkan');
  },

  async updateCategory(req: Request, res: Response) {
    const data = categorySchema.partial().parse(req.body);
    const category = await prisma.category.update({
      where: { id: Number(req.params.id) },
      data: { ...data, ...(data.slug ? { slug: slugify(data.slug) } : {}) },
    });
    ok(res, { category }, 'Kategori diperbarui');
  },

  async deleteCategory(req: Request, res: Response) {
    const id = Number(req.params.id);
    const count = await prisma.product.count({ where: { categoryId: id } });
    if (count > 0) throw ApiError.badRequest(`Kategori masih dipakai ${count} produk. Pindahkan produk terlebih dahulu.`);
    await prisma.category.delete({ where: { id } });
    await logActivity({ userId: req.user!.id, action: 'CATEGORY_DELETED', entity: 'Category', entityId: id, description: 'Kategori dihapus' });
    ok(res, null, 'Kategori dihapus');
  },

  async listStores(req: Request, res: Response) {
    const { page, limit, skip, take } = paginate(req.query as Record<string, unknown>);
    const where: Prisma.StoreWhereInput = {};
    if (req.query.q) where.OR = [{ name: { contains: String(req.query.q) } }, { slug: { contains: String(req.query.q) } }];
    if (req.query.status) where.status = String(req.query.status) as never;

    const [total, stores] = await Promise.all([
      prisma.store.count({ where }),
      prisma.store.findMany({
        where,
        include: { user: { select: { id: true, name: true, email: true, isActive: true } }, _count: { select: { products: true, orders: true } } },
        orderBy: { createdAt: 'desc' },
        skip,
        take,
      }),
    ]);
    ok(res, { stores: stores.map((s) => ({ ...serializeStore(s), owner: s.user, orderCount: s._count.orders })), meta: pageMeta(total, page, limit) });
  },

  async setStoreStatus(req: Request, res: Response) {
    const { status } = z.object({ status: z.enum(['ACTIVE', 'INACTIVE', 'SUSPENDED']) }).parse(req.body);
    const store = await prisma.store.update({ where: { id: Number(req.params.id) }, data: { status } });
    await logActivity({ userId: req.user!.id, action: 'STORE_STATUS_UPDATED', entity: 'Store', entityId: store.id, description: `Status toko ${store.name} menjadi ${status}` });
    ok(res, { store: serializeStore(store) }, 'Status toko diperbarui');
  },

  async listProducts(req: Request, res: Response) {
    const { page, limit, skip, take } = paginate(req.query as Record<string, unknown>);
    const where: Prisma.ProductWhereInput = {};
    if (req.query.q) where.name = { contains: String(req.query.q) };
    if (req.query.status) where.status = String(req.query.status) as never;
    if (req.query.storeId) where.storeId = Number(req.query.storeId);

    const [total, products] = await Promise.all([
      prisma.product.count({ where }),
      prisma.product.findMany({
        where,
        include: {
          images: { orderBy: { sortOrder: 'asc' }, take: 1 },
          category: { select: { id: true, name: true, slug: true, icon: true } },
          store: { select: { id: true, name: true, slug: true, logo: true } },
        },
        orderBy: { createdAt: 'desc' },
        skip,
        take,
      }),
    ]);
    ok(res, { products: products.map(serializeProduct), meta: pageMeta(total, page, limit) });
  },

  /** Hapus produk yang melanggar ketentuan. */
  async deleteProduct(req: Request, res: Response) {
    const product = await prisma.product.findUnique({ where: { id: Number(req.params.id) }, include: { store: true } });
    if (!product) throw ApiError.notFound('Produk tidak ditemukan');
    const { reason } = z.object({ reason: z.string().trim().max(300).optional() }).parse(req.body ?? {});
    await prisma.product.delete({ where: { id: product.id } });
    await logActivity({
      userId: req.user!.id,
      action: 'PRODUCT_REMOVED_BY_ADMIN',
      entity: 'Product',
      entityId: product.id,
      description: `Produk ${product.name} dari toko ${product.store.name} dihapus admin`,
      meta: reason ? { reason } : undefined,
    });
    ok(res, null, 'Produk dihapus');
  },

  async listOrders(req: Request, res: Response) {
    const { page, limit, skip, take } = paginate(req.query as Record<string, unknown>);
    const where: Prisma.OrderWhereInput = {};
    if (req.query.status) where.status = String(req.query.status) as never;
    if (req.query.paymentStatus) where.paymentStatus = String(req.query.paymentStatus) as never;
    if (req.query.q) where.OR = [{ orderNumber: { contains: String(req.query.q) } }, { recipientName: { contains: String(req.query.q) } }];

    const [total, orders] = await Promise.all([
      prisma.order.count({ where }),
      prisma.order.findMany({
        where,
        include: {
          store: { select: { id: true, name: true, slug: true } },
          customer: { select: { id: true, name: true, email: true } },
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
    const order = await prisma.order.findUnique({
      where: { id: Number(req.params.id) },
      include: {
        store: { select: { id: true, name: true, slug: true } },
        customer: { select: { id: true, name: true, email: true, phone: true } },
        items: { include: { product: { include: { images: { take: 1 } } } } },
        payment: true,
        histories: { orderBy: { createdAt: 'asc' } },
      },
    });
    if (!order) throw ApiError.notFound('Pesanan tidak ditemukan');
    ok(res, { order: serializeOrder(order) });
  },

  async listPayments(req: Request, res: Response) {
    const { page, limit, skip, take } = paginate(req.query as Record<string, unknown>);
    const where: Prisma.PaymentWhereInput = {};
    if (req.query.status) where.status = String(req.query.status) as never;
    if (req.query.method) where.method = String(req.query.method) as never;

    const [total, payments] = await Promise.all([
      prisma.payment.count({ where }),
      prisma.payment.findMany({
        where,
        include: { order: { select: { id: true, orderNumber: true, store: { select: { name: true } }, customer: { select: { name: true, email: true } } } } },
        orderBy: { createdAt: 'desc' },
        skip,
        take,
      }),
    ]);
    ok(res, { payments, meta: pageMeta(total, page, limit) });
  },

  async listBanners(_req: Request, res: Response) {
    const banners = await prisma.banner.findMany({ orderBy: { sortOrder: 'asc' } });
    ok(res, { banners: banners.map(serializeBanner) });
  },

  async createBanner(req: Request, res: Response) {
    const data = bannerSchema.parse(req.body);
    const banner = await prisma.banner.create({ data });
    await logActivity({ userId: req.user!.id, action: 'BANNER_CREATED', entity: 'Banner', entityId: banner.id, description: `Banner ${banner.title} dibuat` });
    ok(res, { banner }, 'Banner ditambahkan');
  },

  async updateBanner(req: Request, res: Response) {
    const data = bannerSchema.partial().parse(req.body);
    const banner = await prisma.banner.update({ where: { id: Number(req.params.id) }, data });
    ok(res, { banner }, 'Banner diperbarui');
  },

  async deleteBanner(req: Request, res: Response) {
    await prisma.banner.delete({ where: { id: Number(req.params.id) } });
    ok(res, null, 'Banner dihapus');
  },

  async uploadBannerImage(req: Request, res: Response) {
    if (!req.file) throw ApiError.badRequest('Pilih file gambar terlebih dahulu');
    ok(res, { url: `uploads/${req.uploadFolder}/${req.file.filename}` }, 'Gambar diunggah');
  },

  async activity(req: Request, res: Response) {
    const { page, limit, skip, take } = paginate(req.query as Record<string, unknown>);
    const where: Prisma.ActivityLogWhereInput = {};
    if (req.query.entity) where.entity = String(req.query.entity);
    if (req.query.q) where.description = { contains: String(req.query.q) };

    const [total, logs] = await Promise.all([
      prisma.activityLog.count({ where }),
      prisma.activityLog.findMany({
        where,
        include: { user: { select: { id: true, name: true, email: true, role: true } } },
        orderBy: { createdAt: 'desc' },
        skip,
        take,
      }),
    ]);
    ok(res, { logs, meta: pageMeta(total, page, limit) });
  },

  async notifications(req: Request, res: Response) {
    const items = await prisma.notification.findMany({ where: { userId: req.user!.id }, orderBy: { createdAt: 'desc' }, take: 30 });
    ok(res, { notifications: items });
  },
};

const categorySchema = z.object({
  name: z.string().trim().min(2).max(40),
  slug: z.string().trim().max(40).optional(),
  icon: z.string().trim().max(40).optional().or(z.literal('')),
  sortOrder: z.coerce.number().int().min(0).default(0),
  isActive: z.boolean().default(true),
});

const bannerSchema = z.object({
  title: z.string().trim().min(3).max(80),
  description: z.string().trim().max(300).optional().or(z.literal('')),
  image: z.string().trim().min(1, 'Gambar banner wajib diunggah'),
  ctaLabel: z.string().trim().max(40).optional().or(z.literal('')),
  ctaUrl: z.string().trim().max(200).optional().or(z.literal('')),
  sortOrder: z.coerce.number().int().min(0).default(0),
  status: z.enum(['ACTIVE', 'INACTIVE']).default('ACTIVE'),
});
