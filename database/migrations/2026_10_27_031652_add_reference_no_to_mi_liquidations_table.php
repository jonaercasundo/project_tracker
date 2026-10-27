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
        Schema::table('mi_liquidations', function (Blueprint $table): void {
            $table->string('reference_no', 32)->nullable()->unique('mi_liquidations_reference_no_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mi_liquidations', function (Blueprint $table): void {
            $table->dropUnique('mi_liquidations_reference_no_unique');
            $table->dropColumn('reference_no');
        });
    }
};
