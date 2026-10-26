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
        Schema::create('ai_assistant_attachments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('conversation_id')->index();
            $table->unsignedBigInteger('user_id')->index();
            $table->unsignedBigInteger('company_id')->index();
            $table->string('disk', 40)->default('local');
            $table->string('path', 500);
            $table->string('original_name', 255);
            $table->unsignedBigInteger('size_bytes');
            $table->char('sha256', 64);
            $table->longText('parsed_data');
            $table->timestamp('expires_at')->index();
            $table->timestamps();
            $table->index(['user_id', 'company_id', 'conversation_id', 'expires_at'], 'ai_assistant_attachments_owner_scope');
        });

        Schema::create('ai_assistant_attachment_audits', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('attachment_id')->index();
            $table->unsignedBigInteger('actor_user_id')->index();
            $table->unsignedBigInteger('company_id')->index();
            $table->unsignedBigInteger('conversation_id')->index();
            $table->string('event', 40);
            $table->unsignedInteger('item_count')->default(0);
            $table->char('file_sha256', 64);
            $table->timestamp('created_at')->useCurrent();
            $table->index(['company_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_assistant_attachment_audits');
        Schema::dropIfExists('ai_assistant_attachments');
    }
};
