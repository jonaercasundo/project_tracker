<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_information', function (Blueprint $table) {
            $table->id();

            $table->string('project_id')->unique();
            $table->string('project_name');

            $table->string('procuring_entity');

            $table->decimal('approved_budget_contract_abc', 15, 2);

            $table->string('lot_no')->nullable();

            $table->string('delivery_period')->nullable();

            // Delivery Location
            $table->string('country')->nullable();
            $table->string('region')->nullable();
            $table->string('province')->nullable();
            $table->string('city_municipality')->nullable();
            $table->string('barangay')->nullable();
            $table->text('delivery_address')->nullable();

            $table->date('date_of_bid_opening')->nullable();

            $table->longText('notes_special_condition')->nullable();

            $table->string('prepared_by')->nullable();
            $table->date('prepared_date')->nullable();
            $table->string('verified_by')->nullable();
            $table->string('reference_no')->nullable();
            $table->string('funding_source')->nullable();
            $table->string('mode_of_procurement')->nullable();
            $table->date('bid_submission_deadline')->nullable();
            $table->date('notice_of_award_date')->nullable();
            $table->enum('status', [
                'Draft',
                'For Review',
                'Published',
                'Awarded',
                'Cancelled',
                'Completed'
            ])->default('Draft');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_information');
    }
};