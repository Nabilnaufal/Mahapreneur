import fs from 'node:fs';
import path from 'node:path';
import crypto from 'node:crypto';
import multer from 'multer';
import type { Request } from 'express';
import { env } from '../config/env.js';
import { ApiError } from '../utils/http.js';

const ALLOWED = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];

const storage = multer.diskStorage({
  destination: (req, _file, cb) => {
    const dir = path.join(process.cwd(), env.uploadDir, req.uploadFolder ?? 'misc');
    fs.mkdirSync(dir, { recursive: true });
    cb(null, dir);
  },
  filename: (_req, file, cb) => {
    const ext = path.extname(file.originalname).toLowerCase().replace(/[^a-z0-9.]/g, '') || '.jpg';
    cb(null, `${Date.now()}-${crypto.randomBytes(6).toString('hex')}${ext}`);
  },
});

export const upload = multer({
  storage,
  limits: { fileSize: env.maxUploadMb * 1024 * 1024 },
  fileFilter: (_req, file, cb) => {
    // Hanya tipe gambar yang diizinkan; ekstensi asli tidak dipercaya.
    if (!ALLOWED.includes(file.mimetype)) {
      return cb(ApiError.badRequest('Format file harus JPG, PNG, WEBP, atau GIF'));
    }
    cb(null, true);
  },
});

/** Simpan path relatif agar mudah dipindahkan ke object storage saat deployment. */
export const uploadPath = (req: Request, file: Express.Multer.File) =>
  `${env.uploadDir}/${req.uploadFolder ?? 'misc'}/${file.filename}`;

declare global {
  // eslint-disable-next-line @typescript-eslint/no-namespace
  namespace Express {
    interface Request {
      uploadFolder?: string;
    }
  }
}

export const folder = (name: string) => (req: Request, _res: unknown, next: (e?: unknown) => void) => {
  req.uploadFolder = name;
  next();
};
