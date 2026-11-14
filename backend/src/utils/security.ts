import crypto from 'node:crypto';
import bcrypt from 'bcrypt';
import jwt from 'jsonwebtoken';
import type { Response } from 'express';
import { env } from '../config/env.js';
import type { Role } from '@prisma/client';

export const hashPassword = (plain: string) => bcrypt.hash(plain, 10);
export const verifyPassword = (plain: string, hash: string) => bcrypt.compare(plain, hash);

export const randomToken = () => crypto.randomBytes(32).toString('hex');

export type AuthPayload = { userId: number; role: Role; email: string };

export const signToken = (payload: AuthPayload) =>
  jwt.sign(payload, env.jwtSecret, { expiresIn: env.jwtExpiresIn } as jwt.SignOptions);

export const verifyToken = (token: string): AuthPayload => jwt.verify(token, env.jwtSecret) as AuthPayload;

export const AUTH_COOKIE = 'mahapreneur_token';

/**
 * Token disimpan pada cookie httpOnly sehingga tidak dapat dibaca JavaScript
 * (mengurangi risiko pencurian token lewat XSS). Frontend memakai kredensial cookie ini.
 */
export function setAuthCookie(res: Response, token: string) {
  res.cookie(AUTH_COOKIE, token, {
    httpOnly: true,
    sameSite: env.isProd ? 'none' : 'lax',
    secure: env.isProd,
    maxAge: 7 * 24 * 60 * 60 * 1000,
    path: '/',
  });
}

export function clearAuthCookie(res: Response) {
  res.clearCookie(AUTH_COOKIE, { path: '/' });
}

export const slugify = (value: string) =>
  value
    .toLowerCase()
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .replace(/[^a-z0-9]+/g, '-')
    .replace(/^-+|-+$/g, '')
    .slice(0, 60) || 'toko';

/** Nomor pesanan manusiawi: MP-YYMMDD-XXXX */
export function orderNumber() {
  const d = new Date();
  const stamp = `${String(d.getFullYear()).slice(2)}${String(d.getMonth() + 1).padStart(2, '0')}${String(d.getDate()).padStart(2, '0')}`;
  return `MP-${stamp}-${crypto.randomInt(1000, 9999)}`;
}

export function invoiceNumber(orderNum: string) {
  return `${orderNum}-${Date.now().toString(36).toUpperCase()}`;
}
