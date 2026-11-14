import type { Request, Response } from 'express';
import type { Prisma } from '@prisma/client';
import { prisma } from '../config/prisma.js';
import { ApiError, ok, pageMeta, paginate } from '../utils/http.js';
import { serializeBanner, serializeProduct, serializeStore } from '../utils/serialize.js';

const productInclude = {
  images: { orderBy: { sortOrder: 'asc' }, take: 5 },
  category: { select: { id: true, name: true, slug: true, icon: true } },
  store: { select: { id: true, name: true, slug: true, logo: true, status: true } },
} satisfies Prisma.ProductInclude;

const visibleProduct: Prisma.ProductWhereInput = { status: 'ACTIVE', store: { status: 'ACTIVE' } };

function buildProductFilters(query: Record<string, any>): Prisma.ProductWhereInput {
  const where: Prisma.ProductWhereInput = { ...visibleProduct };
  const and: Prisma.ProductWhereInput[] = [];

  if (query.q) and.push({ OR: [{ name: { contains: String(query.q) } }, { description: { contains: String(query.q) } }] });
  if (query.category) and.push({ category: { slug: String(query.category) } });
  if (query.store) and.push({ store: { slug: String(query.store) } });
  if (query.type) and.push({ type: String(query.type) === 'SERVICE' ? 'SERVICE' : 'GOODS' });

  const min = Number(query.minPrice);
  const max = Number(query.maxPrice);
  if (!Number.isNaN(min) && query.minPrice) and.push({ price: { gte: min } });
  if (!Number.isNaN(max) && query.maxPrice) and.push({ price: { lte: max } });
  if (query.inStock === 'true') and.push({ stock: { gt: 0 } });

  if (and.length) where.AND = and;
  return where;
}

const orderBy = (sort?: string): Prisma.ProductOrderByWithRelationInput => {
  switch (sort) {
    case 'price_asc':
      return { price: 'asc' };
    case 'price_desc':
      return { price: 'desc' };
    case 'popular':
      return { sold: 'desc' };
    case 'oldest':
      return { createdAt: 'asc' };
    default:
      return { createdAt: 'desc' };
  }
};

export const publicController = {
  /** Data homepage: banner, kategori, dan section produk dari data nyata. */
  async home(_req: Request, res: Response) {
    const [banners, categories, latest, bestsellers, waitingForBuyer, topStores, counts] = await Promise.all([
      prisma.banner.findMany({ where: { status: 'ACTIVE' }, orderBy: { sortOrder: 'asc' } }),
      prisma.category.findMany({
        where: { isActive: true },
        orderBy: { sortOrder: 'asc' },
        include: { _count: { select: { products: { where: visibleProduct } } } },
      }),
      prisma.product.findMany({ where: visibleProduct, include: productInclude, orderBy: { createdAt: 'desc' }, take: 8 }),
      prisma.product.findMany({ where: visibleProduct, include: productInclude, orderBy: { sold: 'desc' }, take: 8 }),
      // Rekomendasi jujur: produk siap kirim yang belum pernah terjual.
      prisma.product.findMany({
        where: { ...visibleProduct, sold: 0, stock: { gt: 0 } },
        include: productInclude,
        orderBy: { createdAt: 'desc' },
        take: 8,
      }),
      prisma.store.findMany({
        where: { status: 'ACTIVE' },
        select: { id: true, name: true, slug: true, logo: true, banner: true, description: true, campus: true, _count: { select: { products: true } } },
        orderBy: { createdAt: 'desc' },
        take: 6,
      }),
      Promise.all([
        prisma.user.count({ where: { role: 'SELLER' } }),
        prisma.store.count(),
        prisma.product.count({ where: visibleProduct }),
        prisma.order.count(),
      ]),
    ]);

    const categorySections = await Promise.all(
      categories.slice(0, 4).map(async (category) => ({
        category,
        products: (
          await prisma.product.findMany({
            where: { ...visibleProduct, categoryId: category.id },
            include: productInclude,
            orderBy: { createdAt: 'desc' },
            take: 4,
          })
        ).map(serializeProduct),
      })),
    );

    ok(res, {
      banners: banners.map(serializeBanner),
      categories: categories.map((c) => ({ ...c, productCount: c._count.products })),
      latest: latest.map(serializeProduct),
      bestsellers: bestsellers.filter((p) => p.sold > 0).map(serializeProduct),
      recommended: waitingForBuyer.map(serializeProduct),
      categorySections: categorySections.filter((s) => s.products.length > 0),
      topStores: topStores.map(serializeStore),
      stats: { sellers: counts[0], stores: counts[1], products: counts[2], orders: counts[3] },
    });
  },

  async categories(_req: Request, res: Response) {
    const categories = await prisma.category.findMany({
      where: { isActive: true },
      orderBy: { sortOrder: 'asc' },
      include: { _count: { select: { products: { where: visibleProduct } } } },
    });
    ok(res, { categories: categories.map((c) => ({ ...c, productCount: c._count.products })) });
  },

  async products(req: Request, res: Response) {
    const { page, limit, skip, take } = paginate(req.query as Record<string, unknown>);
    const where = buildProductFilters(req.query as Record<string, any>);

    const [total, products] = await Promise.all([
      prisma.product.count({ where }),
      prisma.product.findMany({ where, include: productInclude, orderBy: orderBy(req.query.sort as string), skip, take }),
    ]);

    ok(res, { products: products.map(serializeProduct), meta: pageMeta(total, page, limit) });
  },

  async productDetail(req: Request, res: Response) {
    const product = await prisma.product.findUnique({
      where: { slug: req.params.slug },
      include: {
        ...productInclude,
        store: { select: { id: true, name: true, slug: true, logo: true, banner: true, description: true, campus: true, address: true, phone: true, status: true } },
        category: { select: { id: true, name: true, slug: true, icon: true } },
      },
    });
    if (!product || product.status !== 'ACTIVE' || product.store.status !== 'ACTIVE') {
      throw ApiError.notFound('Produk tidak ditemukan atau sudah tidak aktif');
    }

    const [fromStore, related] = await Promise.all([
      prisma.product.findMany({
        where: { ...visibleProduct, storeId: product.storeId, id: { not: product.id } },
        include: productInclude,
        orderBy: { createdAt: 'desc' },
        take: 4,
      }),
      prisma.product.findMany({
        where: { ...visibleProduct, categoryId: product.categoryId, id: { not: product.id } },
        include: productInclude,
        orderBy: { sold: 'desc' },
        take: 4,
      }),
    ]);

    ok(res, {
      product: serializeProduct(product),
      store: serializeStore(product.store),
      fromStore: fromStore.map(serializeProduct),
      related: related.map(serializeProduct),
    });
  },

  async stores(req: Request, res: Response) {
    const { page, limit, skip, take } = paginate(req.query as Record<string, unknown>);
    const q = req.query.q ? String(req.query.q) : undefined;
    const where: Prisma.StoreWhereInput = { status: 'ACTIVE' };
    if (q) where.OR = [{ name: { contains: q } }, { description: { contains: q } }];

    const [total, stores] = await Promise.all([
      prisma.store.count({ where }),
      prisma.store.findMany({
        where,
        select: {
          id: true, name: true, slug: true, logo: true, banner: true, description: true, campus: true, address: true,
          _count: { select: { products: { where: visibleProduct } } },
        },
        orderBy: { createdAt: 'desc' },
        skip,
        take,
      }),
    ]);

    ok(res, { stores: stores.map(serializeStore), meta: pageMeta(total, page, limit) });
  },

  async storeDetail(req: Request, res: Response) {
    const store = await prisma.store.findUnique({
      where: { slug: req.params.slug },
      include: {
        user: { select: { name: true, createdAt: true } },
        _count: { select: { products: { where: visibleProduct } } },
      },
    });
    if (!store || store.status !== 'ACTIVE') throw ApiError.notFound('Toko tidak ditemukan');

    const { page, limit, skip, take } = paginate(req.query as Record<string, unknown>);
    const where: Prisma.ProductWhereInput = {
      ...visibleProduct,
      storeId: store.id,
      ...(req.query.category ? { category: { slug: String(req.query.category) } } : {}),
      ...(req.query.q ? { name: { contains: String(req.query.q) } } : {}),
    };

    const [total, products, categories] = await Promise.all([
      prisma.product.count({ where }),
      prisma.product.findMany({ where, include: productInclude, orderBy: orderBy(req.query.sort as string), skip, take }),
      prisma.category.findMany({
        where: { isActive: true, products: { some: { storeId: store.id, status: 'ACTIVE' } } },
        orderBy: { sortOrder: 'asc' },
      }),
    ]);

    ok(res, {
      store: serializeStore(store),
      owner: store.user,
      products: products.map(serializeProduct),
      categories,
      meta: pageMeta(total, page, limit),
    });
  },
};
