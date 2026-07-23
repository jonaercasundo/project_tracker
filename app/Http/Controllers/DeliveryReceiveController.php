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
    $packageStatus = PackageStatus::with('delivery')->findOrFail($packageStatusId);

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
        $packageStatus->receiver_name = Auth::user()->name;

        if (isset($packageStatus->delivered_by)) {
            $packageStatus->delivered_by = Auth::id();
        }

        $packageStatus->save();

        DeliveryHistory::create([
            'package_status_id' => $packageStatus->package_status_id,
            'user_id'           => Auth::id(),
            'status'            => 'delivered',
            'remarks'           => $request->remarks,
        ]);

        DB::commit();

        return redirect()
            ->route('delivery.success')
            ->with('success', 'Package delivered successfully.');

    } catch (\Exception $e) {

        DB::rollBack();

        Log::error($e->getMessage());
        Log::error($e->getTraceAsString());

        return back()->withErrors([
            'error' => $e->getMessage()
        ]);
    }
}
}
