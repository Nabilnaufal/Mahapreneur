import { env } from '../config/env.js';

/** Ubah path file lokal menjadi URL absolut yang bisa dipakai frontend. */
export const fileUrl = (path?: string | null) => {
  if (!path) return null;
  if (/^https?:\/\//.test(path)) return path;
  return `${env.apiUrl}/${path.replace(/^\/+/, '')}`;
};

export const productImages = (images: { url: string }[] = []) =>
  images.map((i) => fileUrl(i.url)).filter((u): u is string => Boolean(u));

type ProductWithRelations = {
  id: number;
  name: string;
  slug: string;
  description: string;
  price: number;
  stock: number;
  type: string;
  status: string;
  sold: number;
  serviceTerms?: string | null;
  serviceDuration?: string | null;
  createdAt: Date;
  images?: { id: number; url: string }[];
  category?: { id: number; name: string; slug: string; icon?: string | null } | null;
  store?: { id: number; name: string; slug: string; logo?: string | null } | null;
};

export function serializeProduct(p: ProductWithRelations) {
  const images = productImages(p.images);
  return {
    id: p.id,
    name: p.name,
    slug: p.slug,
    description: p.description,
    price: p.price,
    stock: p.stock,
    type: p.type,
    status: p.status,
    sold: p.sold,
    serviceTerms: p.serviceTerms ?? null,
    serviceDuration: p.serviceDuration ?? null,
    createdAt: p.createdAt,
    image: images[0] ?? null,
    images,
    // Daftar gambar beserta id-nya — dibutuhkan halaman edit seller untuk menghapus gambar.
    imageRecords: (p.images ?? []).map((image) => ({ id: image.id, url: fileUrl(image.url) })),
    category: p.category ?? null,
    store: p.store ? { ...p.store, logo: fileUrl(p.store.logo) } : null,
  };
}

/** Banner menyimpan gambar sebagai path relatif; ubah ke URL absolut untuk frontend. */
export const serializeBanner = (b: any) => ({ ...b, image: fileUrl(b.image) });

export function serializeStore(s: any) {
  return {
    ...s,
    logo: fileUrl(s.logo),
    banner: fileUrl(s.banner),
    ...(s._count ? { productCount: s._count.products } : {}),
  };
}

export function serializeUser(u: any) {
  if (!u) return u;
  const { password, ...rest } = u;
  return { ...rest, avatar: fileUrl(rest.avatar) };
}

export function serializeOrder(o: any) {
  return {
    ...o,
    store: o.store ? serializeStore(o.store) : undefined,
    items: o.items?.map((it: any) => ({ ...it, image: it.product?.images?.[0] ? fileUrl(it.product.images[0].url) : null })),
  };
}

export const PUBLIC_USER_SELECT = {
  id: true,
  name: true,
  email: true,
  role: true,
  isActive: true,
  emailVerifiedAt: true,
  avatar: true,
  phone: true,
  createdAt: true,
} as const;
