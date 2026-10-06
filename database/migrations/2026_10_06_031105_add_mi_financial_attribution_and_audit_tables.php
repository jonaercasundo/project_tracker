<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Add financial attribution fields to existing tables
        |--------------------------------------------------------------------------
        |
        | Guard each column so this migration can recover safely if a previous
        | migration attempt partially completed.
        |
        */

        foreach (['budget_requests', 'liquidations'] as $name) {
            if (! Schema::hasColumn($name, 'company_id')) {
                Schema::table($name, function (Blueprint $table): void {
                    $table->unsignedBigInteger('company_id')->nullable();

                    $table->foreign('company_id')
                        ->references('company_id')
                        ->on('companies')
                        ->restrictOnDelete();
                });
            }

            if (! Schema::hasColumn($name, 'deleted_at')) {
                Schema::table($name, function (Blueprint $table): void {
                    $table->softDeletes();
                });
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Budget Releases
        |--------------------------------------------------------------------------
        */

        if (! Schema::hasTable('budget_releases')) {
            Schema::create('budget_releases', function (Blueprint $table): void {
                $table->id();

                $table->foreignId('budget_request_id')
                    ->constrained('budget_requests')
                    ->restrictOnDelete();

                $table->unsignedBigInteger('company_id');

                $table->foreign('company_id')
                    ->references('company_id')
                    ->on('companies')
                    ->restrictOnDelete();

                $table->decimal('amount', 12, 2);
                $table->char('currency', 3);

                $table->decimal('exchange_rate', 12, 4)->nullable();

                $table->string('payment_method')->nullable();
                $table->string('reference_no')->nullable();
                $table->text('note')->nullable();

                /*
                 * users.user_id is INT(11), not BIGINT UNSIGNED.
                 * This must therefore use integer().
                 */
                $table->integer('released_by')->nullable();

                $table->foreign('released_by')
                    ->references('user_id')
                    ->on('users')
                    ->nullOnDelete();

                $table->timestamp('released_at');

                $table->timestamps();

                $table->index('company_id');
                $table->index('released_by');
                $table->index('released_at');
            });
        }

        /*
        |--------------------------------------------------------------------------
        | Financial Settlements
        |--------------------------------------------------------------------------
        */

        if (! Schema::hasTable('financial_settlements')) {
            Schema::create('financial_settlements', function (Blueprint $table): void {
                $table->id();

                $table->foreignId('liquidation_id')
                    ->unique()
                    ->constrained('liquidations')
                    ->restrictOnDelete();

                $table->unsignedBigInteger('company_id');

                $table->foreign('company_id')
                    ->references('company_id')
                    ->on('companies')
                    ->restrictOnDelete();

                $table->decimal('released_amount', 12, 2);
                $table->decimal('expense_amount', 12, 2);
                $table->decimal('employee_return_amount', 12, 2);
                $table->decimal('company_reimbursement_amount', 12, 2);
                $table->decimal('settlement_amount', 12, 2);
                $table->decimal('outstanding_balance', 12, 2);

                $table->char('currency', 3);

                $table->string('settlement_method');
                $table->string('reference_no');

                /*
                 * Must match users.user_id INT(11).
                 */
                $table->integer('settled_by')->nullable();

                $table->foreign('settled_by')
                    ->references('user_id')
                    ->on('users')
                    ->nullOnDelete();

                $table->timestamp('settled_at');

                $table->text('note')->nullable();

                $table->timestamps();

                $table->index('company_id');
                $table->index('settled_by');
                $table->index('settled_at');
            });
        }

        /*
        |--------------------------------------------------------------------------
        | Financial Activities / Audit Trail
        |--------------------------------------------------------------------------
        */

        if (! Schema::hasTable('financial_activities')) {
            Schema::create('financial_activities', function (Blueprint $table): void {
                $table->id();

                $table->unsignedBigInteger('company_id')->nullable();

                $table->foreign('company_id')
                    ->references('company_id')
                    ->on('companies')
                    ->restrictOnDelete();

                $table->string('record_type', 40);
                $table->unsignedBigInteger('record_id');

                $table->string('event', 80);

                $table->string('previous_status')->nullable();
                $table->string('new_status')->nullable();

                /*
                 * Must match users.user_id INT(11).
                 */
                $table->integer('actor_user_id')->nullable();

                $table->foreign('actor_user_id')
                    ->references('user_id')
                    ->on('users')
                    ->nullOnDelete();

                $table->string('actor_name_snapshot');
                $table->text('actor_role_snapshot')->nullable();

                $table->decimal('amount', 14, 2)->nullable();
                $table->char('currency', 3)->nullable();

                $table->string('reference_no')->nullable();
                $table->text('note')->nullable();

                $table->json('metadata')->nullable();

                $table->timestamp('created_at');

                $table->index(
                    ['record_type', 'record_id', 'id'],
                    'financial_activities_record_index'
                );

                $table->index(
                    ['company_id', 'event', 'created_at'],
                    'financial_activities_company_event_index'
                );

                $table->index('actor_user_id');
            });
        }
    }

    public function down(): void
    {
        throw new RuntimeException(
            'Financial evidence requires a reviewed forward reconciliation; automatic rollback is disabled.'
        );
    }
};