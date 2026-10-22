<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('project_financial_plans', function (Blueprint $table): void {
            $table->softDeletes();
            $table->unsignedBigInteger('deleted_by')->nullable()->after('deleted_at');
            $table->index(['company_id', 'deleted_at'], 'pfinancial_company_deleted_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::table('project_financial_plans')->whereNotNull('deleted_at')->exists()) {
            throw new RuntimeException('Cannot remove financial-plan deletion history while soft-deleted plans exist.');
        }

        Schema::table('project_financial_plans', function (Blueprint $table): void {
            $table->dropIndex('pfinancial_company_deleted_index');
            $table->dropColumn(['deleted_at', 'deleted_by']);
        });
    }
};
