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
        Schema::create('mmc_biometric_connections', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('device_ip', 45);
            $table->unsignedSmallInteger('tcp_port')->default(4370);
            $table->unsignedInteger('device_id')->default(1);
            $table->string('device_serial')->nullable();
            $table->text('comm_key')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mmc_biometric_connections');
    }
};
