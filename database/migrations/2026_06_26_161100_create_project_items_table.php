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

            $table->foreignId('project_information_id')
                ->constrained('project_information')
                ->cascadeOnDelete();

            $table->integer('item_no');

            $table->text('item_description');

            $table->string('unit', 50);

            $table->decimal('quantity', 15, 2);

            $table->decimal('unit_cost', 15, 2);

            $table->decimal('total_amount', 15, 2);

            $table->string('brand')->nullable();

            $table->text('remarks')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_items');
    }
};