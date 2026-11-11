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
        Schema::create('mmc_platform_project_syncs', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->string('program_id', 255);
            $table->string('program_name', 255)->nullable();
            $table->json('program_info')->nullable();
            $table->json('records');
            $table->json('entity_counts');
            $table->unsignedInteger('record_count')->default(0);
            $table->timestamp('synced_at');
            $table->timestamps();

            $table->unique(['company_id', 'program_id'], 'mmc_platform_project_syncs_company_program_unique');
            $table->index(['company_id', 'synced_at'], 'mmc_platform_project_syncs_company_synced_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mmc_platform_project_syncs');
    }
};
