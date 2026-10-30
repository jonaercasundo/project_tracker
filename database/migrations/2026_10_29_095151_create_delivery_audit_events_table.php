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
        Schema::create('delivery_audit_events', function (Blueprint $table): void {
            $table->id();
            $table->uuid('event_uuid')->unique();
            $table->string('source_key', 191)->unique();
            $table->dateTime('event_at')->nullable()->index();
            $table->unsignedInteger('project_id')->nullable()->index();
            $table->unsignedInteger('lot_id')->nullable()->index();
            $table->string('school_id', 64)->nullable()->index();
            $table->string('school_name')->nullable();
            $table->string('package_code')->nullable();
            $table->string('dr_no', 191)->nullable()->index();
            $table->string('event_type', 64)->index();
            $table->string('previous_status')->nullable();
            $table->string('new_status')->nullable();
            $table->decimal('quantity', 14, 3)->nullable();
            $table->unsignedInteger('warehouse_id')->nullable();
            $table->string('warehouse_name')->nullable();
            $table->unsignedInteger('performed_by')->nullable();
            $table->string('actor_name')->nullable();
            $table->string('source_module', 100);
            $table->string('integration_id', 64)->nullable()->index();
            $table->string('related_type', 100)->nullable();
            $table->string('related_id', 191)->nullable();
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->index(['project_id', 'event_at']);
            $table->index(['event_type', 'event_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('delivery_audit_events');
    }
};
