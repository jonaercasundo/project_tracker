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
        if (! Schema::hasTable('project_financial_plans')) {
            throw new RuntimeException('The project_financial_plans table must exist before revenue year can be added.');
        }
        if (Schema::hasColumn('project_financial_plans', 'revenue_year')) {
            throw new RuntimeException('project_financial_plans.revenue_year already exists; review the existing column before applying this migration.');
        }

        Schema::table('project_financial_plans', function (Blueprint $table): void {
            $table->unsignedSmallInteger('revenue_year')->nullable()->after('ppl_form_id');
            $table->index(['company_id', 'revenue_year'], 'project_financial_plans_company_revenue_year_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasColumn('project_financial_plans', 'revenue_year')) {
            return;
        }
        if (DB::table('project_financial_plans')->whereNotNull('revenue_year')->exists()) {
            throw new RuntimeException('Cannot remove populated revenue_year values; preserve the financial-plan monitoring data first.');
        }

        Schema::table('project_financial_plans', function (Blueprint $table): void {
            $table->dropIndex('project_financial_plans_company_revenue_year_index');
            $table->dropColumn('revenue_year');
        });
    }
};
