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
        Schema::create('mi_product_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('mi_products', 'product_id')->cascadeOnDelete();
            $table->enum('image_type', ['upload', 'url']);
            $table->string('image_path')->nullable();
            $table->text('image_url')->nullable();
            $table->boolean('is_primary')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->index(['product_id', 'sort_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mi_product_images');
    }
};
