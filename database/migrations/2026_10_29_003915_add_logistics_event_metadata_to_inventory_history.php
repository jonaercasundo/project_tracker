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
        Schema::table('inventory_history', function (Blueprint $table): void {
            $table->string('integration_id', 64)->nullable();
            $table->string('webhook_event_id', 191)->nullable();
            $table->index(['integration_id', 'webhook_event_id'], 'inventory_history_logistics_event_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inventory_history', function (Blueprint $table): void {
            $table->dropIndex('inventory_history_logistics_event_index');
            $table->dropColumn(['integration_id', 'webhook_event_id']);
        });
    }
};
