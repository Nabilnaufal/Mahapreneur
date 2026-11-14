import { prisma } from '../config/prisma.js';

type LogInput = {
  userId?: number | null;
  action: string;
  entity: string;
  entityId?: string | number | null;
  description: string;
  meta?: Record<string, unknown>;
};

/** Catat tindakan penting. Kegagalan pencatatan tidak boleh menggagalkan aksi utama. */
export async function logActivity(input: LogInput) {
  try {
    await prisma.activityLog.create({
      data: {
        userId: input.userId ?? null,
        action: input.action,
        entity: input.entity,
        entityId: input.entityId != null ? String(input.entityId) : null,
        description: input.description,
        meta: input.meta as never,
      },
    });
  } catch (err) {
    console.error('[activity] gagal mencatat', err);
  }
}

/** Notifikasi dalam aplikasi (tabel database, tanpa WebSocket). */
export async function notify(input: { userId: number; title: string; message: string; type?: string; link?: string }) {
  try {
    await prisma.notification.create({
      data: {
        userId: input.userId,
        title: input.title,
        message: input.message,
        type: input.type ?? 'info',
        link: input.link ?? null,
      },
    });
  } catch (err) {
    console.error('[notification] gagal menyimpan', err);
  }
}
