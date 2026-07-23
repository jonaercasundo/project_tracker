<?php

namespace App\Http\Controllers;

use App\Models\PackageStatus;
use App\Models\Inventory;
use App\Models\InventoryHistory;
use Illuminate\Http\Request;
use App\Models\DeliveryProof;
use App\Models\DeliveryHistory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Auth\AuthManager;
class DeliveryReceiveController extends Controller
{
    public function show(Request $request, $packageStatusId)
    {
        $packageStatus = PackageStatus::with([
            'delivery.project',
            'delivery.school',
            'package.contents.item',
        ])->findOrFail($packageStatusId);

        $delivery = $packageStatus->delivery;

        /*
        |--------------------------------------------------------------------------
        | Package Multiplier
        |--------------------------------------------------------------------------
        */

        $multiplier = 1;

        if (!empty($delivery->package_type)) {
            preg_match('/\d+/', $delivery->package_type, $matches);

            if (!empty($matches)) {
                $multiplier = (int) $matches[0];
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Warehouse Inventory
        |--------------------------------------------------------------------------
        */

        // Replace this if warehouse_id comes from another source
        $warehouseId = Auth::user()->warehouse_id ?? null;

        $inventory = [];

        if ($warehouseId) {

            $inventory = Inventory::where('warehouse_id', $warehouseId)
                ->where('inventory_status', 'Approved')
                ->selectRaw('item_id, SUM(qty) as total_qty')
                ->groupBy('item_id')
                ->pluck('total_qty', 'item_id')
                ->toArray();
        }

        /*
        |--------------------------------------------------------------------------
        | Packing List
        |--------------------------------------------------------------------------
        */

        $items = [];

        foreach ($packageStatus->package->contents as $content) {

            $item = $content->item;

            $requiredQty = $this->getRequiredQty(
                strtolower($item->item_name),
                $delivery,
                $content->qty,
                $multiplier
            );

            $items[] = [

                'item_id' => $item->item_id,

                'item_name' => $item->item_name,

                'required_qty' => $requiredQty,

                'available_qty' => $inventory[$item->item_id] ?? 0,

                'is_sufficient' => ($inventory[$item->item_id] ?? 0) >= $requiredQty,
            ];
        }

        return view('operation.delivery.receive', compact(
            'packageStatus',
            'items',
            'inventory'
        ));
    }

    /**
     * Compute required quantity.
     */
    private function getRequiredQty($itemName, $delivery, $defaultQty, $multiplier)
    {
        if (str_contains($itemName, 'teacher')) {
            return (int) $delivery->qty_teachers_manual;
        }

        if (str_contains($itemName, 'textbook')) {
            return (int) $delivery->package_qty;
        }

        return $defaultQty * $multiplier;
    }

    public function store(Request $request, $packageStatusId)
    {
        // Load package with school relationship
        $packageStatus = PackageStatus::with([
            'delivery.school',
            'package.contents.item'
        ])->findOrFail($packageStatusId);

        /*
        |--------------------------------------------------------------------------
        | Prevent Duplicate Delivery
        |--------------------------------------------------------------------------
        */
        if ($packageStatus->status === 'delivered') {
            return back()->withErrors([
                'delivery' => 'This package has already been delivered.'
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Validate Request
        |--------------------------------------------------------------------------
        | Location fields are captured for the record only - they never
        | block submission, so they're nullable rather than required.
        */
        $request->validate([
            'latitude'   => 'nullable|numeric',
            'longitude'  => 'nullable|numeric',
            'accuracy'   => 'nullable|numeric',
            'photos.*'   => 'nullable|image|mimes:jpg,jpeg,png|max:10240',
            'remarks'    => 'nullable|string|max:500',
        ]);

        /*
        |--------------------------------------------------------------------------
        | Save Delivery
        |--------------------------------------------------------------------------
        */
        DB::beginTransaction();

        try {

            $packageStatus->status = 'delivered';
            $packageStatus->latitude = $request->latitude;
            $packageStatus->longitude = $request->longitude;
            $packageStatus->accuracy = $request->accuracy;
            $packageStatus->remarks = $request->remarks;
            $packageStatus->delivered_at = now();
            $packageStatus->receiver_name = Auth::user()->name;

            if (isset($packageStatus->delivered_by)) {
                $packageStatus->delivered_by = Auth::user()->user_id;
            }

            $packageStatus->save();

            DeliveryHistory::create([

                'package_status_id' => $packageStatus->package_status_id,

                'user_id' => Auth::user()->user_id,

                'status' => 'delivered',

                'remarks' => $request->remarks,

                'latitude' => $request->latitude,

                'longitude' => $request->longitude,

                'accuracy' => $request->accuracy,

            ]);

/*
|--------------------------------------------------------------------------
| Upload Delivery Photos
|--------------------------------------------------------------------------
*/
if ($request->hasFile('photos')) {

    foreach ($request->file('photos') as $photo) {

        $path = $photo->store('delivery-proofs', 'public');

        DeliveryProof::create([
            'package_status_id' => $packageStatus->package_status_id,
            'photo'             => $path,
        ]);
    }
}

/*
|--------------------------------------------------------------------------
| Inventory History (Delivered Audit Only)
|--------------------------------------------------------------------------
| Delivery does NOT deduct inventory.
| It only records the delivery event.
|--------------------------------------------------------------------------
*/

$batchNo = 'DEL-' . now()->format('YmdHis') . '-' . $packageStatus->package_status_id;

foreach ($packageStatus->package->contents as $content) {

    InventoryHistory::create([

        'inventory_id' => null,

        'item_id'      => $content->item_id,

        'warehouse_id' => null,

        'old_qty'      => 0,

        'new_qty'      => 0,

        'batch_no'     => $batchNo,

        'change_type'  => 'delivered',

        'changed_by'   => Auth::user()->name,

        'remarks'      => 'Delivered via DR #' . $packageStatus->delivery->dr_no,

        'changed_at'   => now(),

    ]);
}
            DB::commit();

            return redirect()
                ->route('delivery.success')
                ->with('success', 'Package delivered successfully.');

        } catch (\Exception $e) {

            DB::rollBack();

            Log::error($e->getMessage());
            Log::error($e->getTraceAsString());

            dd($e->getMessage());

        }
    }
}
