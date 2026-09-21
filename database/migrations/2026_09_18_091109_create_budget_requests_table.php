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
        Schema::create('budget_requests', function (Blueprint $table) {
            $table->id();

            $table->string('control_id')->unique(); // BR-YYYY-####

            // Employee who submitted the budget request.
            // Existing users table uses user_id INT.
            $table->integer('employee_id'); // signed to match users.user_id int(11)

            $table->foreign('employee_id')
                ->references('user_id')
                ->on('users')
                ->restrictOnDelete(); // required field: don't allow deleting a user who has requests

            $table->string('department');
            $table->text('objectives')->nullable();

            $table->date('travel_date_from')->nullable();
            $table->date('travel_date_to')->nullable();

            $table->string('place')->nullable();
            $table->string('country')->nullable();

            // Cached totals - recalculated from
            // budget_request_items whenever items change.
            $table->decimal('budget_cash', 12, 2)->default(0);
            $table->decimal('budget_credit_card', 12, 2)->default(0);
            $table->decimal('budget_travel_agent', 12, 2)->default(0);
            $table->decimal('budget_total', 12, 2)->default(0);

            // Workflow status.
            $table->enum('status', [
                'budget_requested',
                'approved',
                'released',
                'in_progress',
                'liquidated',
                'closed',
                'cancelled',
            ])->default('budget_requested');

            $table->index('status'); // frequently filtered (e.g. "pending approvals")

            // Approver
            $table->integer('approved_by')->nullable(); // signed to match users.user_id int(11)

            $table->foreign('approved_by')
                ->references('user_id')
                ->on('users')
                ->nullOnDelete(); // nullable field: don't block deleting old approver accounts

            $table->timestamp('approved_at')->nullable();

            // Accounting sign-off
            $table->integer('noted_by')->nullable(); // signed to match users.user_id int(11)

            $table->foreign('noted_by')
                ->references('user_id')
                ->on('users')
                ->nullOnDelete();

            $table->timestamp('noted_at')->nullable();

            // Accounting/person who released the budget
            $table->integer('released_by')->nullable(); // signed to match users.user_id int(11)

            $table->foreign('released_by')
                ->references('user_id')
                ->on('users')
                ->nullOnDelete();

            $table->timestamp('released_at')->nullable();

            // Employee confirms that the budget was received.
            $table->timestamp('received_at')->nullable();

            $table->text('remarks')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('budget_requests');
    }
};