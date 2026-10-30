<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('inventory_history') && ! Schema::hasColumn('inventory_history', 'changed_by_user_id')) {
            Schema::table('inventory_history', function (Blueprint $table): void {
                $table->unsignedInteger('changed_by_user_id')->nullable()->index();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('inventory_history', 'changed_by_user_id')) {
            Schema::table('inventory_history', function (Blueprint $table): void {
                $table->dropColumn('changed_by_user_id');
            });
        }
    }
};
