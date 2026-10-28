<?php

namespace App\Services;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class BillingOperationalSyncService
{
    private const BILLING_STATUSES = ['for_billing', 'billed'];

    private const INVALID_DELIVERY_STATUSES = ['cancelled', 'canceled', 'void', 'voided', 'deleted', 'rejected'];

    /**
     * Advance all eligible billing deliveries for one project in one transaction.
     *
     * @return array{synced_dr_count: int, skipped_dr_count: int, message: string}
     */
    public function syncProject(int $projectId, int $userId, string $userName): array
    {
        return DB::transaction(function () use ($projectId, $userId, $userName): array {
            $project = DB::table('projects')->where('project_id', $projectId)->lockForUpdate()->first();
            if ($project === null) {
                throw new RuntimeException('The selected project could not be found.');
            }

            $eligibleBillingRows = DB::table('delivery_billing_records as billing')
                ->join('deliveries as d', 'd.delivery_id', '=', 'billing.delivery_id')
                ->where('d.project_id', $projectId)
                ->whereIn('billing.status', self::BILLING_STATUSES)
                ->whereNotNull('d.dr_no')
                ->whereRaw("TRIM(d.dr_no) <> ''")
                ->whereRaw("LOWER(TRIM(COALESCE(d.status, ''))) NOT IN ('cancelled', 'canceled', 'void', 'voided', 'deleted', 'rejected')")
                ->select('d.delivery_id')
                ->orderBy('d.delivery_id')
                ->lockForUpdate()
                ->get();

            $eligibleDeliveryIds = $eligibleBillingRows->pluck('delivery_id')->map(fn ($id): int => (int) $id)->unique()->values();
            if ($eligibleDeliveryIds->isEmpty()) {
                return [
                    'synced_dr_count' => 0,
                    'skipped_dr_count' => 0,
                    'message' => 'No eligible billing DRs were found for this project.',
                ];
            }

            $deliveries = DB::table('deliveries as d')
                ->leftJoin('logistics_location as location', 'location.logistics_location_id', '=', 'd.logistics_location_id')
                ->whereIn('d.delivery_id', $eligibleDeliveryIds)
                ->where('d.project_id', $projectId)
                ->whereNotExists(function (Builder $invalidDeliveries) use ($projectId): void {
                    $invalidDeliveries->selectRaw('1')
                        ->from('deliveries as invalid')
                        ->where('invalid.project_id', $projectId)
                        ->whereColumn('invalid.project_id', 'd.project_id')
                        ->whereNotNull('invalid.dr_no')
                        ->whereRaw("LOWER(TRIM(COALESCE(invalid.status, ''))) IN ('cancelled', 'canceled', 'void', 'voided', 'deleted', 'rejected')")
                        ->where(function (Builder $sameDr): void {
                            if (in_array(DB::connection()->getDriverName(), ['mysql', 'mariadb'], true)) {
                                $sameDr->whereRaw('BINARY invalid.dr_no = BINARY d.dr_no AND OCTET_LENGTH(invalid.dr_no) = OCTET_LENGTH(d.dr_no)');
                            } else {
                                $sameDr->whereColumn('invalid.dr_no', 'd.dr_no');
                            }
                        });
                })
                ->whereRaw("LOWER(TRIM(COALESCE(d.status, ''))) NOT IN ('cancelled', 'canceled', 'void', 'voided', 'deleted', 'rejected')")
                ->select(['d.delivery_id', 'd.project_id', 'd.dr_no', 'd.package_qty', 'location.warehouse_id'])
                ->orderBy('d.delivery_id')
                ->lockForUpdate()
                ->get()
                ->keyBy('delivery_id');

            if ($deliveries->isEmpty()) {
                return [
                    'synced_dr_count' => 0,
                    'skipped_dr_count' => 0,
                    'message' => 'No eligible billing DRs were found. Cancelled or voided DRs were skipped.',
                ];
            }

            $deliveryIds = $deliveries->keys()->map(fn ($id): int => (int) $id)->values();
            $keystageAllocations = DB::table('deliveries as d')
                ->join('package as pk', 'pk.keystage_id', '=', 'd.keystage_id')
                ->whereIn('d.delivery_id', $deliveryIds)
                ->whereNotNull('d.keystage_id')
                ->select(['d.delivery_id', 'pk.package_id']);
            $lotAllocations = DB::table('deliveries as d')
                ->join('package as pk', 'pk.lot_id', '=', 'd.lot_id')
                ->whereIn('d.delivery_id', $deliveryIds)
                ->whereNull('d.keystage_id')
                ->select(['d.delivery_id', 'pk.package_id']);
            $allocations = DB::query()->fromSub($keystageAllocations->unionAll($lotAllocations), 'allocation')
                ->orderBy('allocation.delivery_id')
                ->orderBy('allocation.package_id')
                ->get();

            $allocatedDeliveryIds = $allocations->pluck('delivery_id')->map(fn ($id): int => (int) $id)->unique();
            $deliveriesWithoutPackages = $deliveryIds->diff($allocatedDeliveryIds);
            if ($deliveriesWithoutPackages->isNotEmpty()) {
                $drNumbers = $deliveriesWithoutPackages->map(fn (int $id): string => (string) $deliveries->get($id)->dr_no)->unique()->take(5)->implode(', ');
                throw new RuntimeException("Billing sync stopped because DR(s) {$drNumbers} have no package allocation.");
            }

            $packageIds = $allocations->pluck('package_id')->map(fn ($id): int => (int) $id)->unique()->values();
            $latestStatusIds = DB::table('package_status')
                ->whereIn('delivery_id', $deliveryIds)
                ->whereIn('package_id', $packageIds)
                ->select(['delivery_id', 'package_id'])
                ->selectRaw('MAX(package_status_id) as package_status_id')
                ->groupBy('delivery_id', 'package_id');
            $latestStatuses = DB::table('package_status as status')
                ->joinSub($latestStatusIds, 'latest', function ($join): void {
                    $join->on('latest.package_status_id', '=', 'status.package_status_id');
                })
                ->select(['status.delivery_id', 'status.package_id', 'status.status'])
                ->lockForUpdate()
                ->get()
                ->mapWithKeys(fn (object $status): array => [$this->packageKey((int) $status->delivery_id, (int) $status->package_id) => strtolower((string) $status->status)]);

            $packageContents = DB::table('package_content as content')
                ->leftJoin('item as item', 'item.item_id', '=', 'content.item_id')
                ->whereIn('content.package_id', $packageIds)
                ->select(['content.package_id', 'content.item_id', 'content.qty', 'item.item_id as existing_item_id'])
                ->orderBy('content.package_id')
                ->orderBy('content.item_id')
                ->get()
                ->groupBy('package_id');

            $stockOutLines = [];
            $verificationLines = [];
            $packagesToAdvance = [];
            $syncedDrKeys = [];
            $allDrKeys = [];

            foreach ($deliveries as $delivery) {
                $deliveryPackages = $allocations->where('delivery_id', $delivery->delivery_id);
                $hasPendingPackage = false;
                $drKey = $this->drKey((int) $delivery->project_id, (string) $delivery->dr_no);
                $allDrKeys[$drKey] = true;

                foreach ($deliveryPackages as $allocation) {
                    $packageId = (int) $allocation->package_id;
                    $currentStatus = $latestStatuses->get($this->packageKey((int) $delivery->delivery_id, $packageId));
                    if (in_array($currentStatus, ['delivered', 'accepted'], true)) {
                        continue;
                    }

                    $contents = $packageContents->get($packageId, collect());
                    if ($contents->isEmpty()) {
                        throw new RuntimeException("Billing sync stopped because package {$packageId} has no item contents.");
                    }

                    foreach ($contents as $content) {
                        if ($content->existing_item_id === null) {
                            throw new RuntimeException("Billing sync stopped because item {$content->item_id} for package {$packageId} was not found.");
                        }
                    }

                    $packagesToAdvance[] = [
                        'delivery_id' => (int) $delivery->delivery_id,
                        'package_id' => $packageId,
                        'current_status' => $currentStatus,
                        'dr_no' => (string) $delivery->dr_no,
                    ];
                    $hasPendingPackage = true;

                    if ($currentStatus === 'released') {
                        foreach ($contents as $content) {
                            $verificationLines[] = [
                                'delivery_id' => (int) $delivery->delivery_id,
                                'package_id' => $packageId,
                                'item_id' => (int) $content->item_id,
                                'warehouse_id' => $delivery->warehouse_id === null ? null : (int) $delivery->warehouse_id,
                                'dr_no' => (string) $delivery->dr_no,
                            ];
                        }

                        continue;
                    }

                    if ($delivery->warehouse_id === null) {
                        throw new RuntimeException("Billing sync stopped because DR {$delivery->dr_no} is not assigned to a warehouse.");
                    }

                    $packageQuantity = (int) ($delivery->package_qty ?? 0);
                    if ($packageQuantity <= 0) {
                        throw new RuntimeException("Billing sync stopped because DR {$delivery->dr_no} has no valid package quantity.");
                    }

                    foreach ($contents as $content) {
                        $contentQuantity = (int) ($content->qty ?? 0);
                        if ($contentQuantity <= 0) {
                            $contentQuantity = 1;
                        }
                        $stockOutLines[] = [
                            'delivery_id' => (int) $delivery->delivery_id,
                            'project_id' => (int) $delivery->project_id,
                            'package_id' => $packageId,
                            'item_id' => (int) $content->item_id,
                            'warehouse_id' => (int) $delivery->warehouse_id,
                            'quantity' => $contentQuantity * $packageQuantity,
                            'content_quantity' => $contentQuantity,
                            'package_quantity' => $packageQuantity,
                            'dr_no' => (string) $delivery->dr_no,
                        ];
                    }
                }

                if ($hasPendingPackage) {
                    $syncedDrKeys[$drKey] = true;
                }
            }

            $batchNo = 'BILLING-SYNC-'.Str::uuid();
            $now = now();
            $this->deductInventory($stockOutLines, $batchNo, $now, $userName);
            $this->recordVerifiedStockOut($verificationLines, (int) $project->project_id, $batchNo, $now, $userName);

            foreach ($packagesToAdvance as $package) {
                if ($package['current_status'] !== 'released') {
                    DB::table('package_status')->insert([
                        'delivery_id' => $package['delivery_id'],
                        'package_id' => $package['package_id'],
                        'status' => 'released',
                        'remarks' => 'Stock Out completed by Sync Billing.',
                    ]);
                }

                $packageStatusId = DB::table('package_status')->insertGetId([
                    'delivery_id' => $package['delivery_id'],
                    'package_id' => $package['package_id'],
                    'status' => 'delivered',
                    'remarks' => 'Delivered by Sync Billing.',
                    'delivered_at' => $now,
                    'delivered_by' => $userId,
                    'receiver_name' => $userName,
                ], 'package_status_id');

                DB::table('delivery_history')->insert([
                    'package_status_id' => $packageStatusId,
                    'user_id' => $userId,
                    'status' => 'delivered',
                    'remarks' => 'Delivered by Sync Billing.',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            $syncedCount = count($syncedDrKeys);
            $skippedCount = max(count($allDrKeys) - $syncedCount, 0);
            $message = $syncedCount > 0
                ? "Synced {$syncedCount} billing DR(s). Inventory and delivery stages were updated."
                : 'No eligible billing DRs needed syncing. Inventory was not changed.';

            return [
                'synced_dr_count' => $syncedCount,
                'skipped_dr_count' => $skippedCount,
                'message' => $message,
            ];
        }, 3);
    }

    /** @param array<int, array<string, int|string>> $stockOutLines */
    private function deductInventory(array $stockOutLines, string $batchNo, mixed $changedAt, string $changedBy): void
    {
        $requiredByStock = [];
        foreach ($stockOutLines as $line) {
            $key = $this->inventoryKey($line['warehouse_id'], $line['item_id']);
            $requiredByStock[$key]['warehouse_id'] = $line['warehouse_id'];
            $requiredByStock[$key]['item_id'] = $line['item_id'];
            $requiredByStock[$key]['quantity'] = ($requiredByStock[$key]['quantity'] ?? 0) + $line['quantity'];
            $requiredByStock[$key]['lines'][] = $line;
        }

        if ($requiredByStock === []) {
            return;
        }

        $warehouseIds = collect($requiredByStock)->pluck('warehouse_id')->unique()->sort()->values();
        $itemIds = collect($requiredByStock)->pluck('item_id')->unique()->sort()->values();
        $inventoryRows = DB::table('inventory')
            ->whereIn('warehouse_id', $warehouseIds)
            ->whereIn('item_id', $itemIds)
            ->orderBy('warehouse_id')
            ->orderBy('item_id')
            ->orderBy('inventory_id')
            ->lockForUpdate()
            ->get()
            ->groupBy(fn (object $inventory): string => $this->inventoryKey((int) $inventory->warehouse_id, (int) $inventory->item_id));

        foreach ($requiredByStock as $key => $requirement) {
            $inventory = $inventoryRows->get($key)?->first();
            $available = (int) ($inventory->qty ?? 0);
            if ($inventory === null || $available < $requirement['quantity']) {
                throw new RuntimeException("Insufficient inventory for item {$requirement['item_id']} in warehouse {$requirement['warehouse_id']}. Required: {$requirement['quantity']}; available: {$available}.");
            }

            $runningQuantity = $available;
            foreach ($requirement['lines'] as $line) {
                $nextQuantity = $runningQuantity - $line['quantity'];
                DB::table('inventory_history')->insert([
                    'batch_no' => $batchNo,
                    'inventory_id' => $inventory->inventory_id,
                    'item_id' => $line['item_id'],
                    'warehouse_id' => $line['warehouse_id'],
                    'old_qty' => $runningQuantity,
                    'new_qty' => $nextQuantity,
                    'changed_by' => $changedBy,
                    'remarks' => "Sync Billing stock out: content quantity {$line['content_quantity']} x package quantity {$line['package_quantity']}.",
                    'change_type' => 'stock_out',
                    'changed_at' => $changedAt,
                    'project_id' => $line['project_id'],
                    'delivery_id' => $line['delivery_id'],
                    'package_id' => $line['package_id'],
                    'dr_no' => $line['dr_no'],
                    'quantity' => $line['quantity'],
                ]);
                $runningQuantity = $nextQuantity;
            }

            DB::table('inventory')->where('inventory_id', $inventory->inventory_id)->update([
                'qty' => $runningQuantity,
                'inventory_status' => 'Approved',
            ]);
        }
    }

    /** @param array<int, array<string, int|string|null>> $lines */
    private function recordVerifiedStockOut(array $lines, int $projectId, string $batchNo, mixed $changedAt, string $changedBy): void
    {
        foreach ($lines as $line) {
            DB::table('inventory_history')->insert([
                'batch_no' => $batchNo,
                'inventory_id' => null,
                'item_id' => $line['item_id'],
                'warehouse_id' => $line['warehouse_id'],
                'old_qty' => null,
                'new_qty' => null,
                'changed_by' => $changedBy,
                'remarks' => 'Sync Billing verified the prior Stock Out; no additional inventory was deducted.',
                'change_type' => 'stock_out_verified',
                'changed_at' => $changedAt,
                'project_id' => $projectId,
                'delivery_id' => $line['delivery_id'],
                'package_id' => $line['package_id'],
                'dr_no' => $line['dr_no'],
                'quantity' => 0,
            ]);
        }
    }

    private function inventoryKey(int $warehouseId, int $itemId): string
    {
        return $warehouseId.'|'.$itemId;
    }

    private function packageKey(int $deliveryId, int $packageId): string
    {
        return $deliveryId.'|'.$packageId;
    }

    private function drKey(int $projectId, string $drNo): string
    {
        return $projectId.'|'.strlen($drNo).'|'.$drNo;
    }
}
