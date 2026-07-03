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
            $table->unsignedBigInteger('project_id')
                  ->nullable()
                  ->after('school_id');

            // Uncomment if you want a foreign key constraint
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
        Schema::table('school', function (Blueprint $table) {
            // Uncomment if you added the foreign key
            // $table->dropForeign(['project_id']);

            $table->dropColumn('project_id');
        });
    }
};