<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

    public function up(): void
    {
        Schema::table('ppl_forms', function (Blueprint $table) {

            // change project_title to TEXT
            $table->text('project_title')->change();

            // add new column AFTER YEAR OF REVENUE RECOGNITION
            $table->string('column1')->nullable()
                  ->after('year_of_revenue_recognition');

        });
    }

    public function down(): void
    {
        Schema::table('ppl_forms', function (Blueprint $table) {

            // revert project_title back to string
            $table->string('project_title', 255)->change();

            // drop column1
            $table->dropColumn('column1');

        });
    }
};
