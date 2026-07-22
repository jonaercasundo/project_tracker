<?php

namespace App\Http\Controllers;

use App\Models\PackageStatus;
use App\Models\Inventory;
use App\Models\inventoryHistory;
use Illuminate\Http\Request;
use App\Models\DeliveryProof;
use App\Models\Deliveryhistory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

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
        $warehouseId = auth()->user()->warehouse_id ?? null;

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
    */
    $request->validate([
        'latitude'   => 'required|numeric',
        'longitude'  => 'required|numeric',
        'accuracy'   => 'required|numeric',
        'photos.*'   => 'nullable|image|mimes:jpg,jpeg,png|max:10240',
        'remarks'    => 'nullable|string|max:500',
    ]);

    $school = $packageStatus->delivery->school;

    /*
    |--------------------------------------------------------------------------
    | Check School GPS
    |--------------------------------------------------------------------------
    */
    if (
        empty($school->latitude) ||
        empty($school->longitude)
    ) {
        return back()->withErrors([
            'gps' => 'This school does not have GPS coordinates configured.'
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Validate GPS Accuracy
    |--------------------------------------------------------------------------
    */
    if ($request->accuracy > 50) {
        return back()->withErrors([
            'gps' => 'GPS signal is too weak. Please wait until GPS accuracy improves.'
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Calculate Distance
    |--------------------------------------------------------------------------
    */
    $distance = $this->calculateDistance(
        (float) $request->latitude,
        (float) $request->longitude,
        (float) $school->latitude,
        (float) $school->longitude
    );

    $allowedDistance = 100; // meters

    if ($distance > $allowedDistance) {
        return back()->withErrors([
            'gps' => 'You are ' . round($distance, 2) . ' meters away from the delivery location.'
        ]);
    }

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
        $packageStatus->receiver_name = auth()->user()->name;

        // Optional if you added these columns
        if (isset($packageStatus->distance_from_school)) {
            $packageStatus->distance_from_school = round($distance, 2);
        }

        if (isset($packageStatus->delivered_by)) {
            $packageStatus->delivered_by = auth()->id();
        }

        $packageStatus->save();

        DeliveryHistory::create([

            'package_status_id' => $packageStatus->package_status_id,

            'user_id' => auth()->id(),

            'status' => 'Delivered',

            'remarks' => $request->remarks,

            'latitude' => $request->latitude,

            'longitude' => $request->longitude,

            'accuracy' => $request->accuracy,

            'distance_from_school' => round($distance, 2)

        ]);

        /*
        |--------------------------------------------------------------------------
        | Upload Delivery Photos
        |--------------------------------------------------------------------------
        */
        if ($request->hasFile('photos')) {

            foreach ($request->file('photos') as $photo) {

                $path = $photo->store(
                    'delivery-proofs',
                    'public'
                );

                DeliveryProof::create([
                    'package_status_id' => $packageStatus->package_status_id,
                    'photo'             => $path,
                ]);
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Inventory Deduction + Inventory History
        |--------------------------------------------------------------------------
        | For every item in this package's packing list, deduct the delivered
        | quantity from the receiving warehouse's approved inventory (oldest
        | batch first) and log each batch touched in inventory_history with
        | change_type = 'delivered'.
        */

        $warehouseId = auth()->user()->warehouse_id ?? null;

        $delivery = $packageStatus->delivery;

        $multiplier = 1;

        if (!empty($delivery->package_type)) {
            preg_match('/\d+/', $delivery->package_type, $matches);

            if (!empty($matches)) {
                $multiplier = (int) $matches[0];
            }
        }

        foreach ($packageStatus->package->contents as $content) {

            $item = $content->item;

            $requiredQty = $this->getRequiredQty(
                strtolower($item->item_name),
                $delivery,
                $content->qty,
                $multiplier
            );

            if ($requiredQty <= 0) {
                continue;
            }

            // Deduct from approved inventory, oldest batches first (FIFO).
            // Inventory has no timestamps, so inventory_id (auto-increment)
            // stands in for "oldest batch first".
            $remainingToDeduct = $requiredQty;

            $inventoryRows = Inventory::where('warehouse_id', $warehouseId)
                ->where('item_id', $item->item_id)
                ->where('inventory_status', 'Approved')
                ->where('qty', '>', 0)
                ->orderBy('inventory_id')
                ->lockForUpdate()
                ->get();

            foreach ($inventoryRows as $row) {

                if ($remainingToDeduct <= 0) {
                    break;
                }

                $deductFromRow = min($row->qty, $remainingToDeduct);

                $row->qty -= $deductFromRow;
                $row->save();

                $remainingToDeduct -= $deductFromRow;

                // One history row per batch touched, so inventory_id stays accurate
                InventoryHistory::create([

                    'inventory_id' => $row->inventory_id,

                    'item_id' => $item->item_id,

                    'warehouse_id' => $warehouseId,

                    'change_type' => 'delivered',

                    'quantity' => $deductFromRow,

                    'changed_by' => auth()->user()->name,

                    'remarks' => 'Delivered via DR #' . $delivery->dr_no,

                    'changed_at' => now(),

                ]);
            }

            if ($remainingToDeduct > 0) {

                throw new \Exception(
                    "Insufficient approved inventory for {$item->item_name} to complete this delivery."
                );

            }
        }

        DB::commit();

        return redirect()
            ->route('delivery.dashboard')
            ->with('success', 'Package delivered successfully.');

    } catch (\Exception $e) {

        DB::rollBack();

        return back()->withErrors([
            'error' => $e->getMessage()
        ]);
    }
}
    private function calculateDistance($lat1, $lon1, $lat2, $lon2)
    {
        $earthRadius = 6371000;

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) * sin($dLat / 2)
            + cos(deg2rad($lat1))
            * cos(deg2rad($lat2))
            * sin($dLon / 2)
            * sin($dLon / 2);

        $c = 2 * atan2(
            sqrt($a),
            sqrt(1 - $a)
        );

        return $earthRadius * $c;
    }
}