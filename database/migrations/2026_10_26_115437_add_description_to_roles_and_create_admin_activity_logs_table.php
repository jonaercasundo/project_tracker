<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('roles', 'description')) {
            Schema::table('roles', function (Blueprint $table): void {
                $table->string('description', 500)->nullable();
            });
        }

        if (! Schema::hasColumn('users', 'is_active')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->boolean('is_active')->default(true);
            });
        }

        if (! Schema::hasTable('admin_activity_logs')) {
            Schema::create('admin_activity_logs', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('actor_user_id')->nullable()->index();
                $table->unsignedBigInteger('company_id')->nullable()->index();
                $table->string('event', 100)->index();
                $table->string('subject_type', 100);
                $table->string('subject_id', 100)->nullable();
                $table->string('summary', 255);
                $table->json('details')->nullable();
                $table->timestamp('created_at')->useCurrent()->index();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_activity_logs');
        if (Schema::hasColumn('users', 'is_active')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->dropColumn('is_active');
            });
        }
        if (Schema::hasColumn('roles', 'description')) {
            Schema::table('roles', function (Blueprint $table): void {
                $table->dropColumn('description');
            });
        }
    }
};
