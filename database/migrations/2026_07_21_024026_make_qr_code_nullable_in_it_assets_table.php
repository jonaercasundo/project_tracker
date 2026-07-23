<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('it_assets', function (Blueprint $table) {
            $table->string('qr_code')->nullable()->change();
        });
    }

    public function down()
    {
        Schema::table('it_assets', function (Blueprint $table) {
            $table->string('qr_code')->nullable(false)->change();
        });
    }
};