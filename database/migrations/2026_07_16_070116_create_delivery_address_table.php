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
        Schema::create('delivery_address', function (Blueprint $table) {

            $table->id();

            $table->unsignedBigInteger('lot_id');
            $table->unsignedBigInteger('project_id');

            $table->string('delivery_address_region')->nullable();
            $table->string('delivery_address_province')->nullable();
            $table->string('delivery_address_municipality')->nullable();
            $table->string('delivery_address_barangay')->nullable();
            $table->string('delivery_address_otherInformation')->nullable();

            $table->rememberToken();
            $table->timestamps();

            // Foreign Keys
            $table->foreign('lot_id')
                ->references('id')
                ->on('lots')
                ->cascadeOnDelete();

            $table->foreign('project_id')
                ->references('id')
                ->on('project_information')
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('delivery_address');
    }
};