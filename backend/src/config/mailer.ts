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

const escapeHtml = (value: string) =>
  value.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#039;');

const layout = (title: string, body: string, cta?: { label: string; url: string }) => `
<!doctype html>
<html lang="id">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>${escapeHtml(title)}</title>
  </head>
  <body style="margin:0;background:#F7FAFC;font-family:Arial,Helvetica,sans-serif;color:#0A1926">
    <div style="display:none;max-height:0;overflow:hidden;opacity:0">Mahapreneur — marketplace bisnis mahasiswa.</div>
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background:#F7FAFC">
      <tr><td align="center" style="padding:36px 16px">
        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="max-width:560px">
          <tr><td style="padding:0 8px 18px">
            <a href="${escapeHtml(env.appUrl)}" style="text-decoration:none;color:#102C40">
              <img src="${escapeHtml(env.appUrl)}/logo-mark.png" width="34" height="34" alt="Mahapreneur" style="vertical-align:middle;border:0;border-radius:10px" />
              <span style="display:inline-block;vertical-align:middle;margin-left:8px;font-size:19px;font-weight:800;letter-spacing:-.4px">Mahapreneur</span>
            </a>
          </td></tr>
          <tr><td style="background:#102C40;border-radius:22px 22px 0 0;padding:7px 28px 0"><div style="height:5px;border-radius:99px;background:#DBFF00"></div></td></tr>
          <tr><td style="background:#102C40;padding:30px 28px 34px">
            <p style="margin:0 0 12px;color:#DBFF00;font-size:11px;font-weight:700;letter-spacing:1.6px;text-transform:uppercase">Mahapreneur</p>
            <h1 style="margin:0;color:#FFFFFF;font-size:26px;line-height:1.25;letter-spacing:-.5px">${escapeHtml(title)}</h1>
          </td></tr>
          <tr><td style="background:#FFFFFF;padding:30px 28px 28px;border-radius:0 0 22px 22px;box-shadow:0 12px 30px rgba(10,25,38,.08)">
            <div style="color:#33475B;font-size:15px;line-height:1.7">${body}</div>
            ${cta ? `<p style="margin:26px 0 0"><a href="${escapeHtml(cta.url)}" style="display:inline-block;background:#DBFF00;color:#0A1926;padding:13px 22px;border-radius:999px;font-size:14px;font-weight:700;text-decoration:none">${escapeHtml(cta.label)} &nbsp; →</a></p>` : ''}
            <p style="margin:26px 0 0;padding-top:20px;border-top:1px solid #E7EDF1;color:#64798C;font-size:12px;line-height:1.6">Jika kamu tidak melakukan permintaan ini, abaikan email ini. Jangan bagikan tautan ini kepada siapa pun.</p>
          </td></tr>
          <tr><td align="center" style="padding:20px 8px 0;color:#64798C;font-size:12px;line-height:1.6">Mahapreneur · Satu kampus, ribuan peluang jual beli.<br /><span style="color:#9AAAB7">Email otomatis, mohon tidak membalas.</span></td></tr>
        </table>
      </td></tr>
    </table>
  </body>
</html>`;

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
