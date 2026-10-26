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
        if (Schema::hasTable('ai_messages') && ! Schema::hasColumn('ai_messages', 'metadata')) {
            Schema::table('ai_messages', function (Blueprint $table): void {
                $table->json('metadata')->nullable();
            });
        }

        Schema::create('ai_quotation_workflows', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('conversation_id')->unique()->constrained('ai_conversations')->cascadeOnDelete();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('company_id');
            $table->string('idempotency_key', 64)->unique();
            $table->json('state')->nullable();
            $table->unsignedBigInteger('quotation_id')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'company_id']);
        });

        Schema::create('ai_quotations', function (Blueprint $table): void {
            $table->id();
            $table->string('quote_number', 40)->unique();
            $table->string('idempotency_key', 64)->unique();
            $table->unsignedBigInteger('conversation_id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('company_id');
            $table->string('customer_name', 150);
            $table->string('currency', 3)->default('PHP');
            $table->json('items');
            $table->decimal('total', 14, 2);
            $table->string('status', 20)->default('draft');
            $table->timestamp('confirmed_at');
            $table->timestamps();
            $table->index(['user_id', 'company_id', 'created_at']);
        });

        Schema::create('ai_quotation_audits', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('conversation_id')->nullable();
            $table->unsignedBigInteger('quotation_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->string('action', 40);
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->index(['company_id', 'created_at']);
            $table->index(['user_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_quotation_audits');
        Schema::dropIfExists('ai_quotations');
        Schema::dropIfExists('ai_quotation_workflows');
        if (Schema::hasTable('ai_messages') && Schema::hasColumn('ai_messages', 'metadata')) {
            Schema::table('ai_messages', function (Blueprint $table): void {
                $table->dropColumn('metadata');
            });
        }
    }
};
