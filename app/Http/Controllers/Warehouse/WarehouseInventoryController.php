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

            // Total quantity for the whole lot — uniform, no item-name branching.
            $totalPackageQty = $deliveries->sum('package_qty');

            $items = [];

            foreach ($deliveries as $delivery) {

                foreach ($delivery->packageStatuses as $status) {

                    foreach ($status->package?->packageContent ?? [] as $content) {

                        $itemId = $content->item_id;

                        if (isset($items[$itemId])) {
                            continue;
                        }

                        $items[$itemId] = [
                            'item_id'   => $itemId,
                            'item_name' => $content->item?->item_name ?? 'Unnamed Item',
                            'unit'      => $content->item?->unit ?? '',
                            'qty'       => (int) $totalPackageQty,
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
    try {

        $request->validate([
            'qr'           => 'required|string',
            'warehouse_id' => 'required|integer',
        ]);

        $packageStatusId = $this->extractPackageStatusId($request->qr);

        if (!$packageStatusId) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid QR code.'
            ]);
        }

        $status = PackageStatus::with([
            'package.contents.item',
            'delivery'
        ])->find($packageStatusId);

        if (!$status) {
            return response()->json([
                'success' => false,
                'message' => 'Package status not found.'
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | PACKAGE
        |--------------------------------------------------------------------------
        */

        if (!$status->package) {
            return response()->json([
                'success' => false,
                'message' => 'Package information not found.'
            ]);
        }

        $contents = $status->package->contents;

        if (!$contents || $contents->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Package has no item contents.'
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | DELIVERY
        |--------------------------------------------------------------------------
        */

        $delivery = $status->delivery;

        if (!$delivery) {
            return response()->json([
                'success' => false,
                'message' => 'Delivery record not found.'
            ]);
        }

        $packageQty = (int) ($delivery->package_qty ?? 0);

        if ($packageQty <= 0) {
            return response()->json([
                'success' => false,
                'message' => 'Package quantity is missing.'
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | CHECK ALL ITEMS IN PACKAGE
        |--------------------------------------------------------------------------
        */

        $items = [];

        foreach ($contents as $content) {

            if (!$content->item) {
                return response()->json([
                    'success' => false,
                    'message' => "Item information not found for item ID {$content->item_id}."
                ]);
            }

            $contentQty = (int) ($content->qty ?? 0);

            if ($contentQty <= 0) {
                return response()->json([
                    'success' => false,
                    'message' => "Content quantity is missing for item {$content->item_id}."
                ]);
            }

            /*
            |--------------------------------------------------------------------------
            | REQUIRED QUANTITY
            |--------------------------------------------------------------------------
            |
            | package_content.qty × deliveries.package_qty
            |
            */

            $requiredQty = $contentQty * $packageQty;

            /*
            |--------------------------------------------------------------------------
            | GET INVENTORY
            |--------------------------------------------------------------------------
            */

            $inventory = Inventory::where('warehouse_id', $request->warehouse_id)
                ->where('item_id', $content->item_id)
                ->first();

            $availableQty = $inventory
                ? (int) $inventory->qty
                : 0;

            /*
            |--------------------------------------------------------------------------
            | CHECK STOCK
            |--------------------------------------------------------------------------
            */

            if ($availableQty < $requiredQty) {

                return response()->json([
                    'success'           => false,
                    'message'           => "Insufficient stock for item {$content->item_id}. Available: {$availableQty}",
                    'package_status_id' => $status->package_status_id,
                    'item_id'           => $content->item_id,
                    'item'              => $content->item->item_name,
                    'available_qty'     => $availableQty,
                    'required_qty'      => $requiredQty,
                ]);
            }

            /*
            |--------------------------------------------------------------------------
            | ITEM IS AVAILABLE
            |--------------------------------------------------------------------------
            */

            $items[] = [
                'item_id'       => $content->item_id,
                'item'          => $content->item->item_name,
                'content_qty'   => $contentQty,
                'package_qty'   => $packageQty,
                'required_qty'  => $requiredQty,
                'available_qty' => $availableQty,
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | ALL ITEMS HAVE ENOUGH STOCK
        |--------------------------------------------------------------------------
        */

        return response()->json([
            'success'           => true,
            'package_status_id' => $status->package_status_id,
            'package_id'        => $status->package_id,
            'delivery_id'       => $status->delivery_id,
            'dr_no'             => $delivery->dr_no ?? null,
            'package_name'      => 'Package #' . $status->package->package_num,
            'package_qty'       => $packageQty,
            'items'             => $items,
        ]);

    } catch (\Throwable $e) {

        Log::error('validateScan failed', [
            'message' => $e->getMessage(),
            'line'    => $e->getLine(),
            'file'    => $e->getFile(),
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Unexpected error validating scan.',
            'debug'   => $e->getMessage(),
            'debug_line' => $e->getLine(),
        ], 500);
    }
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
            'warehouse_id'               => 'required|exists:warehouse,warehouse_id',
            'items'                      => 'required|array|min:1',
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

                    /*
                    |--------------------------------------------------------------------------
                    | LOAD PACKAGE STATUS
                    |--------------------------------------------------------------------------
                    */

                    $status = PackageStatus::with([
                        'package.contents.item',
                        'delivery'
                    ])->findOrFail($item['package_status_id']);

                    /*
                    |--------------------------------------------------------------------------
                    | PACKAGE VALIDATION
                    |--------------------------------------------------------------------------
                    */

                    if (!$status->package) {
                        throw new \RuntimeException(
                            'Package not found.'
                        );
                    }

                    if ($status->package->contents->isEmpty()) {
                        throw new \RuntimeException(
                            'Package has no contents.'
                        );
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | PACKAGE STATUS
                    |--------------------------------------------------------------------------
                    */

                    if ($status->status !== 'pending') {
                        throw new \RuntimeException(
                            'Package is not available in warehouse.'
                        );
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | DELIVERY
                    |--------------------------------------------------------------------------
                    */

                    $delivery = $status->delivery;

                    if (!$delivery) {
                        throw new \RuntimeException(
                            'Delivery record not found.'
                        );
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | DELIVERY PACKAGE QTY
                    |--------------------------------------------------------------------------
                    |
                    | Source:
                    | deliveries.package_qty
                    |
                    */

                    $packageQty = (int) ($delivery->package_qty ?? 0);

                    if ($packageQty <= 0) {
                        throw new \RuntimeException(
                            'Delivery package quantity is missing.'
                        );
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | PROCESS EACH PACKAGE CONTENT
                    |--------------------------------------------------------------------------
                    */

                    foreach ($status->package->contents as $content) {

                        /*
                        |--------------------------------------------------------------------------
                        | PACKAGE CONTENT QTY
                        |--------------------------------------------------------------------------
                        |
                        | Source:
                        | package_content.qty
                        |
                        */

                        $contentQty = (int) ($content->qty ?? 0);

                        if ($contentQty <= 0) {
                            $contentQty = 1;
                        }

                        /*
                        |--------------------------------------------------------------------------
                        | FINAL STOCK OUT QTY
                        |--------------------------------------------------------------------------
                        |
                        | package_content.qty × deliveries.package_qty
                        |
                        | Example:
                        |
                        | content qty = 2
                        | package qty  = 5
                        |
                        | stock out = 10
                        |
                        */

                        $stockOutQty = $contentQty * $packageQty;

                        Log::info('PACKAGE CONTENT STOCK OUT', [
                            'package_status_id' => $item['package_status_id'],
                            'delivery_id'       => $delivery->delivery_id,
                            'dr_no'             => $delivery->dr_no,
                            'item_id'           => $content->item_id,
                            'content_qty'       => $contentQty,
                            'package_qty'       => $packageQty,
                            'stock_out_qty'     => $stockOutQty,
                        ]);

                        /*
                        |--------------------------------------------------------------------------
                        | FIND INVENTORY
                        |--------------------------------------------------------------------------
                        */

                        $inventory = Inventory::firstOrNew([
                            'warehouse_id' => $request->warehouse_id,
                            'item_id'      => $content->item_id,
                        ]);

                        $oldQty = $inventory->exists
                            ? (int) $inventory->qty
                            : 0;

                        /*
                        |--------------------------------------------------------------------------
                        | CHECK STOCK
                        |--------------------------------------------------------------------------
                        */

                        if ($oldQty < $stockOutQty) {

                            throw new \RuntimeException(
                                "Insufficient stock for item {$content->item_id}. " .
                                "Available: {$oldQty}"
                            );
                        }

                        /*
                        |--------------------------------------------------------------------------
                        | STOCK OUT
                        |--------------------------------------------------------------------------
                        */

                        $newQty = $oldQty - $stockOutQty;

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
                        | INVENTORY HISTORY
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
                            'remarks'      =>
                                'Stock Out via QR Scanner - ' .
                                'Content Qty: ' . $contentQty .
                                ' × Package Qty: ' . $packageQty,
                            'change_type'  => 'stock_out',
                        ]);
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | RELEASE PACKAGE
                    |--------------------------------------------------------------------------
                    */

                    $status->status = 'released';
                    $status->remarks = 'Released from Warehouse';
                    $status->save();

                    /*
                    |--------------------------------------------------------------------------
                    | SUCCESS
                    |--------------------------------------------------------------------------
                    */

                    $results['saved'][] = $item['package_status_id'];
                });

            } catch (\Throwable $e) {

                Log::error('Warehouse Scan Error', [
                    'package_status_id' => $item['package_status_id'],
                    'message'           => $e->getMessage(),
                    'line'              => $e->getLine(),
                ]);

                $results['failed'][] = [
                    'package_status_id' => $item['package_status_id'],
                    'message'           => $e->getMessage(),
                ];
            }
        }

        /*
        |--------------------------------------------------------------------------
        | RESPONSE
        |--------------------------------------------------------------------------
        */

        return response()->json([
            'success' => count($results['failed']) === 0,

            'message' =>
                count($results['saved']) . ' saved, ' .
                count($results['failed']) . ' failed.',

            'batch_no' => $batchNo,

            'saved' => $results['saved'],

            'failed' => $results['failed'],
        ]);
    }

    // ==========================================================
    // NEW: manual (non-QR) stock-in save, used by the Delivery
    // Items table on the stock-in screen. Credits inventory for
    // exactly what was entered as "received", not the full
    // delivered amount, so short/partial receipts are recorded
    // accurately.
    //
    // Quantity is uniform (package_qty) for every item — no
    // per-item-name branching.
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
            'items.*.item_id'      => 'required|integer|exists:item,item_id',
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

        $batchNo = 'STOCKIN-' . now()->format('YmdHis');
        $results = ['saved' => [], 'failed' => []];

        foreach ($request->items as $item) {

            $itemId      = $item['item_id'];
            $receivedQty = (int) $item['received_qty'];
            $delivered   = $totalPackageQty;
            $remarks     = trim($item['remarks'] ?? '');

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
        return response()->json([
            'success' => count($results['failed']) === 0,
            'message' => count($results['saved']) . ' item(s) saved, ' .
                         count($results['failed']) . ' failed.',
            'batch_no' => $batchNo,
            'saved'    => $results['saved'],
            'failed'   => $results['failed'],
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