<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mi_cost_assumptions', function (Blueprint $table) {

            $table->id();

            $table->string('assumption_name');

            $table->decimal('percentage',8,4);

            $table->boolean('active')->default(true);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mi_cost_assumptions');
    }
};