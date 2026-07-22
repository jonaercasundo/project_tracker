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
        Schema::table('package_status', function (Blueprint $table) {


            $table->integer('accuracy')->nullable();

            $table->timestamp('delivered_at')->nullable();

            $table->string('receiver_name')->nullable();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('package_status', function (Blueprint $table) {
            //
        });
    }
};
