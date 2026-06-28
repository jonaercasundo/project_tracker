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
        Schema::create('lots', function (Blueprint $table) {
            $table->id();

            // Parent Project
            $table->foreignId('project_id')
                ->constrained('project_information')
                ->cascadeOnDelete();

            // Lot Information
            $table->string('lot_no', 50);

            // Delivery Location
            $table->string('country')->default('Philippines');
            $table->string('region')->nullable();
            $table->string('province')->nullable();
            $table->string('city_municipality')->nullable();
            $table->string('barangay')->nullable();
            $table->text('delivery_address')->nullable();

            // Special Conditions
            $table->text('notes_special_condition')->nullable();

            $table->timestamps();

            // Prevent duplicate lot numbers within the same project
            $table->unique(['project_id', 'lot_no']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lots');
    }
};