<?php

namespace App\Services;

use App\Models\MI_Product;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;

class MIProductFactoryCostService
{
    /**
     * Apply validated changes inside the product update transaction, with the product row locked.
     *
     * @param array<array-key, array{id?: int|null, factory_name: string, factory_cost: string, currency?: string, effective_date?: string|null, remarks?: string|null, is_primary?: bool}> $rows
     * @param list<int|string> $removeIds
     */
    public function synchronize(MI_Product $product, array $rows, array $removeIds): void
    {
        $costs = $product->factoryCosts()->lockForUpdate()->get()->keyBy('id');
        $removeIds = array_map('intval', $removeIds);
        $submittedIds = array_merge($removeIds, array_column($rows, 'id'));
        foreach ($submittedIds as $id) {
            if ($id !== null && ! $costs->has((int) $id)) {
                throw ValidationException::withMessages(['factory_costs' => 'A factory cost has changed since this form was opened. Reload and try again.']);
            }
        }

        $newCount = count(array_filter($rows, fn (array $row): bool => ! isset($row['id'])));
        if ($costs->count() - count($removeIds) + $newCount > 100) {
            throw ValidationException::withMessages(['factory_costs' => 'A product may have at most 100 factory costs.']);
        }

        foreach ($removeIds as $id) {
            $costs->get($id)->delete();
            $costs->forget($id);
        }

        $primaryId = $costs->firstWhere('is_primary', true)?->getKey();
        $product->factoryCosts()->where('is_primary', true)->update(['is_primary' => false]);

        foreach ($rows as $row) {
            $cost = isset($row['id']) ? $costs->get((int) $row['id']) : $product->factoryCosts()->make();
            $cost->fill(Arr::only($row, ['factory_name', 'factory_cost', 'currency', 'effective_date', 'remarks']));
            $cost->is_primary = false;
            $cost->save();
            $costs->put($cost->getKey(), $cost);
            if ($row['is_primary'] ?? false) {
                $primaryId = $cost->getKey();
            }
        }

        $primaryId ??= $costs->first()?->getKey();
        if ($primaryId !== null) {
            $product->factoryCosts()->whereKey($primaryId)->update(['is_primary' => true]);
        }
    }
}
