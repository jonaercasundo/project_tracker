<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mi_liquidation_items', function (Blueprint $table): void {
            $table->string('ocr_reference', 255)->nullable()->after('ref_no');
        });

        Schema::create('mi_liquidation_reference_sequences', function (Blueprint $table): void {
            $table->string('reference_date', 8)->primary();
            $table->unsignedInteger('next_sequence')->default(1);
        });

        $hasDuplicateReferences = DB::table('mi_liquidation_items')
            ->select('ref_no')
            ->groupBy('ref_no')
            ->havingRaw('COUNT(*) > 1')
            ->exists();

        if (! $hasDuplicateReferences) {
            Schema::table('mi_liquidation_items', function (Blueprint $table): void {
                $table->unique('ref_no', 'mi_liquidation_items_ref_no_unique');
            });
        }
    }

    public function down(): void
    {
        $hasUniqueReferenceIndex = collect(Schema::getIndexes('mi_liquidation_items'))
            ->contains(fn (array $index): bool => $index['name'] === 'mi_liquidation_items_ref_no_unique');

        if ($hasUniqueReferenceIndex) {
            Schema::table('mi_liquidation_items', function (Blueprint $table): void {
                $table->dropUnique('mi_liquidation_items_ref_no_unique');
            });
        }

        Schema::dropIfExists('mi_liquidation_reference_sequences');

        Schema::table('mi_liquidation_items', function (Blueprint $table): void {
            $table->dropColumn('ocr_reference');
        });
    }
};
