<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $duplicates = DB::table('mi_products')->whereNotNull('item_code')
            ->select('item_code')->groupBy('item_code')->havingRaw('COUNT(*) > 1')->exists();
        if ($duplicates) {
            throw new RuntimeException('MI product codes contain duplicates. Resolve them explicitly before adding the unique index; no product data was changed.');
        }

        Schema::table('mi_products', function (Blueprint $table): void {
            $table->unique('item_code', 'mi_products_item_code_unique');
        });
    }

    public function down(): void
    {
        Schema::table('mi_products', function (Blueprint $table): void {
            $table->dropUnique('mi_products_item_code_unique');
        });
    }
};
