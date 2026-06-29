<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EntryController extends Controller
{
    public function show($id, $delivery_id)
    {
        session_start();

        $question = include base_path('captcha.php');

        // =========================
        // DELIVERY QUERY
        // =========================
        $deliveries = DB::table('package_status as ps')
            ->join('deliveries as d', 'd.delivery_id', '=', 'ps.delivery_id')
            ->join('school as s', 's.school_id', '=', 'd.school_id')
            ->join('projects as p', 'p.project_id', '=', 'd.project_id')
            ->where('ps.package_status_id', $id)
            ->select(
                'd.*',
                'p.project_name',
                's.school_name as school',
                's.address',
                'ps.status as package_status'
            )
            ->first();

        if (!$deliveries) {
            abort(404, 'Invalid QR Code');
        }

        // =========================
        // MULTIPLIER
        // =========================
        $multiplier = 1;
        if (!empty($deliveries->package_type)) {
            preg_match('/\d+/', $deliveries->package_type, $m);
            $multiplier = $m[0] ?? 1;
        }

        // =========================
        // ITEMS
        // =========================
        $items = DB::table('package_status as ps')
            ->join('package as p', 'p.package_id', '=', 'ps.package_id')
            ->join('package_content as pc', 'pc.package_id', '=', 'p.package_id')
            ->join('item as i', 'i.item_id', '=', 'pc.item_id')
            ->where('ps.package_status_id', $id)
            ->select('pc.*', 'i.item_name')
            ->get();

        // =========================
        // INVENTORY
        // =========================
        $warehouse_id = session('warehouse_id');

        $inventory = [];
        $insufficient = [];
        $ok = true;

        if ($warehouse_id && $deliveries->package_status === 'pending') {

            $rows = DB::table('inventory')
                ->select('item_id', DB::raw('SUM(qty) as total_qty'))
                ->where('warehouse_id', $warehouse_id)
                ->where('inventory_status', 'Approved')
                ->groupBy('item_id')
                ->get();

            foreach ($rows as $r) {
                $inventory[$r->item_id] = $r->total_qty;
            }

            foreach ($items as $item) {

                $actual = $item->qty * $multiplier;
                $available = $inventory[$item->item_id] ?? 0;

                if ($available < $actual) {
                    $ok = false;
                    $insufficient[] = [
                        'item_name' => $item->item_name,
                        'required' => $actual,
                        'available' => $available
                    ];
                }
            }
        }

        return view('entry.show', [
            'question' => $question,
            'id' => $id,
            'delivery_id' => $delivery_id,
            'deliveries' => $deliveries,
            'items' => $items,
            'inventory' => $inventory,
            'insufficient' => $insufficient,
            'ok' => $ok,
            'multiplier' => $multiplier
        ]);
    }
}