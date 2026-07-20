<?php

namespace App\Http\Controllers;

use App\Models\Inventory;
use App\Models\InventoryHistory;
use App\Models\PackageStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class WarehouseInventoryController extends Controller
{
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
                    'remarks'      => 'Warehouse QR Scan',
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
}