<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_financial_plans', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained('companies', 'company_id')->restrictOnDelete();
            $table->unsignedInteger('project_id')->nullable();
            $table->foreign('project_id')->references('project_id')->on('projects')->nullOnDelete();
            $table->foreignId('project_information_id')->nullable()->constrained('project_information')->nullOnDelete();
            $table->foreignId('lot_id')->nullable()->constrained('lots')->nullOnDelete();
            $table->foreignId('ppl_form_id')->nullable()->constrained('ppl_forms')->nullOnDelete();
            $table->string('name');
            $table->string('status', 24)->default('draft');
            $table->foreignId('created_by')->constrained('users', 'user_id')->restrictOnDelete();
            $table->foreignId('updated_by')->constrained('users', 'user_id')->restrictOnDelete();
            $table->timestamps();

            $table->index(['company_id', 'status', 'updated_at'], 'project_financial_plans_company_status_updated_index');
            $table->index(['company_id', 'project_id'], 'project_financial_plans_company_project_index');
        });

        Schema::create('project_financial_plan_versions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('project_financial_plan_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('version_number');
            $table->string('status', 24)->default('draft');
            $table->string('change_summary')->nullable();
            $table->json('input_data');
            $table->json('calculation_results')->nullable();
            $table->json('assumptions_snapshot')->nullable();
            $table->json('factory_cost_snapshot')->nullable();
            $table->foreignId('created_by')->constrained('users', 'user_id')->restrictOnDelete();
            $table->timestamps();

            $table->unique(['project_financial_plan_id', 'version_number'], 'project_financial_plan_versions_number_unique');
            $table->index(['project_financial_plan_id', 'created_at'], 'project_financial_plan_versions_history_index');
        });

        Schema::create('project_financial_plan_cost_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('project_financial_plan_version_id')->constrained()->cascadeOnDelete();
            $table->string('category', 80);
            $table->string('label');
            $table->decimal('amount', 15, 2)->default(0);
            $table->string('calculation_basis', 40)->nullable();
            $table->decimal('rate', 12, 6)->nullable();
            $table->string('source_type', 80)->nullable();
            $table->string('source_reference')->nullable();
            $table->json('source_snapshot')->nullable();
            $table->timestamps();

            $table->index(['project_financial_plan_version_id', 'category'], 'project_financial_plan_cost_category_index');
        });
    }

    public function down(): void
    {
        foreach (['project_financial_plan_cost_items', 'project_financial_plan_versions', 'project_financial_plans'] as $table) {
            if (Schema::hasTable($table) && DB::table($table)->exists()) {
                throw new RuntimeException("Cannot roll back populated {$table}; preserve financial plan history first.");
            }
        }

        Schema::dropIfExists('project_financial_plan_cost_items');
        Schema::dropIfExists('project_financial_plan_versions');
        Schema::dropIfExists('project_financial_plans');
    }
};
