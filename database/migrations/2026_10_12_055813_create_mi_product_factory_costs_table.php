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
        Schema::create('mi_product_factory_costs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('product_id')
                ->constrained('mi_products', 'product_id')
                ->cascadeOnDelete();
            $table->string('factory_name');
            $table->decimal('factory_cost', 15, 2)->unsigned();
            $table->enum('currency', ['PHP'])->default('PHP');
            $table->date('effective_date')->nullable();
            $table->text('remarks')->nullable();
            $table->boolean('is_primary')->default(false);
            $table->unsignedBigInteger('primary_product_id')
                ->nullable()
                ->virtualAs('CASE WHEN is_primary = 1 THEN product_id ELSE NULL END');
            $table->timestamps();

            $table->index(['product_id', 'id'], 'mi_factory_costs_product_order_index');
            $table->unique('primary_product_id', 'mi_factory_costs_one_primary_per_product');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mi_product_factory_costs');
    }
};
