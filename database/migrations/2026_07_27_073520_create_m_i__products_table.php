<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mi_products', function (Blueprint $table) {
            $table->id('product_id');

            // Classification
            $table->string('main_category');
            $table->string('sub_category')->nullable();
            $table->string('collection')->nullable();
            $table->string('sample_type')->nullable();

            // References
            $table->string('photo_reference')->nullable();
            $table->string('file_id')->nullable();
            $table->text('gdrive_link')->nullable();

            // Product Information
            $table->string('item_name');
            $table->text('description')->nullable();
            $table->text('material')->nullable();
            $table->string('dimension')->nullable();

            // Sample Information
            $table->integer('qty')->default(1);

            $table->enum('sample_dev_status', [
                'For Development',
                'On-going',
                'Completed',
                'Cancelled'
            ])->default('For Development');

            $table->boolean('present_in_showroom')->default(false);
            $table->boolean('borrowed')->default(false);

            $table->text('remarks')->nullable();

            $table->timestamps();

            $table->index('item_name');
            $table->index('main_category');
            $table->index('sub_category');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mi_products');
    }
};