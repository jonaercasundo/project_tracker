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
        Schema::create('billing_spreadsheet_sync_audits', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('project_id')->index();
            $table->string('spreadsheet_id', 255);
            $table->string('sheet_tab', 255);
            $table->string('tracker_school_id', 255);
            $table->unsignedInteger('authenticated_user_id')->nullable()->index();
            $table->string('authenticated_user_name');
            $table->string('status', 40)->index();
            $table->json('mappings');
            $table->json('preview_rows');
            $table->json('written_cells');
            $table->json('validation_errors');
            $table->string('idempotency_key', 64)->unique();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('billing_spreadsheet_sync_audits');
    }
};
