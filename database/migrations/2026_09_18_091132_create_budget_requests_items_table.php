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
        // Renamed from "budget_requests_items" to "budget_request_items"
        // to follow Laravel's singular-parent + plural-child convention,
        // so the BudgetRequestItem model resolves this table by default.
        Schema::create('budget_request_items', function (Blueprint $table) {
            $table->id();

            // Link each item to its budget request.
            // budget_requests.id is BIGINT UNSIGNED.
            $table->unsignedBigInteger('budget_request_id');

            $table->foreign('budget_request_id')
                ->references('id')
                ->on('budget_requests')
                ->cascadeOnDelete();

            $table->string('expense_category');
            // Example: Airfare, Hotel / Accommodation, Per Diem, Transportation

            $table->string('particular');
            // Free-text description of the expense.

            $table->decimal('budget_cash', 12, 2)->default(0);
            $table->decimal('budget_credit_card', 12, 2)->default(0);
            $table->decimal('budget_travel_agent', 12, 2)->default(0);
            $table->decimal('budget_total', 12, 2)->default(0);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('budget_request_items');
    }
};