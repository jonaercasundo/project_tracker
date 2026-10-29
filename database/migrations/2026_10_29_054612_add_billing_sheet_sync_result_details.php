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
        Schema::table('billing_sheet_syncs', function (Blueprint $table): void {
            $table->unsignedBigInteger('billing_batch_id')->nullable()->index();
            $table->string('school_id', 255)->nullable()->index();
            $table->unsignedInteger('authenticated_user_id')->nullable()->index();
            $table->string('authenticated_user_name')->nullable();
            $table->json('destination_cells')->nullable();
            $table->decimal('written_amount', 15, 2)->nullable();
            $table->json('validation_errors')->nullable();
            $table->unsignedSmallInteger('sync_version')->default(1);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('billing_sheet_syncs', function (Blueprint $table): void {
            $table->dropColumn([
                'billing_batch_id',
                'school_id',
                'authenticated_user_id',
                'authenticated_user_name',
                'destination_cells',
                'written_amount',
                'validation_errors',
                'sync_version',
            ]);
        });
    }
};
