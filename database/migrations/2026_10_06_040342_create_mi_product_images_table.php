<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * The mi_product_images table may already exist in legacy/production
     * databases and may contain existing product image records.
     *
     * If it already exists, preserve the table and its data.
     */
    public function up(): void
    {
        if (Schema::hasTable('mi_product_images')) {
            return;
        }

        Schema::create('mi_product_images', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('product_id');

            $table->foreign('product_id')
                ->references('product_id')
                ->on('mi_products')
                ->cascadeOnDelete();

            $table->enum('image_type', ['upload', 'url'])
                ->default('url');

            $table->string('image_path')->nullable();

            $table->text('image_url')->nullable();

            $table->boolean('is_primary')
                ->default(false);

            $table->unsignedInteger('sort_order')
                ->default(0);

            $table->timestamps();

            $table->index(
                ['product_id', 'sort_order'],
                'mi_product_images_product_sort_index'
            );
        });
    }

    /**
     * Reverse the migrations.
     *
     * Intentionally do not automatically drop this table because existing
     * production installations may contain legacy product image records.
     */
    public function down(): void
    {
        // Preserve existing mi_product_images records.
    }
};