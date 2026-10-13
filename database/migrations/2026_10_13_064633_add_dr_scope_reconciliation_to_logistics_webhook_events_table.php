<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('logistics_webhook_events', function (Blueprint $table): void {
            $table->string('external_allocation_reference', 191)->nullable()->change();
            $table->unsignedInteger('resolved_project_id')->nullable()->after('dr_reference');
            $table->string('resolved_school_id', 255)->nullable()->after('resolved_project_id');
            $table->string('logistics_status', 32)->nullable()->after('event_type');
            $table->unsignedBigInteger('event_sequence')->nullable()->after('event_occurred_at');
            $table->string('resolution_status', 24)->default('pending')->after('processing_status');
            $table->string('reconciliation_status', 32)->default('pending')->after('resolution_status');
            $table->text('reconciliation_details')->nullable()->after('reconciliation_status');
            $table->index(
                ['integration_id', 'resolved_project_id', 'resolved_school_id', 'dr_reference', 'event_sequence'],
                'logistics_webhook_sequence_index'
            );
        });
    }

    public function down(): void
    {
        Schema::table('logistics_webhook_events', function (Blueprint $table): void {
            $table->dropIndex('logistics_webhook_sequence_index');
            $table->dropColumn([
                'resolved_project_id',
                'resolved_school_id',
                'logistics_status',
                'event_sequence',
                'resolution_status',
                'reconciliation_status',
                'reconciliation_details',
            ]);
        });
    }
};
