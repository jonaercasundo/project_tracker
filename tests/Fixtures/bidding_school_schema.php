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
            foreach (['school_name', 'region', 'division', 'municipality', 'address'] as $field) {
                $table->string($field)->default('');
            }
        });
        Schema::create('deliveries', function (Blueprint $table): void {
            $table->integer('delivery_id')->primary();
        });
    }
};
