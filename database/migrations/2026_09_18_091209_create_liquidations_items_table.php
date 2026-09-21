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
        // Renamed from "liquidations_items" to "liquidation_items"
        // to follow Laravel's singular-parent + plural-child convention,
        // so the LiquidationItem model resolves this table by default.
        Schema::create('liquidation_items', function (Blueprint $table) {
            $table->id();

            /*
             * Link to the liquidation.
             *
             * liquidations.id = BIGINT UNSIGNED
             */
            $table->unsignedBigInteger('liquidation_id');

            $table->foreign('liquidation_id')
                ->references('id')
                ->on('liquidations')
                ->cascadeOnDelete();

            /*
             * Link to the original budget request item.
             *
             * Nullable because an employee may add an
             * unbudgeted actual expense.
             *
             * budget_request_items.id = BIGINT UNSIGNED
             */
            $table->unsignedBigInteger('budget_request_item_id')->nullable();

            $table->foreign('budget_request_item_id')
                ->references('id')
                ->on('budget_request_items')
                ->nullOnDelete();

            /*
             * Expense information
             */
            $table->string('expense_category');
            $table->string('particular');

            /*
             * Actual amount paid
             */
            $table->decimal('actual_cash', 12, 2)->default(0);
            $table->decimal('actual_credit_card', 12, 2)->default(0);
            $table->decimal('actual_travel_agent', 12, 2)->default(0);
            $table->decimal('actual_total', 12, 2)->default(0);

            /*
             * Receipt status.
             *
             * Lowercased to match the snake_case convention used by
             * every other enum in this schema (e.g. budget_requested,
             * in_progress). Update any frontend/API code that expects
             * the old "Yes"/"No"/"N/A" capitalization.
             */
            $table->enum('receipt_attached', [
                'yes',
                'no',
                'n_a',
            ])->default('yes');

            /*
             * Additional notes
             */
            $table->text('remarks')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('liquidation_items');
    }
};