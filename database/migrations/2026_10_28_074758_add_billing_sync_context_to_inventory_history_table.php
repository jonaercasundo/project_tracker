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
        if (! Schema::hasTable('inventory_history')) {
            throw new RuntimeException('The legacy inventory_history table must exist before billing sync history can be added.');
        }

        Schema::table('inventory_history', function (Blueprint $table): void {
            if (! Schema::hasColumn('inventory_history', 'project_id')) {
                $table->integer('project_id')->nullable();
            }
            if (! Schema::hasColumn('inventory_history', 'delivery_id')) {
                $table->integer('delivery_id')->nullable();
            }
            if (! Schema::hasColumn('inventory_history', 'package_id')) {
                $table->integer('package_id')->nullable();
            }
            if (! Schema::hasColumn('inventory_history', 'dr_no')) {
                $table->string('dr_no', 255)->nullable();
            }
            if (! Schema::hasColumn('inventory_history', 'quantity')) {
                $table->integer('quantity')->nullable();
            }
        });

        if (! Schema::hasIndex('inventory_history', ['project_id', 'delivery_id', 'change_type'])) {
            Schema::table('inventory_history', function (Blueprint $table): void {
                $table->index(['project_id', 'delivery_id', 'change_type'], 'inventory_history_billing_sync_idx');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('inventory_history')) {
            return;
        }

        if (Schema::hasIndex('inventory_history', 'inventory_history_billing_sync_idx')) {
            Schema::table('inventory_history', function (Blueprint $table): void {
                $table->dropIndex('inventory_history_billing_sync_idx');
            });
        }

        $columns = array_values(array_filter(
            ['project_id', 'delivery_id', 'package_id', 'dr_no', 'quantity'],
            fn (string $column): bool => Schema::hasColumn('inventory_history', $column)
        ));
        if ($columns !== []) {
            Schema::table('inventory_history', function (Blueprint $table) use ($columns): void {
                $table->dropColumn($columns);
            });
        }
    }
};
