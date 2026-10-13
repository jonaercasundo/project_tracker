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
        Schema::create('logistics_dr_tracking', function (Blueprint $table): void {
            $table->id();
            $table->string('integration_id', 64);
            $table->unsignedInteger('project_id');
            $table->string('school_id', 255);
            $table->string('dr_reference', 100);
            $table->char('school_scope_hash', 64);
            $table->char('dr_reference_hash', 64);
            $table->string('logistics_status', 32);
            $table->string('latest_event_id', 191);
            $table->foreignId('latest_webhook_event_row_id')
                ->constrained('logistics_webhook_events')
                ->restrictOnDelete();
            $table->dateTime('event_occurred_at');
            $table->unsignedBigInteger('latest_sequence');
            $table->dateTime('last_synchronized_at');
            $table->string('reconciliation_status', 32)->default('synchronized');
            $table->timestamps();

            $table->unique(
                ['integration_id', 'project_id', 'school_scope_hash', 'dr_reference_hash'],
                'logistics_dr_tracking_scope_unique'
            );
            $table->index(['project_id', 'school_scope_hash'], 'logistics_dr_tracking_school_index');
            $table->index(['project_id', 'dr_reference_hash'], 'logistics_dr_tracking_dr_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('logistics_dr_tracking');
    }
};
