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
        Schema::create('mi_product_variant_inventory', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('variant_id')->constrained('mi_product_variants', 'variant_id')->cascadeOnDelete();
            $table->integer('warehouse_id');
            $table->unsignedInteger('quantity')->default(0);
            $table->timestamps();
            $table->foreign('warehouse_id')->references('warehouse_id')->on('warehouse')->cascadeOnDelete();
            $table->unique(['variant_id', 'warehouse_id']);
        });

        Schema::create('mi_product_variant_inventory_movements', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('variant_id')->constrained('mi_product_variants', 'variant_id')->cascadeOnDelete();
            $table->integer('warehouse_id');
            $table->string('movement_type', 20);
            $table->integer('quantity_change');
            $table->unsignedInteger('quantity_after');
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('remarks', 500)->nullable();
            $table->timestamps();
            $table->foreign('warehouse_id')->references('warehouse_id')->on('warehouse')->cascadeOnDelete();
            $table->index(['variant_id', 'warehouse_id', 'created_at'], 'mi_variant_movement_lookup_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mi_product_variant_inventory_movements');
        Schema::dropIfExists('mi_product_variant_inventory');
    }
};
