<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('billing_batches', function (Blueprint $table): void {
            $table->id();
            $table->string('batch_ref', 40)->unique();
            $table->string('status', 32)->default('for_billing')->index();
            $table->string('selection_method', 32);
            $table->char('selection_hash', 64);
            $table->uuid('idempotency_key')->unique();
            $table->json('selection_filters')->nullable();
            $table->unsignedInteger('created_by')->nullable();
            $table->string('created_by_name')->nullable();
            $table->unsignedInteger('confirmed_by')->nullable();
            $table->string('confirmed_by_name')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamps();

            $table->foreign('created_by')->references('user_id')->on('users')->nullOnDelete();
            $table->foreign('confirmed_by')->references('user_id')->on('users')->nullOnDelete();
        });

        Schema::table('delivery_billing_records', function (Blueprint $table): void {
            $table->foreignId('billing_batch_id')
                ->nullable()
                ->constrained('billing_batches')
                ->restrictOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('billing_batches') && DB::table('billing_batches')->exists()) {
            throw new RuntimeException('Billing batches contain audit history and cannot be removed by rolling back this migration.');
        }

        Schema::table('delivery_billing_records', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('billing_batch_id');
        });

        Schema::dropIfExists('billing_batches');
    }
};
