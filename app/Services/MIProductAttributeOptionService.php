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

    /** @param array<int, string> $values
     * @return array<int, string>
     */
    private function persist(array $values, string $modelClass, string $nameColumn): array
    {
        $canonicalNames = [];
        foreach ($values as $value) {
            $name = trim($value);
            if ($name === '') {
                continue;
            }

            $normalizedName = mb_strtolower($name);
            $option = $modelClass::query()
                ->where('normalized_name', $normalizedName)
                ->orWhereRaw('LOWER(TRIM('.$nameColumn.')) = ?', [$normalizedName])
                ->orderBy('id')
                ->first();

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
}
