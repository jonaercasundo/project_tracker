<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
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

            $table->foreign('delivery_id')->references('delivery_id')->on('deliveries')->restrictOnDelete();
            $table->index(['project_id', 'return_type', 'status', 'monitoring_date'], 'delivery_returns_monitoring_idx');
            $table->index(['delivery_id', 'return_type'], 'delivery_returns_delivery_type_idx');
        });

        Schema::table('inventory_history', function (Blueprint $table): void {
            $table->unsignedInteger('delivery_return_id')->nullable();
            $table->unique('delivery_return_id', 'inventory_history_delivery_return_unique');
            $table->foreign('delivery_return_id')->references('return_id')->on('delivery_returns')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('inventory_history', function (Blueprint $table): void {
            $table->dropForeign(['delivery_return_id']);
            $table->dropUnique('inventory_history_delivery_return_unique');
            $table->dropColumn('delivery_return_id');
        });

        Schema::dropIfExists('delivery_returns');
    }
};
