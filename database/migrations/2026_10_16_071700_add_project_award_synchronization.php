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

        Schema::table('project_information', function (Blueprint $table): void {
            $table->decimal('contract_amount', 15, 2)->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
        });

        if (! Schema::hasColumn('projects', 'project_information_id')) {
            Schema::table('projects', function (Blueprint $table): void {
                $table->unsignedBigInteger('project_information_id')->nullable()->after('project_id');
                $table->unique('project_information_id', 'projects_bidding_link_unique');
                $table->foreign('project_information_id', 'projects_bidding_link_foreign')
                    ->references('id')
                    ->on('project_information')
                    ->nullOnDelete();
            });
        }

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
                $table->dropForeign('projects_bidding_link_foreign');
                $table->dropUnique('projects_bidding_link_unique');
                $table->dropColumn('project_information_id');
            });
        }

        Schema::table('project_information', function (Blueprint $table): void {
            $table->dropColumn(['contract_amount', 'start_date', 'end_date']);
        });
    }
};
