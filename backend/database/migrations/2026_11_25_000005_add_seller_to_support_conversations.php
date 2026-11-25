<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('support_conversations', function (Blueprint $table) {
            $table->dropUnique('support_conversations_customer_id_unique');
            $table->foreignId('customer_id')->nullable()->change();
            $table->foreignId('seller_id')->nullable()->constrained('users')->after('customer_id');
            $table->unique('customer_id');
            $table->unique('seller_id');
        });
    }

    public function down(): void
    {
        Schema::table('support_conversations', function (Blueprint $table) {
            $table->dropUnique('support_conversations_customer_id_unique');
            $table->dropUnique('support_conversations_seller_id_unique');
            $table->dropForeign(['seller_id']);
            $table->dropColumn('seller_id');
            $table->foreignId('customer_id')->nullable(false)->change();
            $table->unique('customer_id');
        });
    }
};
