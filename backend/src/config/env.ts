import 'dotenv/config';

function required(name: string, fallback?: string): string {
  const value = process.env[name] ?? fallback;
  if (value === undefined || value === '') {
    throw new Error(`Environment variable ${name} wajib diisi`);
  }
  return value;
}

const num = (name: string, fallback: number) => Number(process.env[name] ?? fallback);

export const env = {
  nodeEnv: process.env.NODE_ENV ?? 'development',
  isProd: process.env.NODE_ENV === 'production',
  port: num('PORT', 4000),
  appUrl: process.env.APP_URL ?? 'http://localhost:3000',
  apiUrl: process.env.API_URL ?? 'http://localhost:4000',
  corsOrigin: (process.env.CORS_ORIGIN ?? 'http://localhost:3000').split(',').map((s) => s.trim()),

  jwtSecret: required('JWT_SECRET'),
  jwtExpiresIn: process.env.JWT_EXPIRES_IN ?? '7d',
  emailVerificationTtlHours: num('EMAIL_VERIFICATION_TTL_HOURS', 24),
  passwordResetTtlMinutes: num('PASSWORD_RESET_TTL_MINUTES', 60),

  smtp: {
    host: process.env.SMTP_HOST ?? '',
    port: num('SMTP_PORT', 587),
    user: process.env.SMTP_USER ?? '',
    pass: process.env.SMTP_PASS ?? '',
    from: process.env.MAIL_FROM ?? 'Mahapreneur <no-reply@mahapreneur.test>',
  },

  uploadDir: process.env.UPLOAD_DIR ?? 'uploads',
  maxUploadMb: num('MAX_UPLOAD_MB', 5),

  doku: {
    clientId: process.env.DOKU_CLIENT_ID ?? '',
    secretKey: process.env.DOKU_SECRET_KEY ?? '',
    baseUrl: process.env.DOKU_BASE_URL ?? 'https://api-sandbox.doku.com',
    qrisChannel: process.env.DOKU_QRIS_CHANNEL ?? 'QRIS',
    vaChannel: process.env.DOKU_VA_CHANNEL ?? 'BCA',
    ewalletChannel: process.env.DOKU_EWALLET_CHANNEL ?? 'SHOPEEPAY',
    paymentTtlMinutes: num('PAYMENT_TTL_MINUTES', 60),
  },
};

/** Pembayaran online berjalan sungguhan hanya bila kredensial DOKU tersedia. */
export const dokuConfigured = Boolean(env.doku.clientId && env.doku.secretKey);
