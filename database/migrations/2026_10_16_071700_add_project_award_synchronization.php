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
        if (! Schema::hasTable('projects')) {
            throw new RuntimeException('The existing projects table is required before bidding synchronization can be enabled.');
        }

        if (! Schema::hasColumn('project_information', 'contract_amount')) {
            Schema::table('project_information', fn (Blueprint $table) => $table->decimal('contract_amount', 15, 2)->nullable());
        }
        if (! Schema::hasColumn('project_information', 'start_date')) {
            Schema::table('project_information', fn (Blueprint $table) => $table->date('start_date')->nullable());
        }
        if (! Schema::hasColumn('project_information', 'end_date')) {
            Schema::table('project_information', fn (Blueprint $table) => $table->date('end_date')->nullable());
        }

        if (! Schema::hasColumn('projects', 'project_information_id')) {
            Schema::table('projects', function (Blueprint $table): void {
                $table->unsignedBigInteger('project_information_id')->nullable()->after('project_id');
            });
        }

        $hasUniqueProjectBiddingLink = collect(Schema::getIndexes('projects'))
            ->contains(fn (array $index): bool => ($index['columns'] ?? []) === ['project_information_id'] && ($index['unique'] ?? false));
        if (! $hasUniqueProjectBiddingLink) {
            $hasDuplicateProjectBiddingLinks = DB::table('projects')
                ->whereNotNull('project_information_id')
                ->select('project_information_id')
                ->groupBy('project_information_id')
                ->havingRaw('COUNT(*) > 1')
                ->exists();
            if ($hasDuplicateProjectBiddingLinks) {
                throw new RuntimeException('Duplicate projects.project_information_id values exist; resolve them before adding the unique project-to-bidding relationship.');
            }

            Schema::table('projects', fn (Blueprint $table) => $table->unique('project_information_id', 'projects_bidding_link_unique'));
        }

        $hasProjectBiddingForeignKey = collect(Schema::getForeignKeys('projects'))->contains(
            fn (array $foreignKey): bool => ($foreignKey['columns'] ?? []) === ['project_information_id']
                && ($foreignKey['foreign_table'] ?? null) === 'project_information'
                && ($foreignKey['foreign_columns'] ?? []) === ['id']
        );
        if (! $hasProjectBiddingForeignKey) {
            if (DB::table('projects')->whereNotNull('project_information_id')->whereNotExists(function ($query): void {
                $query->selectRaw('1')->from('project_information')->whereColumn('project_information.id', 'projects.project_information_id');
            })->exists()) {
                throw new RuntimeException('Orphaned projects.project_information_id values exist; reconcile them before enabling award synchronization.');
            }

            Schema::table('projects', fn (Blueprint $table) => $table->foreign('project_information_id', 'projects_bidding_link_foreign')
                ->references('id')->on('project_information')->nullOnDelete());
        }

        if (! Schema::hasTable('project_award_events')) {
            Schema::create('project_award_events', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('project_information_id')->constrained('project_information')->restrictOnDelete();
                $table->unsignedInteger('project_id')->nullable()->index();
                $table->string('event_type', 30);
                $table->unsignedInteger('actor_user_id')->nullable();
                $table->date('event_date')->nullable();
                $table->string('status_before', 30)->nullable();
                $table->text('reason')->nullable();
                $table->json('project_snapshot')->nullable();
                $table->timestamps();
            });
        } else {
            foreach (['id', 'project_information_id', 'event_type', 'project_id', 'actor_user_id', 'event_date', 'status_before', 'reason', 'project_snapshot', 'created_at', 'updated_at'] as $column) {
                if (! Schema::hasColumn('project_award_events', $column)) {
                    throw new RuntimeException("The existing project_award_events table is missing {$column}; review its schema before continuing.");
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('project_award_events') && DB::table('project_award_events')->exists()) {
            throw new RuntimeException('Award audit events exist; preserve them before rolling back project synchronization.');
        }
        if (Schema::hasColumn('projects', 'project_information_id') && DB::table('projects')->whereNotNull('project_information_id')->exists()) {
            throw new RuntimeException('Linked project records exist; preserve or unlink them explicitly before rolling back project synchronization.');
        }

        Schema::dropIfExists('project_award_events');

        if (Schema::hasColumn('projects', 'project_information_id')) {
            Schema::table('projects', function (Blueprint $table): void {
                $foreignKey = collect(Schema::getForeignKeys('projects'))
                    ->first(fn (array $key): bool => ($key['columns'] ?? []) === ['project_information_id']);
                if ($foreignKey !== null) {
                    $table->dropForeign($foreignKey['name']);
                }
                $uniqueIndex = collect(Schema::getIndexes('projects'))
                    ->first(fn (array $index): bool => ($index['columns'] ?? []) === ['project_information_id'] && ($index['unique'] ?? false));
                if ($uniqueIndex !== null) {
                    $table->dropUnique($uniqueIndex['name']);
                }
                $table->dropColumn('project_information_id');
            });
        }

        $columnsToDrop = array_values(array_filter(['contract_amount', 'start_date', 'end_date'], fn (string $column): bool => Schema::hasColumn('project_information', $column)));
        if ($columnsToDrop !== []) {
            Schema::table('project_information', fn (Blueprint $table) => $table->dropColumn($columnsToDrop));
        }
    }
};
