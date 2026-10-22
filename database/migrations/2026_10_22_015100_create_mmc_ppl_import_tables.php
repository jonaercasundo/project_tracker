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
        Schema::create('mmc_ppl_import_batches', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained('companies', 'company_id')->restrictOnDelete();
            $table->unsignedBigInteger('created_by');
            $table->foreign('created_by')->references('user_id')->on('users')->restrictOnDelete();
            $table->string('original_file_name', 255);
            $table->char('file_sha256', 64);
            $table->string('storage_path', 500);
            $table->unsignedInteger('file_size_bytes');
            $table->string('status', 24)->default('previewed');
            $table->unsignedInteger('attempt_count')->default(0);
            $table->json('summary')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('previewed_at')->nullable();
            $table->timestamp('imported_at')->nullable();
            $table->timestamps();
            $table->unique(['company_id', 'file_sha256'], 'mmc_ppl_import_batches_company_hash_unique');
            $table->index(['company_id', 'status', 'created_at'], 'mmc_ppl_import_batches_company_status_index');
        });

        Schema::create('mmc_ppl_import_rows', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('mmc_ppl_import_batch_id')->constrained('mmc_ppl_import_batches')->cascadeOnDelete();
            $table->string('source_sheet', 100);
            $table->unsignedInteger('source_row');
            $table->string('project_code', 255)->nullable();
            $table->string('lot_number', 100)->nullable();
            $table->char('source_fingerprint', 64);
            $table->string('status', 24)->default('blocked');
            $table->json('source_data');
            $table->json('normalized_data')->nullable();
            $table->json('errors')->nullable();
            $table->json('warnings')->nullable();
            $table->foreignId('financial_plan_id')->nullable()->constrained('project_financial_plans')->nullOnDelete();
            $table->timestamps();
            $table->unique(['mmc_ppl_import_batch_id', 'source_sheet', 'source_row'], 'mmc_ppl_import_rows_source_location_unique');
            $table->index(['mmc_ppl_import_batch_id', 'status'], 'mmc_ppl_import_rows_batch_status_index');
            $table->index('source_fingerprint', 'mmc_ppl_import_rows_source_fingerprint_index');
        });

        Schema::create('mmc_ppl_import_source_mappings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained('companies', 'company_id')->restrictOnDelete();
            $table->char('source_fingerprint', 64);
            $table->foreignId('mmc_ppl_import_batch_id')->constrained('mmc_ppl_import_batches')->restrictOnDelete();
            $table->foreignId('mmc_ppl_import_row_id')->constrained('mmc_ppl_import_rows')->restrictOnDelete();
            $table->foreignId('financial_plan_id')->constrained('project_financial_plans')->restrictOnDelete();
            $table->timestamps();
            $table->unique(['company_id', 'source_fingerprint'], 'mmc_ppl_import_mapping_company_source_unique');
            $table->unique('financial_plan_id', 'mmc_ppl_import_mapping_plan_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        foreach (['mmc_ppl_import_source_mappings', 'mmc_ppl_import_rows', 'mmc_ppl_import_batches'] as $table) {
            if (Schema::hasTable($table) && DB::table($table)->exists()) {
                throw new RuntimeException("Cannot roll back populated {$table}; preserve the workbook import audit history first.");
            }
        }

        Schema::dropIfExists('mmc_ppl_import_source_mappings');
        Schema::dropIfExists('mmc_ppl_import_rows');
        Schema::dropIfExists('mmc_ppl_import_batches');
    }
};
