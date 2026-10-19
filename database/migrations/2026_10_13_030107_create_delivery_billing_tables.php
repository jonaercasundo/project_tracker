<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $hasTable = fn (string $table): bool => Schema::getConnection()
            ->withoutPretending(fn (): bool => Schema::hasTable($table));

        foreach (['deliveries', 'invoices', 'users'] as $table) {
            if (! $hasTable($table)) {
                throw new RuntimeException("The legacy {$table} table must exist before delivery billing can be installed.");
            }
        }

        if (! $hasTable('delivery_billing_records')) {
            Schema::create('delivery_billing_records', function (Blueprint $table): void {
                $table->id();
                $table->integer('delivery_id')->unique();
                $table->string('status', 32)->index();
                $table->integer('invoice_id')->nullable()->index();
                $table->decimal('billing_amount', 15, 2)->nullable();
                $table->string('billing_reference', 255)->nullable();
                $table->text('billing_remarks')->nullable();
                $table->integer('submitted_for_billing_by')->nullable();
                $table->timestamp('submitted_for_billing_at')->nullable();
                $table->integer('confirmed_billed_by')->nullable();
                $table->timestamp('confirmed_billed_at')->nullable();
                $table->string('status_before_hold', 32)->nullable();
                $table->timestamps();

                $table->foreign('delivery_id')
                    ->references('delivery_id')
                    ->on('deliveries')
                    ->restrictOnDelete();
                $table->foreign('invoice_id')
                    ->references('invoice_id')
                    ->on('invoices')
                    ->restrictOnDelete();
                $table->foreign('submitted_for_billing_by')
                    ->references('user_id')
                    ->on('users')
                    ->nullOnDelete();
                $table->foreign('confirmed_billed_by')
                    ->references('user_id')
                    ->on('users')
                    ->nullOnDelete();
            });
        }

        if (! $hasTable('delivery_billing_histories')) {
            Schema::create('delivery_billing_histories', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('delivery_billing_record_id')
                    ->constrained('delivery_billing_records')
                    ->restrictOnDelete();
                $table->string('previous_status', 32)->nullable();
                $table->string('new_status', 32);
                $table->integer('invoice_id')->nullable();
                $table->integer('changed_by')->nullable();
                $table->string('changed_by_name')->nullable();
                $table->text('remarks')->nullable();
                $table->timestamp('created_at')->useCurrent();

                $table->foreign('invoice_id')
                    ->references('invoice_id')
                    ->on('invoices')
                    ->restrictOnDelete();
                $table->foreign('changed_by')
                    ->references('user_id')
                    ->on('users')
                    ->nullOnDelete();
            });
        }

        $hasHistoryIndex = Schema::getConnection()->withoutPretending(
            fn (): bool => Schema::hasIndex('delivery_billing_histories', ['delivery_billing_record_id', 'created_at'])
        );

        if (! $hasHistoryIndex) {
            Schema::table('delivery_billing_histories', function (Blueprint $table): void {
                $table->index(['delivery_billing_record_id', 'created_at'], 'delivery_billing_history_record_created_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_billing_histories');
        Schema::dropIfExists('delivery_billing_records');
    }
};
