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
    $packageStatus = PackageStatus::with([
        'delivery',
        'package.contents.item',
    ])->findOrFail($packageStatusId);

    if ($packageStatus->status === 'delivered') {
        return back()->withErrors([
            'delivery' => 'This package has already been delivered.'
        ]);
    }

    $request->validate([
        'remarks' => 'nullable|string|max:500',
    ]);

    DB::beginTransaction();

    try {

        $packageStatus->status = 'delivered';
        $packageStatus->remarks = $request->remarks;
        $packageStatus->delivered_at = now();

        // No Auth dependency
        $packageStatus->receiver_name = 'Receiver';

        // No Auth dependency
        $packageStatus->delivered_by = null;

        $packageStatus->save();
        foreach ($packageStatus->package->contents as $content) {

            $inventory = Inventory::where('item_id', $content->item_id)
                ->first();

            if (!$inventory) {
                continue;
            }

            InventoryHistory::create([
                'batch_no'     => 'DELIVERED-' . now()->format('YmdHis'),

                'inventory_id' => $inventory->inventory_id,
                'item_id'      => $inventory->item_id,
                'warehouse_id' => $inventory->warehouse_id,

                // Delivery does not change warehouse quantity because
                // stock was already deducted during Stock Out.
                'old_qty'      => $inventory->qty,
                'new_qty'      => $inventory->qty,

                'changed_by'   => 'Receiver',

                'remarks'      => 'Package delivered'
                    . ($request->remarks ? ' - ' . $request->remarks : ''),

                'change_type'  => 'delivered',
            ]);
        }

        DeliveryHistory::create([
            'package_status_id' => $packageStatus->package_status_id,
            'user_id'           => null,
            'status'            => 'delivered',
            'remarks'           => $request->remarks,
        ]);


        DB::commit();

        return redirect()
            ->route('delivery.success')
            ->with('success','Package delivered successfully.');


    } catch (\Exception $e) {

        DB::rollBack();

        Log::error('Delivery Receive Failed', [
            'message'=>$e->getMessage(),
            'line'=>$e->getLine(),
            'file'=>$e->getFile(),
        ]);

        return back()->withErrors([
            'error'=>$e->getMessage()
        ]);
    }
}
}
