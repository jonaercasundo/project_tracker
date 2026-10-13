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
        Schema::create('logistics_integrations', function (Blueprint $table): void {
            $table->id();
            $table->string('integration_id', 64)->unique();
            $table->string('name', 120);
            $table->string('authentication_method', 32)->default('hmac_sha256');
            $table->string('webhook_url', 500)->nullable();
            $table->json('enabled_event_types');
            $table->string('status', 16)->default('active');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->index(['status', 'name'], 'logistics_integrations_status_name_index');
        });

        Schema::create('logistics_integration_projects', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('integration_id')->constrained('logistics_integrations')->cascadeOnDelete();
            $table->unsignedInteger('project_id');
            $table->timestamps();
            $table->unique(['integration_id', 'project_id'], 'logistics_integration_project_unique');
            $table->index('project_id', 'logistics_integration_project_project_index');
        });

        Schema::create('logistics_integration_schools', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('integration_id')->constrained('logistics_integrations')->cascadeOnDelete();
            $table->unsignedInteger('project_id');
            $table->string('school_id', 255);
            $table->timestamps();
            $table->unique(['integration_id', 'project_id', 'school_id'], 'logistics_integration_school_unique');
            $table->index(['project_id', 'school_id'], 'logistics_integration_school_scope_index');
        });

        Schema::create('logistics_integration_credentials', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('integration_id')->constrained('logistics_integrations')->cascadeOnDelete();
            $table->string('key_id', 64)->unique();
            $table->text('secret');
            $table->string('status', 16)->default('active');
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->index(['integration_id', 'status', 'expires_at'], 'logistics_integration_credential_state_index');
        });

        Schema::create('logistics_integration_audit_logs', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('integration_id')->nullable();
            $table->string('integration_reference', 64)->nullable();
            $table->unsignedBigInteger('actor_user_id')->nullable();
            $table->string('action', 64);
            $table->string('target_type', 64)->nullable();
            $table->string('target_reference', 191)->nullable();
            $table->json('details')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['integration_id', 'created_at'], 'logistics_integration_audit_integration_index');
            $table->index(['action', 'created_at'], 'logistics_integration_audit_action_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('logistics_integration_audit_logs');
        Schema::dropIfExists('logistics_integration_credentials');
        Schema::dropIfExists('logistics_integration_schools');
        Schema::dropIfExists('logistics_integration_projects');
        Schema::dropIfExists('logistics_integrations');
    }
};
