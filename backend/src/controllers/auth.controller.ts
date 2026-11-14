import type { Request, Response } from 'express';
import { z } from 'zod';
import { prisma } from '../config/prisma.js';
import { env } from '../config/env.js';
import { resetPasswordMail, sendMail, verifyEmailMail } from '../config/mailer.js';
import { ApiError, ok } from '../utils/http.js';
import {
  clearAuthCookie,
  hashPassword,
  randomToken,
  setAuthCookie,
  signToken,
  verifyPassword,
} from '../utils/security.js';
import { PUBLIC_USER_SELECT, serializeUser } from '../utils/serialize.js';
import { logActivity, notify } from '../utils/activity.js';

const passwordRule = z
  .string()
  .min(8, 'Password minimal 8 karakter')
  .regex(/[A-Za-z]/, 'Password harus mengandung huruf')
  .regex(/[0-9]/, 'Password harus mengandung angka');

export const registerSchema = z
  .object({
    name: z.string().trim().min(3, 'Nama minimal 3 karakter').max(80),
    email: z.string().trim().toLowerCase().email('Format email tidak valid'),
    password: passwordRule,
    confirmPassword: z.string(),
    role: z.enum(['CUSTOMER', 'SELLER']),
  })
  .refine((d) => d.password === d.confirmPassword, {
    message: 'Konfirmasi password tidak sama',
    path: ['confirmPassword'],
  });

export const loginSchema = z.object({
  email: z.string().trim().toLowerCase().email('Format email tidak valid'),
  password: z.string().min(1, 'Password wajib diisi'),
});

const addHours = (h: number) => new Date(Date.now() + h * 60 * 60 * 1000);
const addMinutes = (m: number) => new Date(Date.now() + m * 60 * 1000);

async function issueVerification(userId: number, name: string, email: string) {
  const token = randomToken();
  await prisma.emailVerificationToken.upsert({
    where: { userId },
    create: { userId, token, expiresAt: addHours(env.emailVerificationTtlHours) },
    update: { token, expiresAt: addHours(env.emailVerificationTtlHours), usedAt: null },
  });
  const url = `${env.appUrl}/auth/verify-email?token=${token}`;
  await sendMail(email, 'Verifikasi email Mahapreneur', verifyEmailMail(name, url));
  return token;
}

export const authController = {
  /** Registrasi publik. Role superadmin tidak pernah bisa dihasilkan dari sini. */
  async register(req: Request, res: Response) {
    const data = registerSchema.parse(req.body);

    const existing = await prisma.user.findUnique({ where: { email: data.email } });
    if (existing) throw ApiError.conflict('Email sudah terdaftar');

    const user = await prisma.user.create({
      data: {
        name: data.name,
        email: data.email,
        password: await hashPassword(data.password),
        role: data.role,
      },
    });

    const devToken = await issueVerification(user.id, user.name, user.email);
    await logActivity({
      userId: user.id,
      action: 'REGISTER',
      entity: 'User',
      entityId: user.id,
      description: `${user.name} mendaftar sebagai ${data.role}`,
    });

    ok(
      res,
      {
        user: serializeUser(user),
        // Tanpa SMTP (mode pengembangan) token dikembalikan agar alur bisa diuji.
        verificationToken: env.smtp.host ? undefined : devToken,
      },
      'Registrasi berhasil. Cek email untuk verifikasi.',
    );
  },

  async verifyEmail(req: Request, res: Response) {
    const { token } = z.object({ token: z.string().min(10) }).parse(req.body);
    const record = await prisma.emailVerificationToken.findUnique({ where: { token }, include: { user: true } });

    if (!record) throw ApiError.badRequest('Token verifikasi tidak valid');
    if (record.usedAt) throw ApiError.badRequest('Email sudah diverifikasi sebelumnya');
    if (record.expiresAt < new Date()) throw ApiError.badRequest('Token verifikasi sudah kedaluwarsa. Minta email baru.');

    await prisma.$transaction([
      prisma.user.update({ where: { id: record.userId }, data: { emailVerifiedAt: new Date() } }),
      prisma.emailVerificationToken.update({ where: { id: record.id }, data: { usedAt: new Date() } }),
    ]);

    await logActivity({
      userId: record.userId,
      action: 'VERIFY_EMAIL',
      entity: 'User',
      entityId: record.userId,
      description: `${record.user.name} memverifikasi email`,
    });
    await notify({
      userId: record.userId,
      title: 'Email terverifikasi',
      message: 'Akunmu sudah aktif. Selamat berbelanja di Mahapreneur!',
      type: 'success',
    });

    ok(res, { email: record.user.email }, 'Email berhasil diverifikasi. Silakan masuk.');
  },

  async resendVerification(req: Request, res: Response) {
    const { email } = z.object({ email: z.string().trim().toLowerCase().email() }).parse(req.body);
    const user = await prisma.user.findUnique({ where: { email } });
    // Balasan seragam supaya tidak membocorkan email mana yang terdaftar.
    const message = 'Jika email terdaftar dan belum diverifikasi, kami sudah mengirim tautan baru.';
    if (!user || user.emailVerifiedAt) return ok(res, null, message);

    const token = await issueVerification(user.id, user.name, user.email);
    ok(res, { verificationToken: env.smtp.host ? undefined : token }, message);
  },

  async login(req: Request, res: Response) {
    const data = loginSchema.parse(req.body);
    const user = await prisma.user.findUnique({
      where: { email: data.email },
      include: { store: { select: { id: true, name: true, slug: true, status: true, logo: true } } },
    });
    if (!user || !(await verifyPassword(data.password, user.password))) {
      throw ApiError.unauthorized('Email atau password salah');
    }
    if (!user.isActive) throw ApiError.forbidden('Akun kamu dinonaktifkan. Hubungi admin Mahapreneur.');
    if (!user.emailVerifiedAt) {
      throw new ApiError(403, 'Email belum diverifikasi. Cek inbox atau minta kirim ulang.', {
        code: 'EMAIL_NOT_VERIFIED',
      });
    }

    setAuthCookie(res, signToken({ userId: user.id, role: user.role, email: user.email }));
    await logActivity({
      userId: user.id,
      action: 'LOGIN',
      entity: 'User',
      entityId: user.id,
      description: `${user.name} masuk ke aplikasi`,
    });
    ok(res, { user: serializeUser(user) }, 'Berhasil masuk');
  },

  async logout(_req: Request, res: Response) {
    clearAuthCookie(res);
    ok(res, null, 'Berhasil keluar');
  },

  async me(req: Request, res: Response) {
    const user = await prisma.user.findUnique({
      where: { id: req.user!.id },
      select: { ...PUBLIC_USER_SELECT, store: { select: { id: true, name: true, slug: true, status: true, logo: true } } },
    });
    ok(res, { user: serializeUser(user) });
  },

  async forgotPassword(req: Request, res: Response) {
    const { email } = z.object({ email: z.string().trim().toLowerCase().email() }).parse(req.body);
    const user = await prisma.user.findUnique({ where: { email } });
    const message = 'Jika email terdaftar, kami sudah mengirim tautan reset password.';
    if (!user) return ok(res, null, message);

    const token = randomToken();
    await prisma.passwordResetToken.create({
      data: { userId: user.id, token, expiresAt: addMinutes(env.passwordResetTtlMinutes) },
    });
    await sendMail(user.email, 'Reset password Mahapreneur', resetPasswordMail(user.name, `${env.appUrl}/auth/reset-password?token=${token}`));

    ok(res, { resetToken: env.smtp.host ? undefined : token }, message);
  },

  async resetPassword(req: Request, res: Response) {
    const data = z
      .object({ token: z.string().min(10), password: passwordRule, confirmPassword: z.string() })
      .refine((d) => d.password === d.confirmPassword, { message: 'Konfirmasi password tidak sama', path: ['confirmPassword'] })
      .parse(req.body);

    const record = await prisma.passwordResetToken.findUnique({ where: { token: data.token } });
    if (!record || record.usedAt) throw ApiError.badRequest('Token reset tidak valid atau sudah dipakai');
    if (record.expiresAt < new Date()) throw ApiError.badRequest('Token reset sudah kedaluwarsa. Minta tautan baru.');

    await prisma.$transaction([
      prisma.user.update({ where: { id: record.userId }, data: { password: await hashPassword(data.password) } }),
      prisma.passwordResetToken.update({ where: { id: record.id }, data: { usedAt: new Date() } }),
    ]);
    await logActivity({
      userId: record.userId,
      action: 'RESET_PASSWORD',
      entity: 'User',
      entityId: record.userId,
      description: 'Password diubah melalui tautan reset',
    });

    ok(res, null, 'Password berhasil diubah. Silakan masuk dengan password baru.');
  },
};
