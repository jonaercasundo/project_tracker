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
        Schema::create('mi_costing_analyses', function (Blueprint $table) {
            $table->id('analysis_id');

            $table->foreignId('product_id')
                ->constrained('mi_products','product_id');

            $table->foreignId('factory_id')
                ->constrained('mi_factories','factory_id');

            $table->decimal('selling_price',12,2);

            $table->integer('actual_qty');

            $table->decimal('sales_amount',12,2);

            $table->decimal('factory_cost',12,2);

            $table->decimal('gross_profit',12,2);

            $table->decimal('gross_margin',8,2);

            $table->decimal('total_variable_cost',12,2);

            $table->decimal('operating_profit',12,2);

            $table->decimal('net_income',12,2);

            $table->decimal('net_margin',8,2);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mi_costing_analyses');
    }
};
