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
        Schema::create('liquidations', function (Blueprint $table) {
            $table->id();

            /*
             * One liquidation per budget request.
             *
             * budget_requests.id is BIGINT UNSIGNED,
             * so budget_request_id must use the same type.
             *
             * Remove unique() if you later want to allow
             * multiple liquidation submissions/revisions.
             */
            $table->unsignedBigInteger('budget_request_id')->unique();

            $table->foreign('budget_request_id')
                ->references('id')
                ->on('budget_requests')
                ->cascadeOnDelete();

            /*
             * Existing users table uses:
             *
             * users.user_id = BIGINT UNSIGNED
             *
             * Therefore all user foreign keys below use
             * unsignedBigInteger() and explicitly reference user_id, since
             */
            $table->unsignedBigInteger('liquidated_by'); // matches users.user_id BIGINT UNSIGNED

            $table->foreign('liquidated_by')
                ->references('user_id')
                ->on('users')
                ->restrictOnDelete(); // required field: don't allow deleting a user who has liquidations

            /*
             * Liquidation workflow.
             */
            $table->enum('status', [
                'draft',
                'submitted',
                'noted',
                'approved',
                'closed',
            ])->default('draft');

            $table->index('status'); // frequently filtered (e.g. "pending review")

            /*
             * Cached totals.
             *
             * These should be recalculated whenever
             * liquidation_items are added/updated/deleted.
             */
            $table->decimal('actual_total', 12, 2)->default(0);

            // budget_total - actual_total
            $table->decimal('variance', 12, 2)->default(0);

            /*
             * Submission
             */
            $table->timestamp('submitted_at')->nullable();

            /*
             * Accounting / checking sign-off
             */
            $table->unsignedBigInteger('noted_by')->nullable(); // matches users.user_id BIGINT UNSIGNED

            $table->foreign('noted_by')
                ->references('user_id')
                ->on('users')
                ->nullOnDelete(); // nullable field: don't block deleting old accounts

            $table->timestamp('noted_at')->nullable();

            /*
             * Approval
             */
            $table->unsignedBigInteger('approved_by')->nullable(); // matches users.user_id BIGINT UNSIGNED

            $table->foreign('approved_by')
                ->references('user_id')
                ->on('users')
                ->nullOnDelete();

            $table->timestamp('approved_at')->nullable();

            /*
             * Additional remarks
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
        Schema::dropIfExists('liquidations');
    }
};
