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
        if (! Schema::hasTable('mi_product_variants')) {
            return;
        }

        Schema::table('mi_product_variants', function (Blueprint $table): void {
            if (! Schema::hasColumn('mi_product_variants', 'materials')) {
                $table->json('materials')->nullable();
            }
            if (! Schema::hasColumn('mi_product_variants', 'colors')) {
                $table->json('colors')->nullable();
            }
        });

        DB::table('mi_product_variants')->select(['variant_id', 'material', 'color', 'materials', 'colors'])
            ->orderBy('variant_id')
            ->chunkById(500, function ($variants): void {
                foreach ($variants as $variant) {
                    $attributes = [];
                    if ($variant->materials === null) {
                        $attributes['materials'] = json_encode($this->decodeList($variant->material));
                    }
                    if ($variant->colors === null) {
                        $attributes['colors'] = json_encode($this->decodeList($variant->color));
                    }
                    if ($attributes !== []) {
                        DB::table('mi_product_variants')->where('variant_id', $variant->variant_id)->update($attributes);
                    }
                }
            }, 'variant_id', 'variant_id');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Keep the JSON attributes so rolling back application code never discards variant selections.
    }

    /** @return array<int, string> */
    private function decodeList(?string $value): array
    {
        $value = trim((string) $value);
        if ($value === '') {
            return [];
        }

        $decoded = json_decode($value, true);
        if (is_array($decoded)) {
            return array_values(array_filter($decoded, 'is_string'));
        }

        return [$value];
    }
};
