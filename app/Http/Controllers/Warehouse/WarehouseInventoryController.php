<?php

namespace App\Http\Controllers\Warehouse;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Inventory;
use App\Models\InventoryHistory;
use App\Models\PackageStatus;
use App\Models\Project;
use App\Models\Lot;
use App\Models\Delivery;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Warehouse;

class WarehouseInventoryController extends Controller
{
    // ==========================================================
    // Existing single-scan-immediate-save endpoint (kept as-is,
    // in case anything else still calls it directly).
    // ==========================================================

    public function scanner()
    {
        $warehouses = Warehouse::orderBy('warehouse_name')->get();
        return view('operation.warehouse.stock_out', [
            'warehouses' => $warehouses,
        ]);
    }

    public function dashboard()
    {
        $pendingCount = Project::where('status', 'Pending')->count();
        $stockInCount = InventoryHistory::where('change_type', 'stock_in')->count();
        $stockOutCount = InventoryHistory::where('change_type', 'stock_out')->count();
        $deliveredCount = PackageStatus::where('status', 'delivered')->count();

        return view('operation.warehouse.dashboard', compact(
            'pendingCount',
            'stockInCount',
            'stockOutCount',
            'deliveredCount'
        ));
    }

    public function stockInIndex()
    {
        $projects = Project::select('project_id', 'project_name')
            ->orderBy('project_name')
            ->get();

        $warehouses = Warehouse::orderBy('warehouse_name')->get();

        return view('operation.warehouse.stock-in.index', compact('projects', 'warehouses'));
    }

    public function getLotsForProject(Request $request)
    {
        $request->validate([
            'project_id' => 'required|integer|exists:projects,project_id',
        ]);

        $lots = Lot::where('project_id', $request->project_id)
            ->select('lot_id', 'lot_name')
            ->orderBy('lot_name')
            ->get();

        return response()->json($lots);
    }

    public function getDeliveriesForLot(Request $request)
    {
        $request->validate([
            'lot_id' => 'required|integer|exists:lot,lot_id',
        ]);

        $deliveries = Delivery::where('lot_id', $request->lot_id)
            ->select('delivery_id')
            ->orderBy('delivery_id')
            ->get();

        return response()->json($deliveries);
    }

        public function getDeliveryItems(Request $request)
        {
            try {

                $request->validate([
                    'lot_id' => 'required|integer|exists:lot,lot_id',
                ]);

                $deliveries = Delivery::with([
                    'project',
                    'lot',
                    'school',
                    'packageStatuses.package.packageContent.item'
                ])
                ->where('lot_id', $request->lot_id)
                ->where('status', 'pending')
                ->get();

                if ($deliveries->isEmpty()) {
                    return response()->json([
                        'delivery_id'   => null,
                        'project'       => '',
                        'lot'           => '',
                        'school'        => '',
                        'delivery_date' => '',
                        'items'         => [],
                    ]);
                }

                $firstDelivery = $deliveries->first();

                // Total quantities for the whole lot
                $totalPackageQty = $deliveries->sum('package_qty');
                $totalTeacherQty = $deliveries->sum('qty_teachers_manual');

                $items = [];

                foreach ($deliveries as $delivery) {

                    foreach ($delivery->packageStatuses as $status) {

                        foreach ($status->package?->packageContent ?? [] as $content) {

                            $itemId = $content->item_id;

                            if (isset($items[$itemId])) {
                                continue;
                            }

                            $itemName = strtolower($content->item?->item_name ?? '');

                            // Teacher Manual
                            if (
                                str_contains($itemName, 'teacher') ||
                                str_contains($itemName, 'manual')
                            ) {
                                $qty = $totalTeacherQty;
                            } else {
                                $qty = $totalPackageQty;
                            }

                            $items[$itemId] = [
                                'item_id'   => $itemId,
                                'item_name' => $content->item?->item_name ?? 'Unnamed Item',
                                'unit'      => $content->item?->unit ?? '',
                                'qty'       => (int) $qty,
                            ];
                        }
                    }
                }

                return response()->json([
                    'delivery_id'   => null, // Per LOT, not per DR
                    'project'       => $firstDelivery->project->project_name ?? '',
                    'lot'           => $firstDelivery->lot->lot_name ?? '',
                    'school'        => '',
                    'delivery_date' => '',
                    'items'         => array_values($items),
                ]);

            } catch (\Throwable $e) {

                Log::error('getDeliveryItems failed', [
                    'message' => $e->getMessage(),
                    'line'    => $e->getLine(),
                    'file'    => $e->getFile(),
                ]);

                return response()->json([
                    'error' => $e->getMessage(),
                    'line'  => $e->getLine(),
                    'file'  => $e->getFile(),
                ], 500);
            }
        }

    // ==========================================================
    // NEW: validate a single QR — lookup only, NO writes.
    // Used while the user is scanning, before they hit "Save".
    // ==========================================================
    public function validateScan(Request $request)
    {
        $request->validate([
            'qr'           => 'required|string',
            'warehouse_id' => 'required|integer',
        ]);

        // Extract package_status_id from QR
        $packageStatusId = $this->extractPackageStatusId($request->qr);

        if (!$packageStatusId) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid QR code.'
            ]);
        }


        // Load package + contents + item + delivery
        $status = PackageStatus::with([
            'delivery',
            'package.contents.item'
        ])->find($packageStatusId);


        if (!$status) {
            return response()->json([
                'success' => false,
                'message' => 'Package not found.'
            ]);
        }


        /*
        |--------------------------------------------------------------------------
        | STOCK OUT VALIDATION
        |--------------------------------------------------------------------------
        */

        // Package must already be received by warehouse
        if ($status->status !== 'warehouse') {

            return response()->json([
                'success'           => false,
                'package_status_id' => $status->package_status_id,
                'package_id'        => $status->package_id,
                'message'           => 'Package is not available for stock out.'
            ]);
        }


        /*
        |--------------------------------------------------------------------------
        | PACKAGE CONTENTS
        |--------------------------------------------------------------------------
        */

        $contents = $status->package?->contents ?? collect();


        if ($contents->isEmpty()) {

            return response()->json([
                'success' => false,
                'message' => 'Package has no contents defined.'
            ]);
        }


        $packageName = $status->package->package_num ?? null;


        if (!$packageName) {

            return response()->json([
                'success' => false,
                'message' => 'Package number not assigned.'
            ]);
        }


        $isSingleItem = $contents->count() === 1;


        $itemName = $isSingleItem
            ? ($contents->first()->item->item_name ?? null)
            : $contents->count() . ' items';


        if ($isSingleItem && !$itemName) {

            return response()->json([
                'success' => false,
                'message' => 'Item record exists but has no name.'
            ]);
        }


        /*
        |--------------------------------------------------------------------------
        | SUCCESS RESPONSE
        |--------------------------------------------------------------------------
        */

        return response()->json([

            'success'           => true,

            'package_status_id' => $status->package_status_id,

            'package_id'        => $status->package_id,

            'delivery_id'       => $status->delivery_id,

            'dr_no'             => $status->delivery->dr_no ?? null,

            'package_name'      => 'Package #' . $packageName,

            'item'              => $itemName,

            'item_id'           => $isSingleItem
                                    ? $contents->first()->item_id
                                    : null,

            'qty'               => $contents->sum('qty'),

        ]);
    }

    // ==========================================================
    // NEW: batch save — persists everything staged in the browser.
    // Re-validates each package server-side before writing, so a
    // stale client-side staged list can't corrupt inventory.
    // ==========================================================
    public function saveScan(Request $request)
    {
        Log::info('SAVE REQUEST', $request->all());
        $request->validate([
            'warehouse_id'              => 'required|exists:warehouse,warehouse_id',
            'transaction'               => 'required|in:IN,OUT',
            'items'                     => 'required|array|min:1',
            'items.*.package_status_id' => 'required|integer',
        ]);

        $batchNo = 'BATCH-' . now()->format('YmdHis');

        $results = [
            'saved'  => [],
            'failed' => [],
        ];

        foreach ($request->items as $item) {

            try {

                DB::transaction(function () use (
                    $item,
                    $request,
                    &$results,
                    $batchNo
                ) {

                    $status = PackageStatus::with('package.contents')
                        ->findOrFail($item['package_status_id']);

                    if (!$status->package) {
                        throw new \Exception('Package not found.');
                    }

                    if ($status->package->contents->isEmpty()) {
                        throw new \Exception('Package has no contents.');
                    }

                    if ($request->transaction === 'IN') {

                        if ($status->status === 'warehouse') {
                            throw new \RuntimeException(
                                'Already received in warehouse.'
                            );
                        }

                    } else {

                        if ($status->status !== 'warehouse') {
                            throw new \RuntimeException(
                                'Package is not inside warehouse.'
                            );
                        }
                    }

                    foreach ($status->package->contents as $content) {
                        Log::info('PACKAGE CONTENT', [
                            'package_status_id' => $item['package_status_id'],
                            'item_id'           => $content->item_id,
                            'qty'               => $content->qty,
                        ]);

                        $inventory = Inventory::firstOrNew([
                            'warehouse_id' => $request->warehouse_id,
                            'item_id'      => $content->item_id,
                        ]);

                        $oldQty = $inventory->exists
                            ? $inventory->qty
                            : 0;

                        if ($request->transaction === 'IN') {

                            $newQty = $oldQty + $content->qty;

                        } else {

                            if ($oldQty < $content->qty) {

                                throw new \RuntimeException(
                                    "Insufficient stock for item {$content->item_id}"
                                );

                            }

                            $newQty = $oldQty - $content->qty;
                        }

                        /*
                        |--------------------------------------------------------------------------
                        | Prevent duplicate history from Model Observer
                        |--------------------------------------------------------------------------
                        */

                        $inventory->withoutEvents(function () use (
                            $inventory,
                            $newQty
                        ) {

                            $inventory->qty = $newQty;
                            $inventory->inventory_status = 'Approved';
                            $inventory->save();

                        });

                        /*
                        |--------------------------------------------------------------------------
                        | Single Batch History Record
                        |--------------------------------------------------------------------------
                        */

                        InventoryHistory::create([

                            'batch_no'     => $batchNo,

                            'inventory_id' => $inventory->inventory_id,

                            'item_id'      => $content->item_id,

                            'warehouse_id' => $request->warehouse_id,

                            'old_qty'      => $oldQty,

                            'new_qty'      => $newQty,

                            'changed_by'   => Auth::user()->name,

                            'remarks' => $request->transaction === 'IN'
                                ? 'Stock In via QR Scanner'
                                : 'Stock Out via QR Scanner',

                            'change_type' => $request->transaction === 'IN'
                                ? 'stock_in'
                                : 'stock_out',

                        ]);

                    }

                    if ($request->transaction === 'IN') {

                        $status->status = 'warehouse';
                        $status->remarks = 'Received by Warehouse';

                    } else {

                        $status->status = 'released';
                        $status->remarks = 'Released from Warehouse';

                    }

                    $status->save();

                    $results['saved'][] = $item['package_status_id'];

                });

            } catch (\Throwable $e) {

                Log::error('Warehouse Scan Error', [

                    'package_status_id' => $item['package_status_id'],

                    'message' => $e->getMessage(),

                    'line' => $e->getLine(),

                ]);

                $results['failed'][] = [

                    'package_status_id' => $item['package_status_id'],

                    'message' => $e->getMessage(),

                ];

            }

        }

        return response()->json([

            'success' => count($results['failed']) === 0,

            'message' => count($results['saved']) .
                ' saved, ' .
                count($results['failed']) .
                ' failed.',

            'batch_no' => $batchNo,

            'saved'   => $results['saved'],

            'failed'  => $results['failed'],

        ]);
    }

    // ==========================================================
    // NEW: manual (non-QR) stock-in save, used by the Delivery
    // Items table on the stock-in screen. Credits inventory for
    // exactly what was entered as "received", not the full
    // delivered amount, so short/partial receipts are recorded
    // accurately.
    //
    // ASSUMPTION (confirm / adjust to your process): package
    // statuses for the delivery are only flipped to 'warehouse'
    // if every item was received in full. If anything was
    // received short, package statuses are left as-is so the
    // shortage stays visible, and the shortfall is written into
    // the InventoryHistory remarks instead.
    // ==========================================================
    public function saveStockIn(Request $request)
    {
        Log::info('STOCK IN SAVE REQUEST', $request->all());

        $request->validate([
            'lot_id'       => 'required|integer|exists:lot,lot_id',
            'warehouse_id' => 'required|exists:warehouse,warehouse_id',
            'items'        => 'required|array|min:1',
            'items.*.item_id'      => 'required|integer|exists:items,item_id',
            'items.*.received_qty' => 'required|integer|min:0',
            'items.*.remarks'      => 'nullable|string',
        ]);

        $deliveries = Delivery::where('lot_id', $request->lot_id)
            ->where('status', 'pending')
            ->get();

        if ($deliveries->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No pending deliveries found for this lot.'
            ], 404);
        }

        $totalPackageQty = $deliveries->sum('package_qty');
        $totalTeacherQty = $deliveries->sum('qty_teachers_manual');

        $deliveredTotals = [];

        foreach ($request->items as $item) {

            $itemId = $item['item_id'];

            // Replace these IDs with your actual Teacher Manual item IDs
            if ($itemId == 756) {
                $deliveredTotals[$itemId] = $totalTeacherQty;
            } else {
                $deliveredTotals[$itemId] = $totalPackageQty;
            }
        }

        $batchNo = 'STOCKIN-' . now()->format('YmdHis');
        $results = ['saved' => [], 'failed' => []];
        $fullyReceived = true;

        foreach ($request->items as $item) {

            $itemId      = $item['item_id'];
            $receivedQty = (int) $item['received_qty'];
            $delivered   = $deliveredTotals[$itemId] ?? 0;
            $remarks     = trim($item['remarks'] ?? '');

            if ($receivedQty < $delivered) {
                $fullyReceived = false;
            }

            try {

                DB::transaction(function () use (
                    $itemId,
                    $receivedQty,
                    $delivered,
                    $remarks,
                    $request,
                    $batchNo,
                    &$results
                ) {

                    if ($receivedQty === 0) {
                        // Nothing to credit — skip inventory write but still record the entry.
                        $results['saved'][] = $itemId;
                        return;
                    }

                    $inventory = Inventory::firstOrNew([
                        'warehouse_id' => $request->warehouse_id,
                        'item_id'      => $itemId,
                    ]);

                    $oldQty = $inventory->exists ? $inventory->qty : 0;
                    $newQty = $oldQty + $receivedQty;

                    $inventory->withoutEvents(function () use ($inventory, $newQty) {
                        $inventory->qty = $newQty;
                        $inventory->inventory_status = 'Approved';
                        $inventory->save();
                    });

                    $shortfallNote = $receivedQty < $delivered
                        ? sprintf(' (short by %d)', $delivered - $receivedQty)
                        : '';

                    InventoryHistory::create([
                        'batch_no'     => $batchNo,
                        'inventory_id' => $inventory->inventory_id,
                        'item_id'      => $itemId,
                        'warehouse_id' => $request->warehouse_id,
                        'old_qty'      => $oldQty,
                        'new_qty'      => $newQty,
                        'changed_by'   => Auth::user()->name,
                        'remarks'      => trim('Stock In via Delivery Receipt' . $shortfallNote . ($remarks ? " — {$remarks}" : '')),
                        'change_type'  => 'stock_in',
                    ]);

                    $results['saved'][] = $itemId;
                });

            } catch (\Throwable $e) {

                Log::error('Stock In Save Error', [
                    'item_id' => $itemId,
                    'message' => $e->getMessage(),
                    'line'    => $e->getLine(),
                ]);

                $results['failed'][] = [
                    'item_id' => $itemId,
                    'message' => $e->getMessage(),
                ];
            }
        }

        if ($fullyReceived && count($results['failed']) === 0) {

            // Update all package statuses for this lot
            PackageStatus::whereIn(
                'delivery_id',
                Delivery::where('lot_id', $request->lot_id)
                    ->where('status', 'pending')
                    ->pluck('delivery_id')
            )->update([
                'status'  => 'warehouse',
                'remarks' => 'Received by Warehouse (Lot Stock In)',
            ]);

            // Update all deliveries for this lot
            Delivery::where('lot_id', $request->lot_id)
                ->where('status', 'pending')
                ->update([
                    'status' => 'warehouse',
                ]);
        }

        return response()->json([
            'success' => count($results['failed']) === 0,
            'message' => count($results['saved']) . ' item(s) saved, ' . count($results['failed']) . ' failed.'
                . ($fullyReceived ? '' : ' Some items were received short — delivery left open.'),
            'batch_no' => $batchNo,
            'fully_received' => $fullyReceived,
            'saved'   => $results['saved'],
            'failed'  => $results['failed'],
        ]);
    }

    // ==========================================================
    // Helper: pull package_status_id out of the scanned QR value.
    // QR encodes a URL like https://.../?id=123, or a bare numeric ID.
    // ==========================================================
    private function extractPackageStatusId(string $qr): ?int
    {
        if (ctype_digit($qr)) {
            return (int) $qr;
        }

        $parts = parse_url($qr);
        if (!isset($parts['query'])) {
            return null;
        }

        parse_str($parts['query'], $query);

        return isset($query['id']) ? (int) $query['id'] : null;
    }
}