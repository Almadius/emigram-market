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
        Schema::create('price_snapshots', function (Blueprint $table) {
            $table->id();
            $table->string('shop_domain');
            $table->string('product_url', 2048);
            $table->decimal('price', 10, 2);
            $table->string('currency', 3)->default('EUR');
            $table->string('source'); // extension, webview, crawler
            $table->timestamp('parsed_at');
            $table->timestamps();

            $table->index(['shop_domain', 'product_url']);
            $table->index('parsed_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('price_snapshots');
    }
};
