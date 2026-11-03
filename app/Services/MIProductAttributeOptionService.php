<?php

namespace App\Services;

use App\Models\MI_Color;
use App\Models\MI_Material;
use Illuminate\Database\Eloquent\Model;

class MIProductAttributeOptionService
{
    /** @param array<int, string> $values
     * @return array<int, string>
     */
    public function persistMaterials(array $values): array
    {
        return $this->persist($values, MI_Material::class, 'material_name');
    }

    /** @param array<int, string> $values
     * @return array<int, string>
     */
    public function persistColors(array $values): array
    {
        return $this->persist($values, MI_Color::class, 'color_name');
    }

    /** @param array<int, array<string, mixed>> $variants
     * @return array<int, array<string, mixed>>
     */
    public function persistVariantOptions(array $variants): array
    {
        if ($variants === []) {
            return [];
        }

        $materialNames = [];
        $colorNames = [];
        foreach ($variants as $variant) {
            $materialNames = array_merge($materialNames, $this->variantOptionValues($variant, 'materials', 'material'));
            $colorNames = array_merge($colorNames, $this->variantOptionValues($variant, 'colors', 'color'));
        }

        $materialMap = $this->canonicalMap($this->persistMaterials($materialNames));
        $colorMap = $this->canonicalMap($this->persistColors($colorNames));

        foreach ($variants as &$variant) {
            $variant['materials'] = $this->canonicalizeOptions(
                $this->variantOptionValues($variant, 'materials', 'material'),
                $materialMap,
            );
            $variant['colors'] = $this->canonicalizeOptions(
                $this->variantOptionValues($variant, 'colors', 'color'),
                $colorMap,
            );
            $variant['material'] = $variant['materials'][0] ?? '';
            $variant['color'] = $variant['colors'][0] ?? null;
        }
        unset($variant);

        return array_values($variants);
    }

    /** @param array<int, string> $values
     * @return array<int, string>
     */
    private function persist(array $values, string $modelClass, string $nameColumn): array
    {
        $canonicalNames = [];
        $existingOptions = $modelClass::query()->get(['id', $nameColumn, 'normalized_name']);
        foreach ($values as $value) {
            $name = $this->normalizeSpacing($value);
            if ($name === '') {
                continue;
            }

            $normalizedName = mb_strtolower($name);
            $option = $existingOptions->first(function (Model $candidate) use ($nameColumn, $normalizedName): bool {
                $candidateName = $candidate->getAttribute('normalized_name')
                    ?: $candidate->getAttribute($nameColumn);
                $candidateName = mb_strtolower($this->normalizeSpacing((string) $candidateName));

                return $candidateName === $normalizedName;
            });

            if (! $option instanceof Model) {
                $option = $modelClass::firstOrCreate(
                    ['normalized_name' => $normalizedName],
                    [$nameColumn => $name, 'is_active' => true],
                );
            }

            $canonicalNames[$normalizedName] = $option->getAttribute($nameColumn);
        }

        return array_values($canonicalNames);
    }

    private function normalizeSpacing(string $value): string
    {
        return preg_replace('/\s+/u', ' ', trim($value)) ?? trim($value);
    }

    /** @param array<int, string> $values
     * @return array<string, string>
     */
    private function canonicalMap(array $values): array
    {
        $canonicalNames = [];
        foreach ($values as $value) {
            $canonicalNames[$this->normalizedKey($value)] = $value;
        }

        return $canonicalNames;
    }

    private function normalizedKey(string $value): string
    {
        return mb_strtolower($this->normalizeSpacing($value));
    }

    /** @param array<string, mixed> $variant
     * @return array<int, string>
     */
    private function variantOptionValues(array $variant, string $pluralKey, string $legacyKey): array
    {
        $values = $variant[$pluralKey] ?? [$variant[$legacyKey] ?? ''];
        if (! is_array($values)) {
            $values = [$values];
        }

        return array_values(array_filter(array_map(
            fn (mixed $value): string => $this->normalizeSpacing((string) $value),
            $values,
        ), static fn (string $value): bool => $value !== ''));
    }

    /** @param array<int, string> $values
     * @param  array<string, string>  $canonicalMap
     * @return array<int, string>
     */
    private function canonicalizeOptions(array $values, array $canonicalMap): array
    {
        $canonicalValues = [];
        foreach ($values as $value) {
            $key = $this->normalizedKey($value);
            if (isset($canonicalMap[$key])) {
                $canonicalValues[$key] = $canonicalMap[$key];
            }
        }

        return array_values($canonicalValues);
    }
}
