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
        // BASE QUERY
        // =========================
        $baseQuery = DB::table('deliveries as d')
            ->leftJoin('keystage as k', 'k.keystage_id', '=', 'd.keystage_id')
            ->join('lot as l', 'l.lot_id', '=', 'd.lot_id')
            ->join('projects as p', 'p.project_id', '=', 'd.project_id')
            ->join('school as s', 's.school_id', '=', 'd.school_id')
            ->leftJoin('region as r', 'r.region_id', '=', 's.region_id')
            ->leftJoin('division as dv', 'dv.division_id', '=', 's.division_id')
            ->leftJoin('municipality as m', 'm.municipality_id', '=', 's.municipality_id');

        // =========================
        // SEARCH
        // =========================
        if ($request->filled('search')) {
            $search = $request->search;

            $baseQuery->where(function ($q) use ($search) {
                $q->where('d.dr_no', 'like', "%{$search}%")
                ->orWhere('p.project_name', 'like', "%{$search}%")
                ->orWhere('s.school_name', 'like', "%{$search}%")
                ->orWhere('l.lot_name', 'like', "%{$search}%");
            });
        }

        // =========================
        // STATUS FILTER
        // =========================
        if ($request->filled('status')) {
            $baseQuery->where('d.status', $request->status);
        }

        // =========================
        // PROJECT FILTER
        // =========================
        if ($request->filled('project')) {
            $baseQuery->where('d.project_id', $request->project);
        }

        // =========================
        // LOT FILTER
        // =========================
        if ($request->filled('lot')) {
            $baseQuery->where('d.lot_id', $request->lot);
        }

        // =========================
        // REGION FILTER
        // =========================
        if ($request->filled('region')) {
            $baseQuery->where('s.region_id', $request->region);
        }

        // =========================
        // DIVISION FILTER
        // =========================
        if ($request->filled('division')) {
            $baseQuery->where('s.division_id', $request->division);
        }

        // =========================
        // MUNICIPALITY FILTER
        // =========================
        if ($request->filled('municipality')) {
            $baseQuery->where('s.municipality_id', $request->municipality);
        }

        // =========================
        // TOTAL (after filters)
        // =========================
        $total_rows = (clone $baseQuery)->count();
        $total_pages = (int) ceil($total_rows / $limit);

        // =========================
        // DATA FETCH
        // =========================
        $deliveries = $baseQuery
            ->select(
                'd.*',
                'p.project_name',
                's.school_name',
                's.address',
                'k.keystage_num',
                'k.description',
                'l.lot_name',
                'r.region_name',
                'dv.division_name',
                'm.municipality_name'
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
                    'region_name' => $row->region_name,
                    'division_name' => $row->division_name,
                    'municipality_name' => $row->municipality_name,
                    'delivery_date' => $row->delivery_date,
                    'status' => $row->status,
                    'deliveries' => []
                ];
            }

            $grouped[$dr]['deliveries'][] = $row;
        }

        // =========================
        // DROPDOWNS DATA
        // =========================
        $projects = DB::table('projects')->get();
        $regions = DB::table('region')->orderBy('region_name')->get();

        return view('deliveries.index', [
            'grouped_deliveries' => $grouped,
            'projects' => $projects,
            'regions' => $regions,
            'page' => $page,
            'total_pages' => $total_pages,
            'total_rows' => $total_rows
        ]);
    }
}