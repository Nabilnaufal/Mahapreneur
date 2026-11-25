<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('support_conversations', function (Blueprint $table) {
            $table->dropForeign('support_conversations_customer_id_foreign');
        });

        // MySQL tidak mengizinkan index unik dihapus dalam statement yang
        // sama ketika index tersebut masih dipakai oleh foreign key.
        Schema::table('support_conversations', function (Blueprint $table) {
            $table->dropUnique('support_conversations_customer_id_unique');
        });

        Schema::table('support_conversations', function (Blueprint $table) {
            $table->foreignId('customer_id')->nullable()->change();
            $table->foreignId('seller_id')->nullable()->constrained('users')->after('customer_id');
            $table->foreign('customer_id')->references('id')->on('users')->cascadeOnDelete();
            $table->unique('customer_id');
            $table->unique('seller_id');
        });
    }

    public function down(): void
    {
        Schema::table('support_conversations', function (Blueprint $table) {
            $table->dropForeign(['seller_id']);
        });

        Schema::table('support_conversations', function (Blueprint $table) {
            $table->dropUnique('support_conversations_seller_id_unique');
        });

        Schema::table('support_conversations', function (Blueprint $table) {
            $table->dropForeign(['customer_id']);
        });

        Schema::table('support_conversations', function (Blueprint $table) {
            $table->dropUnique('support_conversations_customer_id_unique');
        });

        Schema::table('support_conversations', function (Blueprint $table) {
            $table->dropColumn('seller_id');
            $table->foreignId('customer_id')->nullable(false)->change();
            $table->foreign('customer_id')->references('id')->on('users')->cascadeOnDelete();
            $table->unique('customer_id');
        });
    }
};
