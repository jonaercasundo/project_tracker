<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DeliveryController extends Controller
{
    public function index(Request $request)
    {
        $limit = 10;
        $page = max(1, (int) $request->get('page', 1));
        $offset = ($page - 1) * $limit;

        // =========================
        // BASE QUERY (REUSABLE)
        // =========================
        $query = DB::table('deliveries as d')
            ->leftJoin('keystage as k', 'k.keystage_id', '=', 'd.keystage_id')
            ->join('lot as l', 'l.lot_id', '=', 'd.lot_id')
            ->join('projects as p', 'p.project_id', '=', 'd.project_id')
            ->join('school as s', 's.school_id', '=', 'd.school_id')
            ->leftJoin('logistics_location as ll', 'll.logistics_location_id', '=', 'd.logistics_location_id')
            ->leftJoin('warehouse as w', 'w.warehouse_id', '=', 'll.warehouse_id');

        // =========================
        // SEARCH (DR / Project / School / Lot)
        // =========================
        if ($request->filled('search')) {
            $search = $request->search;

            $query->where(function ($q) use ($search) {
                $q->where('d.dr_no', 'like', "%{$search}%")
                  ->orWhere('p.project_name', 'like', "%{$search}%")
                  ->orWhere('s.school_name', 'like', "%{$search}%")
                  ->orWhere('l.lot_name', 'like', "%{$search}%");
            });
        }

        // =========================
        // FILTERS
        // =========================
        if ($request->filled('status')) {
            $query->where('d.status', $request->status);
        }

        if ($request->filled('project_id')) {
            $query->where('d.project_id', $request->project_id);
        }

        if ($request->filled('school_id')) {
            $query->where('d.school_id', $request->school_id);
        }

        // =========================
        // TOTAL ROWS (AFTER FILTERS)
        // =========================
        $total_rows = (clone $query)->count();
        $total_pages = (int) ceil($total_rows / $limit);

        // =========================
        // DATA FETCH
        // =========================
        $deliveries = $query
            ->select(
                'd.*',
                'p.project_name',
                's.school_name',
                's.address',
                'k.keystage_num',
                'k.description',
                'l.lot_name',
                'w.warehouse_name'
            )
            ->orderBy('d.status')
            ->orderBy('d.delivery_date')
            ->limit($limit)
            ->offset($offset)
            ->get();

        // =========================
        // GROUP BY DR
        // =========================
        $grouped = [];

        foreach ($deliveries as $row) {
            $dr = $row->dr_no;

            if (!isset($grouped[$dr])) {
                $grouped[$dr] = [
                    'dr_no' => $dr,
                    'project_id' => $row->project_id,
                    'project_name' => $row->project_name,
                    'school_id' => $row->school_id,
                    'school_name' => $row->school_name,
                    'address' => $row->address,
                    'delivery_date' => $row->delivery_date,
                    'status' => $row->status,
                    'deliveries' => []
                ];
            }

            $grouped[$dr]['deliveries'][] = $row;
        }

        // =========================
        // PROJECT LIST
        // =========================
        $projects = DB::table('projects')->get();

        return view('deliveries.index', [
            'grouped_deliveries' => $grouped,
            'projects' => $projects,
            'page' => $page,
            'total_pages' => $total_pages,
            'total_rows' => $total_rows
        ]);
    }
}