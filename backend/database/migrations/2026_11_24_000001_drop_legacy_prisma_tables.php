<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Hapus schema Prisma lama yang hidup berdampingan dengan tabel Laravel
     * lowercase plural dan tidak pernah dipakai oleh aplikasi aktif.
     */
    public function up(): void
    {
        foreach ([
            'PaymentNotification', 'Payment', 'OrderStatusHistory', 'OrderItem',
            'ProductImage', 'CartItem', 'Wishlist', 'Order', 'Product', 'Cart',
            'Address', 'Notification', 'EmailVerificationToken', 'PasswordResetToken',
            'Store', 'Category', 'Banner', 'ActivityLog', 'User', '_prisma_migrations',
        ] as $table) {
            Schema::dropIfExists($table);
        }
    }

    public function down(): void
    {
        // Schema lama berasal dari Prisma dan tidak direkonstruksi oleh Laravel.
    }
};
