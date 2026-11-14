import type { NextFunction, Request, Response } from 'express';
import type { Role } from '@prisma/client';
import { prisma } from '../config/prisma.js';
import { ApiError } from '../utils/http.js';
import { AUTH_COOKIE, verifyToken } from '../utils/security.js';

export type AuthUser = {
  id: number;
  name: string;
  email: string;
  role: Role;
  storeId?: number | null;
};

declare global {
  // eslint-disable-next-line @typescript-eslint/no-namespace
  namespace Express {
    interface Request {
      user?: AuthUser;
    }
  }
}

function readToken(req: Request) {
  const cookie = (req.cookies as Record<string, string> | undefined)?.[AUTH_COOKIE];
  if (cookie) return cookie;
  const header = req.headers.authorization;
  if (header?.startsWith('Bearer ')) return header.slice(7);
  return null;
}

/**
 * Verifikasi JWT lalu muat ulang user dari database pada setiap request.
 * Dengan begitu akun yang dinonaktifkan atau dihapus langsung kehilangan akses
 * meski tokennya masih berlaku.
 */
export async function authenticate(req: Request, _res: Response, next: NextFunction) {
  try {
    const token = readToken(req);
    if (!token) throw ApiError.unauthorized();

    let payload;
    try {
      payload = verifyToken(token);
    } catch {
      throw ApiError.unauthorized('Sesi sudah berakhir, silakan masuk kembali');
    }

    const user = await prisma.user.findUnique({
      where: { id: payload.userId },
      select: { id: true, name: true, email: true, role: true, isActive: true, store: { select: { id: true } } },
    });
    if (!user) throw ApiError.unauthorized('Akun tidak ditemukan');
    if (!user.isActive) throw ApiError.forbidden('Akun kamu dinonaktifkan. Hubungi admin Mahapreneur.');

    req.user = { id: user.id, name: user.name, email: user.email, role: user.role, storeId: user.store?.id ?? null };
    next();
  } catch (err) {
    next(err);
  }
}

export const requireRole =
  (...roles: Role[]) =>
  (req: Request, _res: Response, next: NextFunction) => {
    if (!req.user) return next(ApiError.unauthorized());
    if (!roles.includes(req.user.role)) return next(ApiError.forbidden());
    next();
  };

/** Seller harus sudah punya toko sebelum mengelola produk/pesanan. */
export function requireStore(req: Request, _res: Response, next: NextFunction) {
  if (!req.user?.storeId) return next(ApiError.badRequest('Buat toko terlebih dahulu sebelum melanjutkan'));
  next();
}

/** Pastikan user yang mengakses hanya dirinya sendiri (kecuali admin). */
export function requireSelfOrAdmin(paramName = 'id') {
  return (req: Request, _res: Response, next: NextFunction) => {
    if (!req.user) return next(ApiError.unauthorized());
    if (req.user.role === 'SUPERADMIN') return next();
    if (Number(req.params[paramName]) !== req.user.id) return next(ApiError.forbidden());
    next();
  };
}
