<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mi_product_quotations', function (Blueprint $table) {

            $table->id('quotation_id');

            $table->foreignId('product_id')
                ->constrained('mi_products', 'product_id')
                ->cascadeOnDelete();

            $table->foreignId('factory_id')
                ->constrained('mi_factories', 'factory_id')
                ->cascadeOnDelete();

            $table->string('currency')->default('USD');

            $table->decimal('quoted_unit_cost',12,2);

            $table->date('quotation_date')->nullable();

            $table->boolean('is_lowest')->default(false);

            $table->text('remarks')->nullable();

            $table->timestamps();

            $table->unique([
                'product_id',
                'factory_id',
                'quotation_date'
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mi_product_quotations');
    }
};