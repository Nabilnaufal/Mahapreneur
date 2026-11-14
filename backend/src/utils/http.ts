import type { NextFunction, Request, RequestHandler, Response } from 'express';
import { ZodError } from 'zod';

export class ApiError extends Error {
  status: number;
  details?: unknown;
  constructor(status: number, message: string, details?: unknown) {
    super(message);
    this.status = status;
    this.details = details;
  }
  static badRequest = (m = 'Permintaan tidak valid', d?: unknown) => new ApiError(400, m, d);
  static unauthorized = (m = 'Silakan masuk terlebih dahulu') => new ApiError(401, m);
  static forbidden = (m = 'Kamu tidak punya akses ke resource ini') => new ApiError(403, m);
  static notFound = (m = 'Data tidak ditemukan') => new ApiError(404, m);
  static conflict = (m = 'Data sudah ada') => new ApiError(409, m);
}

/** Bungkus handler async agar error-nya diteruskan ke error middleware. */
export const ah =
  (fn: (req: Request, res: Response, next: NextFunction) => Promise<unknown>): RequestHandler =>
  (req, res, next) => {
    fn(req, res, next).catch(next);
  };

export const ok = <T>(res: Response, data: T, message = 'OK') => res.json({ success: true, message, data });

export function paginate(query: Record<string, unknown>) {
  const page = Math.max(1, Number(query.page) || 1);
  const rawLimit = Number(query.limit) || 12;
  const limit = Math.min(60, Math.max(1, rawLimit));
  return { page, limit, skip: (page - 1) * limit, take: limit };
}

export function pageMeta(total: number, page: number, limit: number) {
  return { total, page, limit, totalPages: Math.max(1, Math.ceil(total / limit)) };
}

export const errorHandler = (err: unknown, _req: Request, res: Response, _next: NextFunction) => {
  if (err instanceof ZodError) {
    return res.status(422).json({
      success: false,
      message: 'Validasi gagal',
      errors: err.issues.map((i) => ({ field: i.path.join('.'), message: i.message })),
    });
  }
  if (err instanceof ApiError) {
    return res.status(err.status).json({ success: false, message: err.message, errors: err.details });
  }
  const message = err instanceof Error ? err.message : 'Terjadi kesalahan tak terduga';
  console.error('[error]', err);
  res.status(500).json({ success: false, message: env_message(message) });
};

const env_message = (m: string) => (process.env.NODE_ENV === 'production' ? 'Terjadi kesalahan pada server' : m);

export const notFoundHandler = (_req: Request, res: Response) =>
  res.status(404).json({ success: false, message: 'Endpoint tidak ditemukan' });
