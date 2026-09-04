<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mi_liquidation_items', function (Blueprint $table) {
            // Remove the foreign key first
            $table->dropForeign(['requested_by']);
        });

        Schema::table('mi_liquidation_items', function (Blueprint $table) {
            // Change from integer to string
            $table->string('requested_by', 255)->change();
        });
    }

    public function down(): void
    {
        Schema::table('mi_liquidation_items', function (Blueprint $table) {
            $table->unsignedBigInteger('requested_by')->change();
        });

        Schema::table('mi_liquidation_items', function (Blueprint $table) {
            $table->foreign('requested_by')
                ->references('user_id')
                ->on('users');
        });
    }
};
