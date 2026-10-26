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
        Schema::create('ai_provider_settings', function (Blueprint $table): void {
            $table->unsignedTinyInteger('id')->primary();
            $table->string('provider', 32)->default('openai');
            $table->text('api_key')->nullable();
            $table->string('model', 100)->default('gpt-5.5');
            $table->boolean('enabled')->default(true);
            $table->unsignedSmallInteger('per_user_requests_per_minute')->default(12);
            $table->unsignedBigInteger('monthly_token_limit')->nullable();
            $table->foreignId('configured_by')->nullable()->constrained('users', 'user_id')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('ai_provider_setting_audits', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('performed_by')->nullable()->constrained('users', 'user_id')->nullOnDelete();
            $table->string('action', 40);
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->index(['action', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_provider_setting_audits');
        Schema::dropIfExists('ai_provider_settings');
    }
};
