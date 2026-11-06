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
        Schema::create('logistics_integration_regions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('integration_id')->constrained('logistics_integrations')->cascadeOnDelete();
            $table->unsignedInteger('project_id');
            $table->string('region', 255);
            $table->timestamps();
            $table->unique(['integration_id', 'project_id', 'region'], 'logistics_integration_region_unique');
            $table->index(['project_id', 'region'], 'logistics_integration_region_scope_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('logistics_integration_regions');
    }
};
