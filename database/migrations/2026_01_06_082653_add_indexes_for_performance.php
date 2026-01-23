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
        // Индексы для таблицы orders
        Schema::table('orders', function (Blueprint $table) {
            $table->index('status');
            $table->index('created_at');
            $table->index(['user_id', 'status']);
            $table->index(['shop_domain', 'status']);
        });

        // Индексы для таблицы order_items
        Schema::table('order_items', function (Blueprint $table) {
            $table->index('product_id');
        });

        // Индексы для таблицы carts
        Schema::table('carts', function (Blueprint $table) {
            $table->index('updated_at');
        });

        // Индексы для таблицы cart_items
        if (Schema::hasTable('cart_items')) {
            Schema::table('cart_items', function (Blueprint $table) {
                $table->index(['cart_id', 'shop_domain']);
                $table->index('product_id');
            });
        }

        // Индексы для таблицы price_snapshots (оптимизация поиска)
        Schema::table('price_snapshots', function (Blueprint $table) {
            $table->index(['shop_domain', 'product_url', 'source']);
            $table->index(['shop_domain', 'parsed_at']);
        });

        // Индексы для таблицы products
        if (Schema::hasTable('products')) {
            Schema::table('products', function (Blueprint $table) {
                $table->index('is_active');
                $table->index(['shop_id', 'is_active']);
                $table->index('created_at');
            });
        }

        // Индексы для таблицы shops
        if (Schema::hasTable('shops')) {
            Schema::table('shops', function (Blueprint $table) {
                $table->index('is_active');
                $table->index('domain');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['created_at']);
            $table->dropIndex(['user_id', 'status']);
            $table->dropIndex(['shop_domain', 'status']);
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->dropIndex(['product_id']);
        });

        Schema::table('carts', function (Blueprint $table) {
            $table->dropIndex(['updated_at']);
        });

        if (Schema::hasTable('cart_items')) {
            Schema::table('cart_items', function (Blueprint $table) {
                $table->dropIndex(['cart_id', 'shop_domain']);
                $table->dropIndex(['product_id']);
            });
        }

        Schema::table('price_snapshots', function (Blueprint $table) {
            $table->dropIndex(['shop_domain', 'product_url', 'source']);
            $table->dropIndex(['shop_domain', 'parsed_at']);
        });

        if (Schema::hasTable('products')) {
            Schema::table('products', function (Blueprint $table) {
                $table->dropIndex(['is_active']);
                $table->dropIndex(['shop_id', 'is_active']);
                $table->dropIndex(['created_at']);
            });
        }

        if (Schema::hasTable('shops')) {
            Schema::table('shops', function (Blueprint $table) {
                $table->dropIndex(['is_active']);
                $table->dropIndex(['domain']);
            });
        }
    }
};
