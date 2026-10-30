<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('delivery_returns')) {
            Schema::create('delivery_returns', function (Blueprint $table): void {
                $table->increments('return_id');
                $table->string('return_type', 32);
                $table->integer('project_id');
                $table->integer('lot_id')->nullable();
                $table->string('school_id', 100)->nullable();
                $table->unsignedInteger('delivery_id');
                $table->integer('package_id')->nullable();
                $table->string('dr_no', 255)->nullable();
                $table->integer('item_id')->nullable();
                $table->unsignedInteger('quantity_returned')->nullable();
                $table->string('status', 32)->default('Reported');
                $table->text('reason');
                $table->text('remarks')->nullable();
                $table->date('monitoring_date');
                $table->date('received_date')->nullable();
                $table->string('received_by', 255)->nullable();
                $table->integer('received_warehouse_id')->nullable();
                $table->unsignedInteger('reported_by_user_id')->nullable();
                $table->timestamps();

                $table->index(['project_id', 'return_type', 'status', 'monitoring_date'], 'delivery_returns_monitoring_idx');
                $table->index(['delivery_id', 'return_type'], 'delivery_returns_delivery_type_idx');
            });
        }

        if (! Schema::hasIndex('delivery_returns', 'delivery_returns_monitoring_idx')) {
            Schema::table('delivery_returns', function (Blueprint $table): void {
                $table->index(['project_id', 'return_type', 'status', 'monitoring_date'], 'delivery_returns_monitoring_idx');
            });
        }
        if (! Schema::hasIndex('delivery_returns', 'delivery_returns_delivery_type_idx')) {
            Schema::table('delivery_returns', function (Blueprint $table): void {
                $table->index(['delivery_id', 'return_type'], 'delivery_returns_delivery_type_idx');
            });
        }

        if (Schema::hasTable('inventory_history') && ! Schema::hasColumn('inventory_history', 'delivery_return_id')) {
            Schema::table('inventory_history', function (Blueprint $table): void {
                $table->unsignedInteger('delivery_return_id')->nullable();
            });
        }

        if (Schema::hasTable('inventory_history')
            && Schema::hasColumn('inventory_history', 'delivery_return_id')
            && ! Schema::hasIndex('inventory_history', 'inventory_history_delivery_return_unique')) {
            Schema::table('inventory_history', function (Blueprint $table): void {
                $table->unique('delivery_return_id', 'inventory_history_delivery_return_unique');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('inventory_history') && Schema::hasColumn('inventory_history', 'delivery_return_id')) {
            if (Schema::hasIndex('inventory_history', 'inventory_history_delivery_return_unique')) {
                Schema::table('inventory_history', function (Blueprint $table): void {
                    $table->dropUnique('inventory_history_delivery_return_unique');
                });
            }
            Schema::table('inventory_history', function (Blueprint $table): void {
                $table->dropColumn('delivery_return_id');
            });
        }

        Schema::dropIfExists('delivery_returns');
    }
};
