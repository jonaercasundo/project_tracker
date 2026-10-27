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
        Schema::create('billing_sheet_syncs', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('delivery_billing_history_id');
            $table->unsignedInteger('delivery_id');
            $table->unsignedInteger('package_status_id')->nullable();
            $table->unsignedInteger('package_id')->nullable();
            $table->unsignedInteger('lot_id')->nullable();
            $table->string('lot_number', 20)->nullable();
            $table->string('bir_dr', 255)->nullable();
            $table->string('package_code', 255)->nullable();
            $table->string('spreadsheet_id', 255);
            $table->string('sheet_tab', 255);
            $table->string('target_cell', 12)->nullable();
            $table->timestamp('transition_at');
            $table->date('transition_date');
            $table->string('idempotency_key', 64)->unique();
            $table->string('outcome', 40)->default('queued')->index();
            $table->unsignedSmallInteger('attempts')->default(0);
            $table->text('error_details')->nullable();
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();

            $table->index(['delivery_id', 'package_status_id']);
            $table->index(['outcome', 'updated_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('billing_sheet_syncs');
    }
};
