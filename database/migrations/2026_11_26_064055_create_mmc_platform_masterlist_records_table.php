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
        Schema::create('mmc_platform_masterlist_records', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedInteger('tracker_project_id');
            $table->string('source_program_id', 255);
            $table->string('delivery_id', 255);
            $table->string('lot_id', 255)->nullable();
            $table->string('lot_no', 255)->nullable();
            $table->text('contract_no')->nullable();
            $table->string('dr_no', 255)->nullable();
            $table->string('school_id', 255)->nullable();
            $table->text('school_name')->nullable();
            $table->string('region', 255)->nullable();
            $table->string('division', 255)->nullable();
            $table->string('municipality', 255)->nullable();
            $table->text('address')->nullable();
            $table->text('contact_person')->nullable();
            $table->string('contact_phone', 255)->nullable();
            $table->text('grade_levels')->nullable();
            $table->text('value')->nullable();
            $table->string('status', 255)->nullable();
            $table->string('sap_dr_no', 255)->nullable();
            $table->string('sap_encoded_at', 255)->nullable();
            $table->string('dr_printed_at', 255)->nullable();
            $table->string('iar_no', 255)->nullable();
            $table->string('itr_no', 255)->nullable();
            $table->string('ar_no', 255)->nullable();
            $table->text('received_by')->nullable();
            $table->text('receiver_designation')->nullable();
            $table->text('inspected_by')->nullable();
            $table->text('inspector_designation')->nullable();
            $table->string('date_received', 255)->nullable();
            $table->string('returned_to_office_at', 255)->nullable();
            $table->string('delivered_at', 255)->nullable();
            $table->string('pouch_completed_at', 255)->nullable();
            $table->string('billing_no', 255)->nullable();
            $table->string('sequence_no', 255)->nullable();
            $table->string('source_updated_at', 255)->nullable();

            $table->foreign('tracker_project_id', 'mmc_masterlist_records_tracker_project_foreign')
                ->references('project_id')
                ->on('projects')
                ->cascadeOnDelete();
            $table->unique(['company_id', 'source_program_id', 'delivery_id'], 'mmc_masterlist_records_source_delivery_unique');
            $table->index(['company_id', 'tracker_project_id', 'source_program_id'], 'mmc_masterlist_records_project_source_index');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mmc_platform_masterlist_records');
    }
};
