<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('school', function (Blueprint $table): void {
            $table->string('school_id')->primary();
            $table->unsignedBigInteger('project_id')->nullable();
            $table->foreign('project_id')->references('id')->on('project_information')->nullOnDelete();
            $table->string('project_no', 50)->nullable();
            $table->string('project')->nullable();
            $table->decimal('total_contract_price', 12, 2)->nullable();
            $table->string('contact_person')->nullable();
            $table->string('contact')->nullable();
            $table->string('municipality')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            foreach (['school_name', 'region', 'division', 'address'] as $field) {
                $table->string($field)->default('');
            }
        });
        Schema::create('deliveries', function (Blueprint $table): void {
            $table->integer('delivery_id')->primary();
        });
    }
};
