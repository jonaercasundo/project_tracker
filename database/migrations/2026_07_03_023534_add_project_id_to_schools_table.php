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
        Schema::table('school', function (Blueprint $table) {

            // project_id already exists
            // Add foreign key only if it does not exist

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
        Schema::table('school', function (Blueprint $table) {

            $table->dropForeign(['project_id']);

        });
    }
};