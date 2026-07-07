<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('school', function (Blueprint $table) {

            $table->string('project_no', 50)
                  ->nullable()
                  ->after('project_id');

            $table->string('project', 255)
                  ->nullable()
                  ->after('project_no');

            $table->decimal('total_contract_price', 12, 2)
                  ->nullable()
                  ->after('project');

        });
    }

    public function down(): void
    {
        Schema::table('school', function (Blueprint $table) {

            $table->dropColumn([
                'project_no',
                'project',
                'total_contract_price'
            ]);

        });
    }
};