<?php

namespace App\Services;

use App\Models\PackageStatus;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class Sme2InventoryReconciliationService
{
    public function __construct(private BillingOperationalSyncService $stockOut) {}

    /**
     * @param  array<int, array<string, int>>  $legacyMappings
     * @return array<string, mixed>
     */
    public function reconcile(array $legacyMappings, bool $apply = false, ?string $expectedPreview = null, bool $replenishShortfalls = false, ?array $stockOutSchoolIds = null): array
    {
        return DB::transaction(function () use ($legacyMappings, $apply, $expectedPreview, $replenishShortfalls, $stockOutSchoolIds): array {
            $projectId = 502698;
            if (! DB::table('projects')->where('project_id', $projectId)->lockForUpdate()->first()) {
                throw new RuntimeException('SME2 project was not found.');
            }
            $deliveries = DB::table('deliveries as d')->leftJoin('school as s', 's.school_id', '=', 'd.school_id')
                ->leftJoin('logistics_location as l', 'l.logistics_location_id', '=', 'd.logistics_location_id')
                ->where('d.project_id', $projectId)->orderBy('d.delivery_id')
                ->select('d.*', 's.region as school_region', 'l.warehouse_id')->lockForUpdate()->get()->keyBy('delivery_id');
            $items = DB::table('item')->where('project_id', $projectId)->orderBy('item_id')->lockForUpdate()->get()->keyBy('item_id');
            $inventories = DB::table('inventory')->whereIn('item_id', $items->keys())->orderBy('inventory_id')->lockForUpdate()->get();
            $history = DB::table('inventory_history')->whereIn('item_id', $items->keys())->orderBy('history_id')->lockForUpdate()->get()->keyBy('history_id');
            $statuses = DB::table('package_status')->whereIn('delivery_id', $deliveries->keys())->orderBy('package_status_id')->lockForUpdate()->get()->keyBy('package_status_id');
            $latest = $statuses->groupBy(fn (object $s): string => $s->delivery_id.':'.$s->package_id)->map(fn ($rows) => $rows->last());
            $packages = DB::table('package')->orderBy('package_id')->get();
            $contents = DB::table('package_content')->whereIn('item_id', $items->keys())->orderBy('package_id')->orderBy('item_id')->lockForUpdate()->get()->groupBy('package_id');
            $mapped = [];
            foreach ($legacyMappings as $mapping) {
                $id = $mapping['history_id'];
                $row = $history->get($id);
                $status = $statuses->get($mapping['package_status_id']);
                if (isset($mapped[$id]) || $row === null || $row->delivery_id !== null || $row->change_type !== 'stock_out'
                    || $status === null || (int) $status->delivery_id !== $mapping['delivery_id'] || (int) $status->package_id !== $mapping['package_id']
                    || (int) $row->item_id !== $mapping['item_id'] || (int) $row->warehouse_id !== $mapping['warehouse_id']
                    || $row->old_qty === null || $row->new_qty === null || (int) $row->old_qty - (int) $row->new_qty !== $mapping['deducted_quantity']) {
                    throw new RuntimeException('Invalid or repeated recovered scanner mapping: '.$id);
                }
                $mapped[$id] = $mapping;
            }
            $deducted = [];
            $sourceWarehouses = [];
            foreach ($history as $row) {
                if ($row->change_type !== 'stock_out' || $row->old_qty === null || $row->new_qty === null || $row->old_qty <= $row->new_qty) {
                    continue;
                }
                $deliveryId = $row->delivery_id ?? ($mapped[$row->history_id]['delivery_id'] ?? null);
                $packageId = $row->package_id ?? ($mapped[$row->history_id]['package_id'] ?? null);
                if ($deliveryId === null || ! $deliveries->has($deliveryId)) {
                    throw new RuntimeException('Positive SME2 deduction has no validated delivery reference: '.$row->history_id);
                }
                $key = $deliveryId.':'.$packageId.':'.$row->item_id;
                $deducted[$key] = ($deducted[$key] ?? 0) + (int) $row->old_qty - (int) $row->new_qty;
                $sourceWarehouses[$deliveryId.':'.$packageId][(int) $row->warehouse_id] = true;
            }
            $schoolRows = [];
            $selectedSchools = [];
            if ($stockOutSchoolIds !== null) {
                $schoolDeliveries = $deliveries->groupBy('school_id');
                $activity = [];
                foreach ($latest as $status) {
                    if (in_array(strtolower((string) $status->status), ['released', 'delivered', 'accepted'], true)) {
                        $activity[(string) $deliveries->get($status->delivery_id)->school_id] = true;
                    }
                }
                foreach (array_keys($sourceWarehouses) as $packageKey) {
                    $deliveryId = (int) explode(':', $packageKey)[0];
                    $activity[(string) $deliveries->get($deliveryId)->school_id] = true;
                }
                foreach (array_values(array_unique(array_map('strval', $stockOutSchoolIds))) as $schoolId) {
                    $records = $schoolDeliveries->get($schoolId, collect());
                    $alreadyDelivered = $records->contains(fn (object $d): bool => in_array(strtolower((string) $d->status), ['delivered', 'accepted'], true));
                    $reason = $records->isEmpty() ? 'school_not_found_in_sme2' : ($alreadyDelivered ? 'skip_already_delivered' : (isset($activity[$schoolId]) ? 'skip_already_stock_out' : 'eligible_stock_out'));
                    $schoolRows[] = ['school_id' => $schoolId, 'delivery_rows' => $records->count(), 'reason' => $reason];
                    if ($reason === 'eligible_stock_out') {
                        $selectedSchools[$schoolId] = true;
                    }
                }
            }
            $routing = [];
            $rows = [];
            $lines = [];
            $releaseStatuses = [];
            $newReleasePackages = [];
            foreach ($deliveries as $delivery) {
                if ($stockOutSchoolIds !== null && ! isset($selectedSchools[(string) $delivery->school_id])) {
                    continue;
                }
                $allocation = $packages->filter(fn (object $p): bool => $delivery->keystage_id !== null
                    ? $p->keystage_id === $delivery->keystage_id : $p->lot_id === $delivery->lot_id);
                foreach ($allocation as $package) {
                    $packageKey = $delivery->delivery_id.':'.$package->package_id;
                    $status = $latest->get($packageKey);
                    $completed = in_array(strtolower((string) $delivery->status), ['delivered', 'accepted'], true)
                        || in_array(strtolower((string) ($status->status ?? '')), ['released', 'delivered', 'accepted'], true);
                    if ($stockOutSchoolIds === null && ! $completed && ! isset($sourceWarehouses[$packageKey])) {
                        continue;
                    }
                    $warehouseId = null;
                    $warehouseReason = null;
                    $knownWarehouses = array_keys($sourceWarehouses[$packageKey] ?? []);
                    try {
                        if (count($knownWarehouses) === 1) {
                            $warehouseId = $knownWarehouses[0];
                        } elseif (count($knownWarehouses) > 1) {
                            throw new RuntimeException('Package deductions refer to multiple warehouses.');
                        } else {
                            $routeKey = ($delivery->warehouse_id ?? 'none').':'.($delivery->school_region ?? '');
                            $warehouseId = $routing[$routeKey] ??= $this->stockOut->resolveWarehouseForDelivery($delivery);
                        }
                    } catch (RuntimeException $exception) {
                        $warehouseReason = $exception->getMessage();
                    }
                    $packageContents = $contents->get($package->package_id, collect());
                    $packageValid = $packageContents->isNotEmpty() && $warehouseId !== null && (int) $delivery->package_qty > 0;
                    if ($packageContents->isEmpty()) {
                        $rows[] = ['delivery_id' => (int) $delivery->delivery_id, 'package_id' => (int) $package->package_id, 'item_id' => null, 'reason' => 'missing_package_contents', 'proposed_quantity' => 0];
                    }
                    foreach ($packageContents as $content) {
                        $key = $packageKey.':'.$content->item_id;
                        $required = (int) $delivery->package_qty > 0 && (int) $content->qty > 0
                            ? (int) $delivery->package_qty * (int) $content->qty : null;
                        $packageValid = $packageValid && $required !== null;
                        $already = $deducted[$key] ?? 0;
                        $missing = $required === null ? 0 : max(0, $required - $already);
                        $reason = $required === null ? 'missing_item_or_package_quantity' : ($missing === 0 ? 'already_deducted' : ($warehouseId === null ? 'missing_warehouse_mapping: '.$warehouseReason : 'deduct_missing'));
                        $quantity = $reason === 'deduct_missing' ? $missing : 0;
                        $rows[] = ['delivery_id' => (int) $delivery->delivery_id, 'dr_no' => (string) $delivery->dr_no, 'school_id' => (string) $delivery->school_id,
                            'school_region' => $delivery->school_region, 'delivery_status' => $delivery->status, 'package_status' => $status->status ?? null,
                            'package_id' => (int) $package->package_id, 'item_id' => (int) $content->item_id, 'unit' => $items->get($content->item_id)->unit ?? '',
                            'warehouse_id' => $warehouseId, 'warehouse_basis' => $knownWarehouses ? 'existing_package_stock_out' : 'application_region_routing',
                            'required_quantity' => $required, 'already_deducted' => $already, 'proposed_quantity' => $quantity, 'reason' => $reason, 'remarks' => 'Bypass Encoding'];
                        if ($quantity > 0) {
                            $lines[] = ['project_id' => $projectId, 'delivery_id' => (int) $delivery->delivery_id, 'package_id' => (int) $package->package_id,
                                'item_id' => (int) $content->item_id, 'warehouse_id' => $warehouseId, 'quantity' => $quantity,
                                'content_quantity' => (int) $content->qty, 'package_quantity' => (int) $delivery->package_qty, 'dr_no' => (string) $delivery->dr_no];
                        }
                    }
                    if ($stockOutSchoolIds !== null && $packageValid) {
                        if ($status === null) {
                            $newReleasePackages[] = ['delivery_id' => (int) $delivery->delivery_id, 'package_id' => (int) $package->package_id];
                        } else {
                            $releaseStatuses[] = (int) $status->package_status_id;
                        }
                    }
                }
            }
            $requirements = [];
            foreach ($lines as $line) {
                $key = $line['warehouse_id'].':'.$line['item_id'];
                $requirements[$key] = ($requirements[$key] ?? 0) + $line['quantity'];
            }
            $shortages = [];
            foreach ($requirements as $key => $quantity) {
                [$warehouseId, $itemId] = array_map('intval', explode(':', $key));
                $stock = $inventories->first(fn (object $i): bool => (int) $i->item_id === $itemId && (int) $i->warehouse_id === $warehouseId);
                $available = (int) ($stock->qty ?? 0);
                if ($available < $quantity) {
                    $shortages[] = ['warehouse_id' => $warehouseId, 'item_id' => $itemId, 'required' => $quantity, 'available' => $available];
                }
            }
            $remainingStock = [];
            foreach ($inventories as $inventory) {
                $stockKey = $inventory->warehouse_id.':'.$inventory->item_id;
                $remainingStock[$stockKey] ??= (int) $inventory->qty;
            }
            $eligible = [];
            foreach ($rows as &$row) {
                if ($row['reason'] !== 'deduct_missing') {
                    continue;
                }
                $stockKey = $row['warehouse_id'].':'.$row['item_id'];
                $row['missing_quantity'] = $row['proposed_quantity'];
                if (! $replenishShortfalls && ($remainingStock[$stockKey] ?? 0) < $row['proposed_quantity']) {
                    $row['proposed_quantity'] = 0;
                    $row['reason'] = array_key_exists($stockKey, $remainingStock) ? 'blocked_insufficient_stock' : 'blocked_missing_inventory_balance';

                    continue;
                }
                $remainingStock[$stockKey] = ($remainingStock[$stockKey] ?? 0) - $row['proposed_quantity'];
                $eligible[$row['delivery_id'].':'.$row['package_id'].':'.$row['item_id']] = true;
            }
            unset($row);
            $lines = array_values(array_filter($lines, fn (array $line): bool => isset($eligible[$line['delivery_id'].':'.$line['package_id'].':'.$line['item_id']])));
            $fingerprint = hash('sha256', json_encode([$lines, $replenishShortfalls ? $shortages : [], $releaseStatuses, $schoolRows, $newReleasePackages], JSON_THROW_ON_ERROR));
            $batch = 'SME2-BYPASS-'.substr($fingerprint, 0, 32);
            if ($apply) {
                if ($expectedPreview === null || ! hash_equals($expectedPreview, $fingerprint)) {
                    throw new RuntimeException('Preview changed; regenerate it before applying.');
                }
                if ($replenishShortfalls) {
                    $this->stockOut->recordReconciliationStockIn($shortages, $batch);
                }
                $this->stockOut->recordReconciledStockOut($lines, $batch);
                foreach (array_chunk($releaseStatuses, 500) as $statusIds) {
                    DB::table('package_status')->whereIn('package_status_id', $statusIds)->update(['status' => 'released', 'remarks' => 'Bypass Encoding']);
                }
                foreach ($newReleasePackages as $package) {
                    PackageStatus::create($package + ['status' => 'released', 'remarks' => 'Bypass Encoding']);
                }
            }

            return ['rows' => $rows, 'lines' => $lines, 'shortages' => $shortages, 'applied' => $apply, 'batch_no' => $batch, 'fingerprint' => $fingerprint, 'schools' => $schoolRows, 'release_status_ids' => $releaseStatuses, 'new_release_packages' => $newReleasePackages];
        }, 3);
    }
}
