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
        if (! Schema::hasTable('ai_conversations')) {
            Schema::create('ai_conversations', function (Blueprint $table): void {
                $table->id();
                // Legacy user/company key definitions differ between installations; scope access in application queries.
                $table->unsignedBigInteger('user_id');
                $table->unsignedBigInteger('company_id');
                $table->string('title', 120)->default('New conversation');
                $table->timestamp('last_message_at')->nullable();
                $table->timestamps();
                $table->index(['user_id', 'company_id', 'updated_at']);
            });
        }

        if (! Schema::hasTable('ai_messages')) {
            Schema::create('ai_messages', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('conversation_id')->constrained('ai_conversations')->cascadeOnDelete();
                $table->string('role', 20);
                $table->longText('content');
                $table->string('model', 100)->nullable();
                $table->unsignedInteger('input_tokens')->nullable();
                $table->unsignedInteger('output_tokens')->nullable();
                $table->timestamps();
                $table->index(['conversation_id', 'id']);
            });
        }

        if (! Schema::hasTable('ai_activity_logs')) {
            Schema::create('ai_activity_logs', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('user_id')->nullable();
                $table->unsignedBigInteger('company_id')->nullable();
                $table->foreignId('conversation_id')->nullable()->constrained('ai_conversations')->nullOnDelete();
                $table->string('model', 100)->nullable();
                $table->string('request_id', 100)->nullable();
                $table->unsignedInteger('input_tokens')->nullable();
                $table->unsignedInteger('output_tokens')->nullable();
                $table->string('status', 30);
                $table->timestamps();
                $table->index(['company_id', 'created_at']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_activity_logs');
        Schema::dropIfExists('ai_messages');
        Schema::dropIfExists('ai_conversations');
    }
};
