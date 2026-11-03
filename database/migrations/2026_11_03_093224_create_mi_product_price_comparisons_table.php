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
        Schema::create('mi_product_price_comparisons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('mi_products', 'product_id')->cascadeOnDelete();
            $table->string('store_name', 255);
            $table->string('product_name', 255);
            $table->string('photo_url', 2048)->nullable();
            $table->string('photo_path', 1024)->nullable();
            $table->decimal('price', 14, 2);
            $table->char('currency', 3);
            $table->string('product_link', 2048);
            $table->char('dedupe_hash', 64);
            $table->timestamps();
            $table->index(['product_id', 'created_at']);
            $table->unique(['product_id', 'dedupe_hash']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mi_product_price_comparisons');
    }
};
