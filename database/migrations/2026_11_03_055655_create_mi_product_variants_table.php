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
        Schema::create('mi_product_variants', function (Blueprint $table): void {
            $table->id('variant_id');
            $table->foreignId('product_id')->constrained('mi_products', 'product_id')->cascadeOnDelete();
            $table->unsignedSmallInteger('sequence');
            $table->string('material', 255);
            $table->string('color', 255)->nullable();
            $table->decimal('product_length', 10, 2)->nullable();
            $table->decimal('product_width', 10, 2)->nullable();
            $table->decimal('product_height', 10, 2)->nullable();
            $table->decimal('product_depth', 10, 2)->nullable();
            $table->decimal('purchase_price', 12, 2)->nullable();
            $table->decimal('selling_price', 12, 2)->nullable();
            $table->string('sku')->unique();
            $table->timestamps();
            $table->unique(['product_id', 'sequence']);
            $table->index(['material', 'color']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mi_product_variants');
    }
};
