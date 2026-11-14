import nodemailer from 'nodemailer';
import { env } from './env.js';

const transporter = env.smtp.host
  ? nodemailer.createTransport({
      host: env.smtp.host,
      port: env.smtp.port,
      secure: env.smtp.port === 465,
      auth: env.smtp.user ? { user: env.smtp.user, pass: env.smtp.pass } : undefined,
    })
  : null;

/**
 * Kirim email. Tanpa konfigurasi SMTP, isi email dicetak ke console supaya
 * alur verifikasi/reset password tetap bisa diuji pada pengembangan lokal.
 */
export async function sendMail(to: string, subject: string, html: string) {
  if (!transporter) {
    console.log(`\n[mail:dev] to=${to}\n[mail:dev] subject=${subject}\n[mail:dev] body=${html.replace(/<[^>]+>/g, ' ').replace(/\s+/g, ' ').trim()}\n`);
    return;
  }
  await transporter.sendMail({ from: env.smtp.from, to, subject, html });
}

const layout = (title: string, body: string, cta?: { label: string; url: string }) => `
  <div style="font-family:Manrope,Segoe UI,sans-serif;background:#F7FAFC;padding:32px">
    <div style="max-width:520px;margin:auto;background:#fff;border-radius:16px;padding:32px">
      <h2 style="color:#102C40;margin:0 0 16px">${title}</h2>
      <div style="color:#0A1926;font-size:15px;line-height:1.6">${body}</div>
      ${cta ? `<p style="margin:28px 0 0"><a href="${cta.url}" style="background:#102C40;color:#fff;padding:12px 22px;border-radius:999px;text-decoration:none;display:inline-block">${cta.label}</a></p>` : ''}
      <p style="color:#64748b;font-size:12px;margin-top:28px">Mahapreneur — marketplace bisnis mahasiswa.</p>
    </div>
  </div>`;

export const verifyEmailMail = (name: string, url: string) =>
  layout(
    `Halo ${name}, verifikasi emailmu`,
    'Satu langkah lagi sebelum kamu bisa masuk ke Mahapreneur. Klik tombol di bawah untuk memverifikasi alamat email ini.',
    { label: 'Verifikasi Email', url },
  );

export const resetPasswordMail = (name: string, url: string) =>
  layout(
    `Reset password untuk ${name}`,
    'Kami menerima permintaan reset password. Klik tombol di bawah untuk membuat password baru. Abaikan email ini jika kamu tidak memintanya.',
    { label: 'Buat Password Baru', url },
  );
