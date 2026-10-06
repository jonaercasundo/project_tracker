<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['budget_requests', 'liquidations'] as $name) {
            Schema::table($name, function (Blueprint $table): void {
                $table->unsignedBigInteger('company_id')->nullable();
                $table->foreign('company_id')->references('company_id')->on('companies')->restrictOnDelete();
                $table->index(['company_id', 'status']);
                $table->softDeletes();
            });
        }
        Schema::create('budget_releases', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('budget_request_id')->constrained()->restrictOnDelete();
            $table->unsignedBigInteger('company_id');
            $table->foreign('company_id')->references('company_id')->on('companies')->restrictOnDelete();
            $table->decimal('amount', 12, 2);
            $table->char('currency', 3);
            $table->decimal('exchange_rate', 12, 4)->nullable();
            $table->string('payment_method')->nullable();
            $table->string('reference_no')->nullable();
            $table->text('note')->nullable();
            $table->unsignedBigInteger('released_by')->nullable();
            $table->foreign('released_by')->references('user_id')->on('users')->nullOnDelete();
            $table->timestamp('released_at');
            $table->timestamps();
        });
        Schema::create('financial_settlements', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('liquidation_id')->unique()->constrained()->restrictOnDelete();
            $table->unsignedBigInteger('company_id');
            $table->foreign('company_id')->references('company_id')->on('companies')->restrictOnDelete();
            foreach (['released_amount', 'expense_amount', 'employee_return_amount', 'company_reimbursement_amount', 'settlement_amount', 'outstanding_balance'] as $column) {
                $table->decimal($column, 12, 2);
            }
            $table->char('currency', 3);
            $table->string('settlement_method');
            $table->string('reference_no');
            $table->unsignedBigInteger('settled_by')->nullable();
            $table->foreign('settled_by')->references('user_id')->on('users')->nullOnDelete();
            $table->timestamp('settled_at');
            $table->text('note')->nullable();
            $table->timestamps();
        });
        Schema::create('financial_activities', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->foreign('company_id')->references('company_id')->on('companies')->restrictOnDelete();
            $table->string('record_type', 40);
            $table->unsignedBigInteger('record_id');
            $table->string('event', 80);
            $table->string('previous_status')->nullable();
            $table->string('new_status')->nullable();
            $table->unsignedBigInteger('actor_user_id')->nullable();
            $table->foreign('actor_user_id')->references('user_id')->on('users')->nullOnDelete();
            $table->string('actor_name_snapshot');
            $table->text('actor_role_snapshot')->nullable();
            $table->decimal('amount', 14, 2)->nullable();
            $table->char('currency', 3)->nullable();
            $table->string('reference_no')->nullable();
            $table->text('note')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('created_at');
            $table->index(['record_type', 'record_id', 'id']);
            $table->index(['company_id', 'event', 'created_at']);
        });
    }

    public function down(): void
    {
        throw new RuntimeException('Financial evidence requires a reviewed forward reconciliation; automatic rollback is disabled.');
    }
};
