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

            $table->foreignId('product_id')
                ->constrained('mi_products', 'product_id')
                ->cascadeOnDelete();

            // upload or url
            $table->enum('image_type', [
                'upload',
                'url'
            ]);

            // uploaded file path
            $table->string('image_path')
                ->nullable();

            // external image link
            $table->text('image_url')
                ->nullable();

            $table->boolean('is_primary')
                ->default(false);

            $table->integer('sort_order')
                ->default(0);

            $table->timestamps();

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
