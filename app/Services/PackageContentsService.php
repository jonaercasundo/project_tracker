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

        $relatedDeliveries = static function ($query) use ($package): void {
            $query->where(function ($scope) use ($package): void {
                if ($package->keystage_id !== null) {
                    $scope->where('keystage_id', $package->keystage_id);
                }
                if ($package->lot_id !== null) {
                    $scope->orWhere(function ($lotDeliveries) use ($package): void {
                        $lotDeliveries->whereNull('keystage_id')->where('lot_id', $package->lot_id);
                    });
                }
            });
        };

        if (Schema::hasTable('package_status') && DB::table('package_status')
            ->where('package_id', $packageId)
            ->whereRaw("LOWER(TRIM(COALESCE(status, 'pending'))) NOT IN ('pending', '')")
            ->exists()) {
            return 'This package has progressed beyond pending delivery. Its item quantities are locked to preserve recorded delivery and inventory activity.';
        }

        if (Schema::hasTable('deliveries') && Schema::hasColumn('deliveries', 'status')
            && ($package->keystage_id !== null || $package->lot_id !== null)
            && DB::table('deliveries')->where($relatedDeliveries)
                ->whereIn(DB::raw('LOWER(TRIM(COALESCE(status, \'\')))'), ['delivered', 'accepted'])
                ->exists()) {
            return 'A related delivery is already delivered or accepted. Its item quantities are locked to preserve delivery history.';
        }

        if (Schema::hasTable('delivery_billing_records') && Schema::hasTable('deliveries')
            && Schema::hasColumn('delivery_billing_records', 'status')
            && ($package->keystage_id !== null || $package->lot_id !== null)
            && DB::table('delivery_billing_records as billing')
                ->join('deliveries as delivery', 'delivery.delivery_id', '=', 'billing.delivery_id')
                ->where($relatedDeliveries)
                ->whereRaw("LOWER(TRIM(COALESCE(billing.status, 'pending_billing'))) <> 'pending_billing'")
                ->exists()) {
            return 'A related delivery has entered billing review or has billing history. Its item quantities are locked to preserve billing records.';
        }

        if (Schema::hasTable('inventory_history') && Schema::hasColumn('inventory_history', 'package_id') && DB::table('inventory_history')->where('package_id', $packageId)->exists()) {
            return 'This package has inventory history. Its item quantities cannot be changed because that would make recorded stock movements inconsistent.';
        }

        return null;
    }

    /** @param array<int|string, int|string> $quantities */
    public function updateQuantities(int $packageId, array $quantities, ?string $remarks, ?int $changedByUserId): void
    {
        DB::transaction(function () use ($packageId, $quantities, $remarks, $changedByUserId): void {
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

                $currentQuantity = $rows->first()->qty === null ? null : (int) $rows->first()->qty;
                if ($currentQuantity !== (int) $quantity) {
                    $updates[$itemId] = [
                        'old_qty' => $currentQuantity,
                        'new_qty' => (int) $quantity,
                    ];
                }
            }

            if ($updates === []) {
                return;
            }

            $reason = $this->quantityChangeBlockReason($packageId);
            if ($reason !== null && blank($remarks)) {
                throw ValidationException::withMessages(['remarks' => 'Explain why this quantity change is needed because this package has related delivery, billing, or inventory records.']);
            }

            if (! Schema::hasTable('package_content_quantity_changes')) {
                throw ValidationException::withMessages(['remarks' => 'Quantity change auditing is unavailable until the package quantity audit migration has been applied.']);
            }

            foreach ($updates as $itemId => $change) {
                DB::table('package_content')
                    ->where('package_id', $packageId)
                    ->where('item_id', $itemId)
                    ->update(['qty' => $change['new_qty']]);

                DB::table('package_content_quantity_changes')->insert([
                    'package_id' => $packageId,
                    'item_id' => (int) $itemId,
                    'old_qty' => $change['old_qty'],
                    'new_qty' => $change['new_qty'],
                    'remarks' => filled($remarks) ? trim($remarks) : 'Routine quantity adjustment; no related transaction history was present.',
                    'changed_by_user_id' => $changedByUserId,
                    'changed_at' => now(),
                ]);
            }
        }, 3);
    }
}
