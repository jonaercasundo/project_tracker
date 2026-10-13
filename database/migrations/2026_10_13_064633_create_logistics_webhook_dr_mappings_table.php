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
        Schema::create('logistics_webhook_dr_mappings', function (Blueprint $table): void {
            $table->id();
            $table->string('integration_id', 64);
            $table->string('dr_reference', 100);
            $table->unsignedInteger('project_id');
            $table->string('school_id', 255);
            $table->string('approved_by', 191);
            $table->timestamp('approved_at');
            $table->timestamps();

            $table->unique(['integration_id', 'dr_reference'], 'logistics_webhook_dr_mapping_unique');
            $table->index(['project_id', 'school_id'], 'logistics_webhook_dr_mapping_scope_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('logistics_webhook_dr_mappings');
    }
};
