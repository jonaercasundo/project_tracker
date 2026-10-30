<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('delivery_history') && ! Schema::hasColumn('delivery_history', 'previous_status')) {
            Schema::table('delivery_history', function (Blueprint $table): void {
                $table->string('previous_status')->nullable()->after('status');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('delivery_history', 'previous_status')) {
            Schema::table('delivery_history', function (Blueprint $table): void {
                $table->dropColumn('previous_status');
            });
        }
    }
};
