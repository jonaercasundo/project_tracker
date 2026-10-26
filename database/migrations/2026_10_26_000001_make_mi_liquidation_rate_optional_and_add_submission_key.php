<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mi_liquidations', function (Blueprint $table): void {
            $table->decimal('exchange_rate', 12, 4)->nullable()->change();
            $table->string('exchange_rate_source', 16)->default('live');
            $table->uuid('submission_key')->nullable()->unique();
        });
    }

    public function down(): void
    {
        throw new RuntimeException('This migration preserves pending-rate reports and submission keys; use a reviewed forward migration to reverse it.');
    }
};
