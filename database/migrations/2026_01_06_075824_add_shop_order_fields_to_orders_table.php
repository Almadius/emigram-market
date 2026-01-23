<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('shop_order_id')->nullable()->after('shop_domain');
            $table->json('metadata')->nullable()->after('shop_order_id');
            
            $table->index('shop_order_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['shop_order_id']);
            $table->dropColumn(['shop_order_id', 'metadata']);
        });
    }
};
