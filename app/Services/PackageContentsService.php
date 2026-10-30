<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class PackageContentsService
{
    public function rows(int $packageId): Collection
    {
        $itemColumns = array_values(array_intersect(
            ['item_name', 'description', 'item_description', 'unit'],
            Schema::getColumnListing('item'),
        ));

        return DB::table('package_content as content')
            ->leftJoin('item', 'item.item_id', '=', 'content.item_id')
            ->where('content.package_id', $packageId)
            ->select(['content.item_id', 'content.qty'])
            ->addSelect(array_map(fn (string $column): string => 'item.'.$column, $itemColumns))
            ->orderBy('item.item_name')
            ->orderBy('content.item_id')
            ->get();
    }

    /** @return array<int, int> */
    public function duplicateItemIds(int $packageId): array
    {
        return DB::table('package_content')
            ->where('package_id', $packageId)
            ->select('item_id')
            ->groupBy('item_id')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('item_id')
            ->map(fn (mixed $itemId): int => (int) $itemId)
            ->all();
    }

    public function quantityChangeBlockReason(int $packageId): ?string
    {
        $package = DB::table('package')->where('package_id', $packageId)->first(['lot_id', 'keystage_id']);
        if ($package === null) {
            abort(404);
        }

        $hasAssignedDelivery = ($package->keystage_id !== null || $package->lot_id !== null)
            && Schema::hasTable('deliveries')
            && DB::table('deliveries')->where(function ($query) use ($package): void {
                if ($package->keystage_id !== null) {
                    $query->where('keystage_id', $package->keystage_id);
                }
                if ($package->lot_id !== null) {
                    $query->orWhere(function ($lotDeliveries) use ($package): void {
                        $lotDeliveries->whereNull('keystage_id')->where('lot_id', $package->lot_id);
                    });
                }
            })->exists();

        if ($hasAssignedDelivery) {
            return 'This package is assigned to a delivery. Its item quantities cannot be changed because that would alter delivery, billing, or inventory requirements.';
        }

        if (Schema::hasTable('package_status') && DB::table('package_status')->where('package_id', $packageId)->exists()) {
            return 'This package already has delivery status records. Its item quantities cannot be changed because that would alter recorded delivery and billing requirements.';
        }

        if (Schema::hasTable('inventory_history') && Schema::hasColumn('inventory_history', 'package_id') && DB::table('inventory_history')->where('package_id', $packageId)->exists()) {
            return 'This package has inventory history. Its item quantities cannot be changed because that would make recorded stock movements inconsistent.';
        }

        return null;
    }

    /** @param array<int|string, int|string> $quantities */
    public function updateQuantities(int $packageId, array $quantities): void
    {
        DB::transaction(function () use ($packageId, $quantities): void {
            $lockedPackage = DB::table('package')->where('package_id', $packageId)->lockForUpdate()->first();
            abort_if($lockedPackage === null, 404);
            $updates = [];

            foreach ($quantities as $itemId => $quantity) {
                if (! ctype_digit((string) $itemId)) {
                    throw ValidationException::withMessages(['items' => 'A selected package item is invalid.']);
                }

                $rows = DB::table('package_content')
                    ->where('package_id', $packageId)
                    ->where('item_id', $itemId)
                    ->lockForUpdate()
                    ->get(['qty']);

                if ($rows->count() !== 1) {
                    throw ValidationException::withMessages([
                        'items.'.$itemId => 'This package has duplicate or missing quantity records for the item. Resolve the package contents before editing this quantity.',
                    ]);
                }

                if ((int) $rows->first()->qty !== (int) $quantity) {
                    $updates[$itemId] = $quantity;
                }
            }

            if ($updates === []) {
                return;
            }

            $reason = $this->quantityChangeBlockReason($packageId);
            if ($reason !== null) {
                throw ValidationException::withMessages(['items' => $reason]);
            }

            foreach ($updates as $itemId => $quantity) {
                DB::table('package_content')
                    ->where('package_id', $packageId)
                    ->where('item_id', $itemId)
                    ->update(['qty' => $quantity]);
            }
        }, 3);
    }
}
