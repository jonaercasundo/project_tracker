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
        foreach (['companies', 'users', 'projects', 'project_information', 'lot', 'lots'] as $tableName) {
            if (! Schema::hasTable($tableName)) {
                throw new RuntimeException("The {$tableName} source table is required before lifecycle monitoring can be enabled.");
            }
        }

        Schema::create('project_lifecycle_tracks', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained('companies', 'company_id')->restrictOnDelete();
            $table->integer('project_id')->nullable();
            $table->foreign('project_id')->references('project_id')->on('projects')->restrictOnDelete();
            $table->foreignId('project_information_id')->nullable()->constrained('project_information')->restrictOnDelete();
            $table->integer('legacy_lot_id')->nullable();
            $table->foreign('legacy_lot_id')->references('lot_id')->on('lot')->restrictOnDelete();
            $table->foreignId('bidding_lot_id')->nullable()->constrained('lots')->restrictOnDelete();
            $table->string('scope_key', 120)->unique();
            $table->string('current_stage', 40);
            $table->string('outcome', 24)->nullable();
            $table->integer('owner_user_id')->nullable();
            $table->foreign('owner_user_id')->references('user_id')->on('users')->nullOnDelete();
            $table->string('next_action', 255)->nullable();
            $table->timestamp('next_action_due_at')->nullable();
            $table->integer('created_by');
            $table->foreign('created_by')->references('user_id')->on('users')->restrictOnDelete();
            $table->timestamps();
            $table->index(['company_id', 'current_stage']);
            $table->index(['company_id', 'outcome']);
            $table->index(['company_id', 'next_action_due_at']);
        });

        Schema::create('project_lifecycle_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('project_lifecycle_track_id')->constrained('project_lifecycle_tracks')->restrictOnDelete();
            $table->integer('actor_user_id')->nullable();
            $table->foreign('actor_user_id')->references('user_id')->on('users')->nullOnDelete();
            $table->string('event_type', 32);
            $table->string('from_stage', 40)->nullable();
            $table->string('to_stage', 40)->nullable();
            $table->string('outcome', 24)->nullable();
            $table->text('note')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['project_lifecycle_track_id', 'created_at'], 'lifecycle_events_track_date_index');
        });

        Schema::create('project_lifecycle_tasks', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('project_lifecycle_track_id')->constrained('project_lifecycle_tracks')->restrictOnDelete();
            $table->string('kind', 24);
            $table->string('title', 255);
            $table->string('status', 16)->default('open');
            $table->timestamp('due_at')->nullable();
            $table->integer('owner_user_id')->nullable();
            $table->foreign('owner_user_id')->references('user_id')->on('users')->nullOnDelete();
            $table->integer('created_by');
            $table->foreign('created_by')->references('user_id')->on('users')->restrictOnDelete();
            $table->timestamps();
            $table->index(['project_lifecycle_track_id', 'kind', 'status'], 'lifecycle_tasks_track_kind_status_index');
            $table->index(['status', 'due_at'], 'lifecycle_tasks_status_due_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('project_lifecycle_tracks') && DB::table('project_lifecycle_tracks')->exists()) {
            throw new RuntimeException('Lifecycle history exists; archive it before rolling back this migration.');
        }

        Schema::dropIfExists('project_lifecycle_tasks');
        Schema::dropIfExists('project_lifecycle_events');
        Schema::dropIfExists('project_lifecycle_tracks');
    }
};
