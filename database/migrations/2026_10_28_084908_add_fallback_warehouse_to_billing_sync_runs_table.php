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
        if (! Schema::hasColumn('billing_sync_runs', 'fallback_warehouse_id')) {
            Schema::table('billing_sync_runs', function (Blueprint $table): void {
                $table->unsignedInteger('fallback_warehouse_id')->nullable()->after('project_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('billing_sync_runs', 'fallback_warehouse_id')) {
            Schema::table('billing_sync_runs', function (Blueprint $table): void {
                $table->dropColumn('fallback_warehouse_id');
            });
        }
    }
};
