<?php

namespace App\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class LogisticsDeliveryCompletionService
{
    /** @return array{status: string, details: string} */
    public function complete(object $event, int $projectId, string $schoolId): array
    {
        $deliveryQuery = DB::table('deliveries as d')
            ->leftJoin('logistics_location as location', 'location.logistics_location_id', '=', 'd.logistics_location_id')
            ->where('d.project_id', $projectId)
            ->where('d.school_id', $schoolId)
            ->select(['d.delivery_id', 'd.project_id', 'd.dr_no', 'd.package_qty', 'd.keystage_id', 'd.lot_id', 'location.warehouse_id'])
            ->orderBy('d.delivery_id')->lockForUpdate();
        if (in_array(DB::connection()->getDriverName(), ['mysql', 'mariadb'], true)) {
            $deliveryQuery->whereRaw('BINARY d.dr_no = BINARY ?', [$event->dr_reference]);
        } else {
            $deliveryQuery->whereRaw('d.dr_no = ? COLLATE BINARY', [$event->dr_reference]);
        }
        $deliveries = $deliveryQuery->get();

        if ($deliveries->isEmpty()) {
            return ['status' => 'unknown', 'details' => 'No delivery rows remain in the resolved DR scope.'];
        }

        $allocations = [];
        foreach ($deliveries as $delivery) {
            $packages = DB::table('package')->when($delivery->keystage_id !== null,
                fn ($query) => $query->where('keystage_id', $delivery->keystage_id),
                fn ($query) => $query->where('lot_id', $delivery->lot_id))
                ->orderBy('package_id')->get(['package_id']);
            foreach ($packages as $package) {
                $allocations[] = ['delivery' => $delivery, 'package_id' => (int) $package->package_id];
            }
        }

        if ($allocations === []) {
            return ['status' => 'ambiguous', 'details' => 'The DR does not identify any package allocations.'];
        }

        $packageStatuses = [];
        foreach ($allocations as $allocation) {
            $status = DB::table('package_status')
                ->where('delivery_id', $allocation['delivery']->delivery_id)
                ->where('package_id', $allocation['package_id'])
                ->orderByDesc('package_status_id')->lockForUpdate()->first();
            if ($status === null) {
                return ['status' => 'ambiguous', 'details' => "Package {$allocation['package_id']} has no status row for this DR."];
            }
            $packageStatuses[] = [...$allocation, 'status' => strtolower((string) $status->status), 'status_id' => (int) $status->package_status_id];
        }

        $lines = [];
        $contentsByPackage = [];
        foreach ($packageStatuses as $packageStatus) {
            if (in_array($packageStatus['status'], ['delivered', 'accepted'], true)) {
                continue;
            }
            $delivery = $packageStatus['delivery'];
            if ($delivery->warehouse_id === null) {
                return ['status' => 'unknown', 'details' => "DR {$delivery->dr_no} has no warehouse assigned through its logistics location."];
            }
            $packageQty = (int) ($delivery->package_qty ?? 0);
            if ($packageQty < 1) {
                return ['status' => 'unknown', 'details' => "DR {$delivery->dr_no} has no valid package quantity."];
            }
            $contents = $contentsByPackage[$packageStatus['package_id']] ??= DB::table('package_content')
                ->where('package_id', $packageStatus['package_id'])->orderBy('item_id')->get(['item_id', 'qty']);
            if ($contents->isEmpty()) {
                return ['status' => 'ambiguous', 'details' => "Package {$packageStatus['package_id']} has no item contents."];
            }
            foreach ($contents as $content) {
                $contentQty = (int) ($content->qty ?? 0);
                $contentQty = $contentQty > 0 ? $contentQty : 1;
                $lines[] = [
                    'delivery_id' => (int) $delivery->delivery_id, 'project_id' => (int) $delivery->project_id,
                    'package_id' => $packageStatus['package_id'], 'package_status_id' => $packageStatus['status_id'],
                    'status' => $packageStatus['status'], 'item_id' => (int) $content->item_id,
                    'warehouse_id' => (int) $delivery->warehouse_id, 'quantity' => $contentQty * $packageQty,
                    'content_qty' => $contentQty, 'package_qty' => $packageQty, 'dr_no' => (string) $delivery->dr_no,
                ];
            }
        }

        $required = [];
        foreach ($lines as $line) {
            if ($line['status'] === 'released') {
                continue;
            }
            $key = $line['warehouse_id'].':'.$line['item_id'];
            $required[$key]['warehouse_id'] = $line['warehouse_id'];
            $required[$key]['item_id'] = $line['item_id'];
            $required[$key]['quantity'] = ($required[$key]['quantity'] ?? 0) + ($line['status'] === 'released' ? 0 : $line['quantity']);
            $required[$key]['lines'][] = $line;
        }

        $inventory = [];
        foreach ($required as $key => $requirement) {
            $row = DB::table('inventory')->where('warehouse_id', $requirement['warehouse_id'])->where('item_id', $requirement['item_id'])
                ->orderBy('inventory_id')->lockForUpdate()->first();
            $available = (int) ($row->qty ?? 0);
            if ($row === null || $available < $requirement['quantity']) {
                return ['status' => 'insufficient_stock', 'details' => "Insufficient inventory for item {$requirement['item_id']} in warehouse {$requirement['warehouse_id']}. Required: {$requirement['quantity']}; available: {$available}."];
            }
            $inventory[$key] = $row;
        }

        $now = $event->event_occurred_at;
        $batchNo = 'LOGISTICS-'.hash('sha256', $event->external_event_id);
        $running = [];
        foreach ($required as $key => $requirement) {
            $running[$key] = (int) $inventory[$key]->qty;
        }
        foreach ($lines as $line) {
            $key = $line['warehouse_id'].':'.$line['item_id'];
            $row = $inventory[$key] ?? DB::table('inventory')->where('warehouse_id', $line['warehouse_id'])
                ->where('item_id', $line['item_id'])->orderBy('inventory_id')->lockForUpdate()->first();
            $oldQty = $line['status'] === 'released' ? null : $running[$key];
            $newQty = $line['status'] === 'released' ? null : $oldQty - $line['quantity'];
            DB::table('inventory_history')->insert([
                'batch_no' => $batchNo, 'inventory_id' => $row?->inventory_id, 'item_id' => $line['item_id'],
                'warehouse_id' => $line['warehouse_id'], 'old_qty' => $line['status'] === 'released' ? null : $oldQty,
                'new_qty' => $line['status'] === 'released' ? null : $newQty, 'changed_by' => 'Logistics Integration',
                'remarks' => 'Stock Out via Logistics integration; content quantity '.$line['content_qty'].' x package quantity '.$line['package_qty'].'.',
                'change_type' => $line['status'] === 'released' ? 'stock_out_verified' : 'stock_out', 'changed_at' => $now,
                'project_id' => $line['project_id'], 'delivery_id' => $line['delivery_id'], 'package_id' => $line['package_id'],
                'dr_no' => $line['dr_no'], 'quantity' => $line['status'] === 'released' ? 0 : $line['quantity'],
                'integration_id' => $event->integration_id, 'webhook_event_id' => $event->external_event_id,
            ]);
            if ($line['status'] !== 'released') {
                $running[$key] = $newQty;
            }
        }

        foreach ($running as $key => $quantity) {
            if ($quantity !== (int) $inventory[$key]->qty) {
                DB::table('inventory')->where('inventory_id', $inventory[$key]->inventory_id)->update(['qty' => $quantity, 'inventory_status' => 'Approved']);
            }
        }

        foreach ($packageStatuses as $packageStatus) {
            if (in_array($packageStatus['status'], ['delivered', 'accepted'], true)) {
                continue;
            }
            if ($packageStatus['status'] !== 'released') {
                DB::table('package_status')->where('package_status_id', $packageStatus['status_id'])->update(['status' => 'released', 'remarks' => 'Stock Out bypassed by Logistics integration.']);
            }
            $newStatusId = DB::table('package_status')->insertGetId([
                'delivery_id' => $packageStatus['delivery']->delivery_id, 'package_id' => $packageStatus['package_id'],
                'status' => 'delivered', 'remarks' => 'Delivered via authorized Logistics integration.',
                'delivered_at' => $now, 'delivered_by' => null, 'receiver_name' => 'Logistics Integration',
            ], 'package_status_id');
            DB::table('delivery_history')->insert([
                'package_status_id' => $newStatusId, 'user_id' => null, 'status' => 'delivered',
                'remarks' => 'Delivered via authorized Logistics integration; event '.$event->external_event_id,
                'created_at' => $now, 'updated_at' => $now,
            ]);
            DB::table('deliveries')->where('delivery_id', $packageStatus['delivery']->delivery_id)->update([
                'status' => 'delivered', 'delivered_date' => Carbon::parse($now)->toDateString(),
            ]);
        }

        return ['status' => 'accepted', 'details' => 'Delivery, Stock Out, inventory history, and package statuses synchronized from the authenticated Logistics event.'];
    }
}
