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
        Schema::create('keystages', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('delivery_address_id')->nullable();
            $table->unsignedBigInteger('lot_id')->nullable();
            $table->unsignedBigInteger('project_id')->nullable();
            $table->unsignedInteger('package_no')->nullable();

            $table->unsignedInteger('total_jobs')->default(0);
            $table->unsignedInteger('pending_jobs')->default(0);
            $table->unsignedInteger('failed_jobs')->default(0);

            $table->longText('failed_jobs_ids')->nullable();
            $table->mediumText('options')->nullable();

            $table->timestamp('cancelled_at')->nullable();
            $table->timestamp('finished_at')->nullable();

            $table->rememberToken();
            $table->timestamps();

            $table->foreign('delivery_address_id')
                ->references('id')
                ->on('delivery_address')
                ->nullOnDelete();

            $table->foreign('lot_id')
                ->references('id')
                ->on('lots')
                ->nullOnDelete();

            $table->foreign('project_id')
                ->references('id')
                ->on('project_information')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('keystages');
    }
};