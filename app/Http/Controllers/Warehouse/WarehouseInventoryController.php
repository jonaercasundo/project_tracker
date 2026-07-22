<?php

namespace App\Http\Controllers\Warehouse;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Inventory;
use App\Models\InventoryHistory;
use App\Models\PackageStatus;
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
    public function scan(Request $request)
    {
        $request->validate([
            'package_status_id' => 'required|integer',
            'warehouse_id'      => 'required|integer',
            'transaction'       => 'required|in:IN,OUT',
        ]);

        DB::transaction(function () use ($request) {

            $status = PackageStatus::with('package.contents')
                ->findOrFail($request->package_status_id);

            if ($status->status === 'warehouse') {
                abort(422, 'This package has already been scanned.');
            }

            foreach ($status->package->contents as $content) {

                $inventory = Inventory::firstOrNew([
                    'warehouse_id' => $request->warehouse_id,
                    'item_id'      => $content->item_id,
                ]);

                $oldQty = $inventory->exists ? $inventory->qty : 0;

                if ($request->transaction === 'IN') {
                    $newQty = $oldQty + $content->qty;
                } else {
                    $newQty = max(0, $oldQty - $content->qty);
                }

                $inventory->qty = $newQty;
                $inventory->inventory_status = 'Approved';
                $inventory->save();

                InventoryHistory::create([
                    'inventory_id' => $inventory->inventory_id,
                    'item_id'      => $content->item_id,
                    'warehouse_id' => $request->warehouse_id,
                    'old_qty'      => $oldQty,
                    'new_qty'      => $newQty,
                    'changed_by'   => Auth::user()->name,
                    'remarks'      => "Inventory {$request->transaction}",
                    'change_type'  => 'update',
                ]);
            }

            $status->status = 'warehouse';
            $status->remarks = 'Scanned by Warehouse';
            $status->save();
        });

        return response()->json([
            'success' => true,
            'message' => 'Inventory successfully updated.',
        ]);
    }
    public function scanner()
    {
        $warehouses = Warehouse::orderBy('warehouse_name')->get();
        return view('operation.warehouse.dashboard', [
            'warehouses' => $warehouses,
        ]);
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
            'transaction'  => 'required|in:IN,OUT',
        ]);

        $packageStatusId = $this->extractPackageStatusId($request->qr);

        if (!$packageStatusId) {
            return response()->json(['success' => false, 'message' => 'Invalid QR code.']);
        }

        $status = PackageStatus::with('package.contents.item')
            ->find($packageStatusId);

        if (!$status) {
            return response()->json(['success' => false, 'message' => 'Package not found.']);
        }

        if ($request->transaction === 'IN') {

            // Stock In: reject if already in warehouse
            if ($status->status === 'warehouse') {

                return response()->json([
                    'success' => false,
                    'already_scanned' => true,
                    'package_status_id' => $status->package_status_id,
                    'package' => $status->package->package_num,
                    'message' => 'Already received in Warehouse.'
                ]);

            }

        } else {

            // Stock Out: package must already be in warehouse
            if ($status->status !== 'warehouse') {

                return response()->json([
                    'success' => false,
                    'package_status_id' => $status->package_status_id,
                    'package' => $status->package->package_num,
                    'message' => 'Package is not inside the warehouse.'
                ]);

            }

        }      

        $contents = $status->package->contents;

        if ($contents->isEmpty()) {
            return response()->json(['success' => false, 'message' => 'Package has no contents defined.']);
        }

        $packageName = $status->package->package_num ?? null;

        if (!$packageName) {
            return response()->json([
                'success' => false,
                'message' => 'Package record exists but has no package number assigned.',
            ]);
        }

        $isSingleItem = $contents->count() === 1;

        $itemName = $isSingleItem
            ? ($contents->first()->item->item_name ?? null)
            : $contents->count() . ' items';

        if ($isSingleItem && !$itemName) {
            return response()->json([
                'success' => false,
                'message' => 'Item record exists but has no name assigned.',
            ]);
        }

        return response()->json([
            'success'           => true,
            'package_status_id' => $status->package_status_id,
            'package'           => $packageName,
            'item'              => $itemName,
            'item_id'           => $isSingleItem ? $contents->first()->item_id : null,
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