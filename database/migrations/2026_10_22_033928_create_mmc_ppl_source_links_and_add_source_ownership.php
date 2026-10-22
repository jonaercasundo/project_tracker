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
        foreach (['projects', 'project_information', 'ppl_forms'] as $tableName) {
            if (! Schema::hasColumn($tableName, 'company_id')) {
                Schema::table($tableName, function (Blueprint $table): void {
                    $table->foreignId('company_id')->nullable()->index();
                });
            }
            $this->ensureForeignKey($tableName, 'company_id', 'companies', 'company_id', 'restrict');
        }

        if (! Schema::hasTable('mmc_ppl_source_links')) {
            Schema::create('mmc_ppl_source_links', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('company_id')->constrained('companies', 'company_id')->restrictOnDelete();
                $table->foreignId('ppl_form_id')->nullable()->unique()->constrained('ppl_forms')->nullOnDelete();
                $table->foreignId('project_information_id')->nullable()->constrained('project_information')->nullOnDelete();
                $table->integer('project_id')->nullable();
                $table->unsignedBigInteger('bidding_lot_id')->nullable();
                $table->integer('legacy_lot_id')->nullable();
                $table->string('match_status', 24)->default('needs_review');
                $table->string('match_origin', 24)->nullable();
                $table->json('matching_identifiers')->nullable();
                // Match the deployed legacy MariaDB users.user_id signed INT type.
                $table->integer('confirmed_by')->nullable();
                $table->timestamp('confirmed_at')->nullable();
                $table->timestamps();
                $table->index(['company_id', 'match_status'], 'mmc_ppl_source_links_company_status_index');
                $table->index(['project_information_id', 'project_id'], 'mmc_ppl_source_links_project_pair_index');
                $table->index(['bidding_lot_id', 'legacy_lot_id'], 'mmc_ppl_source_links_lot_pair_index');
            });
        }

        $this->ensureForeignKey('mmc_ppl_source_links', 'project_id', 'projects', 'project_id', 'set null');
        $this->ensureForeignKey('mmc_ppl_source_links', 'bidding_lot_id', 'lots', 'id', 'set null');
        $this->ensureForeignKey('mmc_ppl_source_links', 'legacy_lot_id', 'lot', 'lot_id', 'set null');
        $this->ensureForeignKey('mmc_ppl_source_links', 'confirmed_by', 'users', 'user_id', 'set null');

        if (! Schema::hasColumn('project_financial_plans', 'mmc_ppl_source_link_id')) {
            Schema::table('project_financial_plans', function (Blueprint $table): void {
                $table->foreignId('mmc_ppl_source_link_id')->nullable()->index();
            });
        }
        $this->ensureForeignKey('project_financial_plans', 'mmc_ppl_source_link_id', 'mmc_ppl_source_links', 'id', 'set null');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('mmc_ppl_source_links') && DB::table('mmc_ppl_source_links')->exists()) {
            throw new RuntimeException('MMC PPL source links exist; preserve the links before rolling back source ownership.');
        }
        if (Schema::hasColumn('project_financial_plans', 'mmc_ppl_source_link_id')
            && DB::table('project_financial_plans')->whereNotNull('mmc_ppl_source_link_id')->exists()) {
            throw new RuntimeException('Financial plans use MMC PPL source links; preserve the links before rolling back.');
        }

        if (Schema::hasColumn('project_financial_plans', 'mmc_ppl_source_link_id')) {
            Schema::table('project_financial_plans', function (Blueprint $table): void {
                $table->dropForeign('project_financial_plans_mmc_ppl_source_link_id_fk');
                $table->dropColumn('mmc_ppl_source_link_id');
            });
        }
        Schema::dropIfExists('mmc_ppl_source_links');
    }

    private function ensureForeignKey(string $table, string $column, string $parentTable, string $parentColumn, string $onDelete): void
    {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $column)) {
            return;
        }

        $exists = collect(Schema::getForeignKeys($table))->contains(
            fn (array $foreignKey): bool => ($foreignKey['columns'] ?? []) === [$column]
                && ($foreignKey['foreign_table'] ?? null) === $parentTable
                && ($foreignKey['foreign_columns'] ?? []) === [$parentColumn]
        );
        if ($exists) {
            return;
        }

        if (DB::table($table)->whereNotNull($column)->whereNotExists(function ($query) use ($table, $column, $parentTable, $parentColumn): void {
            $query->selectRaw('1')->from($parentTable)->whereColumn($parentTable.'.'.$parentColumn, $table.'.'.$column);
        })->exists()) {
            throw new RuntimeException("Cannot add {$table}.{$column} foreign key; orphaned source identifiers exist.");
        }

        $keyName = $table.'_'.$column.'_fk';
        Schema::table($table, function (Blueprint $blueprint) use ($column, $parentTable, $parentColumn, $onDelete, $keyName): void {
            $blueprint->foreign($column, $keyName)->references($parentColumn)->on($parentTable)->onDelete($onDelete);
        });
    }
};
