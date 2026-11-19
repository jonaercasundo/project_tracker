<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('mi_product_factory_costs', function (Blueprint $table): void {
            $table->enum('currency', ['PHP', 'USD'])->default('USD')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::table('mi_product_factory_costs')->where('currency', 'USD')->exists()) {
            throw new RuntimeException('Cannot roll back USD factory costs to a PHP-only column. Resolve their currency explicitly first.');
        }

        Schema::table('mi_product_factory_costs', function (Blueprint $table): void {
            $table->enum('currency', ['PHP'])->default('PHP')->change();
        });
    }
};
