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
        Schema::create('items', function (Blueprint $table) {

            // Primary Key
            $table->id();

            // Business Item ID
            $table->string('item_id')->unique();

            $table->string('code_prefix');
            $table->string('item_name');
            $table->text('description')->nullable();

            $table->unsignedBigInteger('project_id')->nullable();

            $table->string('unit')->nullable();

            $table->decimal('price', 15, 2)->default(0);
            $table->decimal('supplier_price', 15, 2)->default(0);

            $table->boolean('active')->default(true);

            $table->timestamps();

            // Optional foreign key if projects.id is an unsignedBigInteger
            // $table->foreign('project_id')->references('id')->on('projects')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('items');
    }
};