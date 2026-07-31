<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
public function up()
{
    Schema::table('mi_products', function (Blueprint $table) {

        $table->json('materials')->change();
        $table->json('color')->nullable()->change();

    });
}

public function down()
{
    Schema::table('mi_products', function (Blueprint $table) {

        $table->string('materials')->change();
        $table->string('color')->nullable()->change();

    });
}
};
