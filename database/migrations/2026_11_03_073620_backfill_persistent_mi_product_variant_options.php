<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $materials = [];
        $colors = [];
        foreach (DB::table('mi_products')->get(['materials', 'color']) as $product) {
            $materials = array_merge($materials, $this->decodeNames($product->materials));
            $colors = array_merge($colors, $this->decodeNames($product->color));
        }
        foreach (DB::table('mi_product_variants')->get(['material', 'color']) as $variant) {
            $materials[] = $variant->material;
            if ($variant->color !== null) {
                $colors[] = $variant->color;
            }
        }

        $this->normalizeAndSeed('mi_materials', 'material_name', $materials);
        $this->normalizeAndSeed('mi_colors', 'color_name', $colors);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Keep persistent options on rollback because products and variants may already reference them.
    }

    /** @return array<int, string> */
    private function decodeNames(mixed $value): array
    {
        if (! is_string($value) || trim($value) === '') {
            return [];
        }

        $decoded = json_decode($value, true);

        return is_array($decoded) ? array_values(array_filter($decoded, 'is_string')) : [$value];
    }

    /** @param array<int, string> $sourceNames */
    private function normalizeAndSeed(string $table, string $nameColumn, array $sourceNames): void
    {
        $canonicalNames = [];
        foreach (DB::table($table)->orderBy('id')->get(['id', $nameColumn]) as $option) {
            $name = $this->normalizeSpacing((string) $option->{$nameColumn});
            $normalized = mb_strtolower($name);
            if ($name === '') {
                continue;
            }
            if (! array_key_exists($normalized, $canonicalNames)) {
                $canonicalNames[$normalized] = $name;
            }
        }

        foreach ($sourceNames as $sourceName) {
            $name = $this->normalizeSpacing((string) $sourceName);
            $normalized = mb_strtolower($name);
            if ($name === '' || array_key_exists($normalized, $canonicalNames)) {
                continue;
            }

            DB::table($table)->insert([
                $nameColumn => $name,
                'normalized_name' => $normalized,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $canonicalNames[$normalized] = $name;
        }
    }

    private function normalizeSpacing(string $value): string
    {
        return preg_replace('/\s+/u', ' ', trim($value)) ?? trim($value);
    }
};
