<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('lot_id')
                ->nullable()
                ->constrained('lots')
                ->cascadeOnDelete();

            $table->integer('item_no')->nullable();

            $table->text('item_description')->nullable();

            $table->string('unit', 50)->nullable();

            $table->decimal('quantity', 15, 2)->nullable();

            $table->decimal('unit_cost', 15, 2)->nullable();

            $table->decimal('total_amount', 15, 2)->nullable();

            $table->string('brand')->nullable();

            $table->text('remarks')->nullable();

            $table->timestamps();

            // Prevent duplicate item numbers within the same lot
            $table->unique(['lot_id', 'item_no']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_items');
    }
};