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

            $table->foreign('keystage_id')
                ->references('id')
                ->on('keystages')
                ->nullOnDelete();

            $table->foreign('project_id')
                ->references('project_id')
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
            $table->dropForeign(['keystage_id']);
            $table->dropForeign(['project_id']);
        });
    }
};