<?php

namespace App\Support;

use App\Models\ActivityLog;
use App\Models\Notification;
use Illuminate\Support\Facades\Log;
use Throwable;

class Activity
{
    /** Catat tindakan penting. Kegagalan pencatatan tidak boleh menggagalkan aksi utama. */
    public static function log(
        ?int $userId,
        string $action,
        string $entity,
        int|string|null $entityId,
        string $description,
        ?array $meta = null,
    ): void {
        try {
            ActivityLog::create([
                'user_id' => $userId,
                'action' => $action,
                'entity' => $entity,
                'entity_id' => $entityId !== null ? (string) $entityId : null,
                'description' => $description,
                'meta' => $meta,
            ]);
        } catch (Throwable $error) {
            Log::error('Gagal mencatat aktivitas: '.$error->getMessage());
        }
    }

    /** Notifikasi dalam aplikasi (tabel database, tanpa WebSocket). */
    public static function notify(int $userId, string $title, string $message, string $type = 'info', ?string $link = null): void
    {
        try {
            Notification::create([
                'user_id' => $userId,
                'title' => $title,
                'message' => $message,
                'type' => $type,
                'link' => $link,
            ]);
        } catch (Throwable $error) {
            Log::error('Gagal menyimpan notifikasi: '.$error->getMessage());
        }
    }
}
