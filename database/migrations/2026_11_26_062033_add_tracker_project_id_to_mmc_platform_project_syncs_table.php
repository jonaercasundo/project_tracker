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
        Schema::table('mmc_platform_project_syncs', function (Blueprint $table): void {
            $table->unsignedInteger('tracker_project_id')->nullable()->after('program_id');
            $table->foreign('tracker_project_id', 'mmc_platform_syncs_tracker_project_foreign')
                ->references('project_id')
                ->on('projects')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mmc_platform_project_syncs', function (Blueprint $table): void {
            $table->dropForeign('mmc_platform_syncs_tracker_project_foreign');
            $table->dropColumn('tracker_project_id');
        });
    }
};
