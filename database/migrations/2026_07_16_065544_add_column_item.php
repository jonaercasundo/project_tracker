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
        Schema::table('items', function (Blueprint $table) {

            $table->unsignedBigInteger('delivery_address_id')
                ->nullable()
                ->after('description');

            $table->unsignedBigInteger('lot_id')
                ->nullable()
                ->after('delivery_address_id');

            $table->unsignedBigInteger('keystage_id')
                ->nullable()
                ->after('lot_id');

            // Foreign Keys
            $table->foreign('delivery_address_id')
                ->references('id')
                ->on('delivery_address')
                ->nullOnDelete();

            $table->foreign('lot_id')
                ->references('id')
                ->on('lots')
                ->nullOnDelete();

            $table->foreign('keystage_id')
                ->references('id')
                ->on('job_batches') // Change to 'keystage' if that's your table name
                ->nullOnDelete();

            $table->foreign('project_id')
                ->references('id')
                ->on('project_information')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('items', function (Blueprint $table) {

            $table->dropForeign(['delivery_address_id']);
            $table->dropForeign(['lot_id']);
            $table->dropForeign(['keystage_id']);
            $table->dropForeign(['project_id']);

            $table->dropColumn([
                'delivery_address_id',
                'lot_id',
                'keystage_id',
            ]);
        });
    }
};