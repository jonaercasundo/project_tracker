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
        if (! Schema::hasTable('delivery_billing_histories') || Schema::hasColumn('delivery_billing_histories', 'changed_by_name')) {
            return;
        }

        Schema::table('delivery_billing_histories', function (Blueprint $table): void {
            $table->string('changed_by_name')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('delivery_billing_histories') || ! Schema::hasColumn('delivery_billing_histories', 'changed_by_name')) {
            return;
        }

        Schema::table('delivery_billing_histories', function (Blueprint $table): void {
            $table->dropColumn('changed_by_name');
        });
    }
};
