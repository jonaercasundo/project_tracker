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
        Schema::create('logistics_webhook_events', function (Blueprint $table): void {
            $table->id();
            $table->string('integration_id', 64);
            $table->string('external_event_id', 191);
            $table->string('event_type', 64);
            $table->string('dr_reference', 100);
            $table->string('external_allocation_reference', 191);
            $table->json('payload');
            $table->char('payload_hash', 64);
            $table->dateTime('event_occurred_at');
            $table->timestamp('received_at')->useCurrent();
            $table->string('processing_status', 24)->default('received');
            $table->unsignedInteger('processing_attempts')->default(0);
            $table->text('error_details')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamp('queued_at')->nullable();
            $table->timestamps();

            $table->unique(
                ['integration_id', 'external_event_id'],
                'logistics_webhook_event_unique'
            );
            $table->index(
                ['processing_status', 'queued_at'],
                'logistics_webhook_recovery_index'
            );
            $table->index('external_allocation_reference', 'logistics_webhook_allocation_index');
            $table->index('dr_reference', 'logistics_webhook_dr_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('logistics_webhook_events');
    }
};
