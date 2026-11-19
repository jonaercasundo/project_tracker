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
        Schema::table('ai_provider_settings', function (Blueprint $table): void {
            $table->string('research_provider', 32)->default('openai');
            $table->text('anthropic_api_key')->nullable();
            $table->string('anthropic_model', 100)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ai_provider_settings', function (Blueprint $table): void {
            $table->dropColumn(['research_provider', 'anthropic_api_key', 'anthropic_model']);
        });
    }
};
