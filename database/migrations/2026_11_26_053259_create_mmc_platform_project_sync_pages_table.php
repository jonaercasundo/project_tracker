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
        Schema::create('mmc_platform_project_sync_pages', function (Blueprint $table) {
            $table->foreignId('sync_id')->constrained('mmc_platform_project_syncs')->cascadeOnDelete();
            $table->unsignedInteger('page');
            $table->json('records');
            $table->primary(['sync_id', 'page']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mmc_platform_project_sync_pages');
    }
};
