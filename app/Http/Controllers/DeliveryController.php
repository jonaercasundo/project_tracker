<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use App\Models\Delivery;
use App\Models\ARSetting;
use App\Models\PackageStatus;
use Illuminate\Support\Facades\Auth;

class DeliveryController extends Controller
{


    public function entry(Request $request, $id)
    {
        $delivery_id = $request->query('delivery_id');

        return view('deliveries.partials.entry', [
            'id' => $id,
            'delivery_id' => $delivery_id,
        ]);
    }
    public function scan(Request $request, $id)
    {
        $delivery_id = $request->query('delivery_id');

        // Load your scan logic here (migrated from scan.php)
        return view('deliveries.partials.scan', [
            'id' => $id,
            'delivery_id' => $delivery_id,
        ]);
    }
    // =========================
    // FILTER ENDPOINTS
    // =========================

    public function getLotInfo(Request $request)
    {
        $request->validate([
            'lot' => 'required|integer|exists:lot,lot_id',
        ]);

        $lot = DB::table('lot as l')
            ->join('projects as p', 'p.project_id', '=', 'l.project_id')
            ->where('l.lot_id', $request->lot)
            ->select('l.lot_id', 'l.project_id', 'p.project_name')
            ->first();

        if (!$lot) {
            return response()->json(['error' => 'Lot not found.'], 404);
        }

        return response()->json($lot);
    }

    public function getLots(Request $request)
    {
        return response()->json(
            DB::table('lot')
                ->where('project_id', $request->project)
                ->orderBy('lot_name')
                ->get(['lot_id', 'lot_name'])
        );
    }

    public function getRegions(Request $request)
    {
        $query = DB::table('deliveries as d')
            ->join('school as s', 's.school_id', '=', 'd.school_id')
            ->select('s.region as region')
            ->whereNotNull('s.region');

        if ($request->filled('project')) $query->where('d.project_id', $request->project);
        if ($request->filled('lot'))     $query->where('d.lot_id', $request->lot);

        return response()->json(
            $query->distinct()->orderBy('s.region')->get()
        );
    }

    public function getDivisions(Request $request)
    {
        $query = DB::table('deliveries as d')
            ->join('school as s', 's.school_id', '=', 'd.school_id')
            ->select('s.division')
            ->distinct();

        if ($request->filled('project')) $query->where('d.project_id', $request->project);
        if ($request->filled('lot'))     $query->where('d.lot_id', $request->lot);
        if ($request->filled('region'))  $query->where('s.region', $request->region);

        return response()->json(
            $query->orderBy('s.division')->get()
        );
    }

    public function getMunicipalities(Request $request)
    {
        $query = DB::table('deliveries as d')
            ->join('school as s', 's.school_id', '=', 'd.school_id')
            ->select('s.municipality')
            ->distinct();

        if ($request->filled('project'))  $query->where('d.project_id', $request->project);
        if ($request->filled('lot'))      $query->where('d.lot_id', $request->lot);
        if ($request->filled('region'))   $query->where('s.region', $request->region);
        if ($request->filled('division')) $query->where('s.division', $request->division);

        return response()->json(
            $query->orderBy('s.municipality')->get()
        );
    }

    // =========================
    // INDEX
    // =========================

        public function index(Request $request)
        {
            $limit = (int) $request->input('per_page', 10);
            if (!in_array($limit, [10, 20, 30, 50, 100])) $limit = 10;

            $page   = max(1, (int) $request->get('page', 1));
            $offset = ($page - 1) * $limit;

            // =========================
            // BASE QUERY (no item joins)
            // =========================
            $baseQuery = DB::table('deliveries as d')
                ->leftJoin('keystage as k', 'k.keystage_id', '=', 'd.keystage_id')
                ->join('lot as l',          'l.lot_id',       '=', 'd.lot_id')
                ->join('projects as p',     'p.project_id',   '=', 'd.project_id')
                ->join('school as s',       's.school_id',    '=', 'd.school_id');

            // =========================
            // SEARCH
            // =========================
            if ($request->filled('search')) {
                $search = $request->search;
                $baseQuery->where(function ($q) use ($search) {
                    $q->where('d.dr_no',        'like', "%{$search}%")
                    ->orWhere('p.project_name', 'like', "%{$search}%")
                    ->orWhere('s.school_name',  'like', "%{$search}%")
                    ->orWhere('l.lot_name',     'like', "%{$search}%");
                });
            }

            // =========================
            // FILTERS
            // =========================
            if ($request->filled('status'))       $baseQuery->where('d.status',      $request->status);
            if ($request->filled('project'))      $baseQuery->where('d.project_id',  $request->project);
            if ($request->filled('lot'))          $baseQuery->where('d.lot_id',      $request->lot);
            if ($request->filled('region'))       $baseQuery->where('s.region',      $request->region);
            if ($request->filled('division'))     $baseQuery->where('s.division',    $request->division);
            if ($request->filled('municipality')) $baseQuery->where('s.municipality',$request->municipality);
            if ($request->filled('year'))         $baseQuery->whereYear('d.delivery_date', $request->year);

            // =========================
            // TOTAL
            // =========================
            $total_rows  = (clone $baseQuery)->distinct()->count('d.delivery_id');
            $total_pages = (int) ceil($total_rows / $limit);

            // =========================
            // PAGINATED IDs ONLY
            // =========================
            $deliveryIds = (clone $baseQuery)
                ->select('d.delivery_id')
                ->distinct()
                ->orderByRaw('CAST(d.dr_no AS UNSIGNED) ASC')
                ->limit($limit)
                ->offset($offset)
                ->pluck('d.delivery_id');

            // =========================
            // FULL DATA WITH ITEMS
            // only for paginated IDs
            // =========================
            $rows = DB::table('deliveries as d')
            ->leftJoin('keystage as k', 'k.keystage_id', '=', 'd.keystage_id')
            ->join('lot as l',          'l.lot_id',       '=', 'd.lot_id')
            ->join('projects as p',     'p.project_id',   '=', 'd.project_id')
            ->join('school as s',       's.school_id',    '=', 'd.school_id')
            // match old concept: package belongs via keystage OR (if no keystage) via lot
            ->leftJoin('package as pk', function ($join) {
                $join->where(function ($j) {
                    $j->whereNotNull('d.keystage_id')
                    ->whereColumn('pk.keystage_id', '=', 'd.keystage_id');
                })->orWhere(function ($j) {
                    $j->whereNull('d.keystage_id')
                    ->whereColumn('pk.lot_id', '=', 'd.lot_id');
                });
            })
            ->leftJoin('package_content as pc', 'pc.package_id', '=', 'pk.package_id')
            ->leftJoin('item as i',             'i.item_id',      '=', 'pc.item_id')
            // live per-package status for THIS delivery
            ->leftJoin('package_status as ps', function ($join) {
                $join->on('ps.delivery_id', '=', 'd.delivery_id')
                    ->on('ps.package_id',  '=', 'pk.package_id');
            })
            ->whereIn('d.delivery_id', $deliveryIds)
            ->select(
                'd.delivery_id',
                'd.dr_no',
                'd.delivery_date',
                'd.status',
                'd.school_id',
                'd.project_id',
                'd.package_qty',
                'p.project_name',
                's.school_name',
                's.address',
                's.region',
                's.division',
                's.municipality',
                'k.keystage_num',
                'k.description',
                'l.lot_name',
                'pk.package_id',
                'ps.status as package_status',
                'i.item_name',
                'pc.qty as content_qty'
            )
            ->orderByRaw('CAST(d.dr_no AS UNSIGNED) ASC')
            ->orderBy('pk.package_id')
        ->get();
        // =========================
        // GROUP BY DR + DELIVERY
        // =========================
        $grouped = [];

        foreach ($rows as $row) {
        
            $dr = $row->dr_no;
        
            if (!isset($grouped[$dr])) {
                $grouped[$dr] = [
                    'dr_no'         => $dr,
                    'delivery_id'   => $row->delivery_id,
                    'project_id'    => $row->project_id,
                    'project_name'  => $row->project_name,
                    'school_id'     => $row->school_id,
                    'school_name'   => $row->school_name,
                    'address'       => $row->address,
                    'region'        => $row->region,
                    'division'      => $row->division,
                    'municipality'  => $row->municipality,
                    'delivery_date' => $row->delivery_date,
                    'status'        => $row->status,
                    'deliveries'    => [],
                ];
            }
        
            $deliveryId = $row->delivery_id;
        
            if (!isset($grouped[$dr]['deliveries'][$deliveryId])) {
                $delivery = clone $row;
                $delivery->packages = []; // keyed by package_id while building
                $grouped[$dr]['deliveries'][$deliveryId] = $delivery;
            }
        
            $delivery = $grouped[$dr]['deliveries'][$deliveryId];
        
            if (!empty($row->package_id)) {
        
                if (!isset($delivery->packages[$row->package_id])) {
                    $delivery->packages[$row->package_id] = [
                        'package_id' => $row->package_id,
                        'status'     => $row->package_status ?: 'pending',
                        'items'      => [], // keyed by item_name to dedupe
                    ];
                }
        
                if (!empty($row->item_name)) {
                    $qty = (int) ($row->content_qty ?? 1) * (int) ($row->package_qty ?? 1);
                    $delivery->packages[$row->package_id]['items'][$row->item_name] =
                        $row->item_name . ' (' . $qty . ')';
                }
            }
        }
        
        // =========================
        // FINALIZE: package rn/total, clean items, reindex
        // =========================
        foreach ($grouped as &$g) {
        
            foreach ($g['deliveries'] as &$delivery) {
        
                $packages = array_values($delivery->packages);
                usort($packages, fn($a, $b) => $a['package_id'] <=> $b['package_id']);
        
                $total = count($packages);
        
                $delivery->packages = array_values(array_map(function ($pkg, $i) use ($total) {
                    return [
                        'package_num'    => $i + 1,
                        'total_packages' => $total,
                        'status'         => $pkg['status'],
                        'items'          => array_values($pkg['items']),
                    ];
                }, $packages, array_keys($packages)));
        
                unset($delivery->items_list); // no longer used
            }
        
            $g['deliveries'] = array_values($g['deliveries']);
        }
        unset($g, $delivery);

        // =========================
        // DROPDOWNS
        // =========================
        $years = DB::table('deliveries')
            ->selectRaw('YEAR(delivery_date) as year')
            ->distinct()
            ->orderBy('year', 'desc')
            ->pluck('year');

        $projects = DB::table('projects')->get();

        $lots = $request->filled('project')
            ? DB::table('lot')->where('project_id', $request->project)->orderBy('lot_name')->get()
            : collect();

        return view('deliveries.index', [
            'grouped_deliveries' => $grouped,
            'projects'           => $projects,
            'lots'               => $lots,
            'years'              => $years,
            'page'               => $page,
            'total_pages'        => $total_pages,
            'total_rows'         => $total_rows,
        ]);
    }

    // =========================
    // GENERATE QR PDF
    // =========================

    public function generate(Request $request)
    {
        $ids = collect(explode(',', $request->ids))
            ->map(fn($v) => trim($v))
            ->filter(fn($v) => is_numeric($v) && $v > 0)
            ->values();
    
        if ($ids->isEmpty()) {
            abort(422, 'Invalid DR numbers.');
        }
    
        /*
        |--------------------------------------------------------------------------
        | Resolve DR numbers
        |--------------------------------------------------------------------------
        */
    
        $drNos = Delivery::whereIn('delivery_id', $ids)
            ->pluck('dr_no')
            ->unique()
            ->values();
    
        if ($drNos->isEmpty()) {
            abort(404, 'No deliveries found.');
        }
    
        /*
        |--------------------------------------------------------------------------
        | Expand to ALL delivery rows belonging to the selected DR
        |--------------------------------------------------------------------------
        */
    
        $ids = Delivery::whereIn('dr_no', $drNos)
            ->pluck('delivery_id')
            ->unique()
            ->values();
    
        /*
        |--------------------------------------------------------------------------
        | Load deliveries
        |--------------------------------------------------------------------------
        |
        | IMPORTANT:
        |
        | package_status only knows:
        |     delivery_id
        |     package_id
        |
        | The item and quantity come from:
        |
        | package
        |     -> packageContent
        |         -> item
        |
        */
    
        $deliveries = Delivery::with([
            'school',
            'project.arSetting',
            'lot',
            'keystage',
            'packageStatuses.package.packageContent.item',
        ])
        ->whereIn('delivery_id', $ids)
        ->orderBy('dr_no')
        ->orderBy('keystage_id')
        ->get();
    
        if ($deliveries->isEmpty()) {
            abort(404, 'No deliveries found.');
        }
    
        $qrCodes = [];
    
        /*
        |--------------------------------------------------------------------------
        | Process each delivery / keystage
        |--------------------------------------------------------------------------
        */
    
        foreach ($deliveries as $delivery) {
    
            /*
            |--------------------------------------------------------------------------
            | Get packages belonging ONLY to this delivery's keystage
            |--------------------------------------------------------------------------
            */
    
            $packageQuery = DB::table('package');
    
            if (!empty($delivery->keystage_id)) {
    
                $packageQuery->where(
                    'keystage_id',
                    $delivery->keystage_id
                );
    
            } else {
    
                $packageQuery->where(
                    'lot_id',
                    $delivery->lot_id
                );
            }
    
            $packageIds = $packageQuery
                ->pluck('package_id');
    
    
            /*
            |--------------------------------------------------------------------------
            | Create missing package_status rows
            |--------------------------------------------------------------------------
            |
            | IMPORTANT:
            | Do NOT put item_id or qty here.
            |
            */
    
            foreach ($packageIds as $packageId) {
    
                $exists = DB::table('package_status')
                    ->where('delivery_id', $delivery->delivery_id)
                    ->where('package_id', $packageId)
                    ->exists();
    
                if (!$exists) {
    
                    DB::table('package_status')->insert([
                        'delivery_id' => $delivery->delivery_id,
                        'package_id'  => $packageId,
                        'status'      => 'pending',
                        'remarks'     => null,
                    ]);
                }
            }
    
    
            /*
            |--------------------------------------------------------------------------
            | Reload package statuses with package contents
            |--------------------------------------------------------------------------
            */
    
            $statuses = PackageStatus::with([
                'package.packageContent.item',
            ])
            ->where('delivery_id', $delivery->delivery_id)
            ->get();
    
    
            /*
            |--------------------------------------------------------------------------
            | Remove package_status rows belonging to another keystage
            |--------------------------------------------------------------------------
            */
    
            if (!empty($delivery->keystage_id)) {
    
                $statuses = $statuses
                    ->filter(function ($status) use ($delivery) {
    
                        return $status->package
                            && (int) $status->package->keystage_id
                                === (int) $delivery->keystage_id;
    
                    })
                    ->values();
    
            } else {
    
                $statuses = $statuses
                    ->filter(function ($status) use ($delivery) {
    
                        return $status->package
                            && (int) $status->package->lot_id
                                === (int) $delivery->lot_id;
    
                    })
                    ->values();
            }
    
    
            /*
            |--------------------------------------------------------------------------
            | Set filtered statuses back onto delivery
            |--------------------------------------------------------------------------
            */
    
            $delivery->setRelation(
                'packageStatuses',
                $statuses
            );
    
            $delivery->ar =
                $delivery->project->arSetting ?? null;
    
    
            /*
            |--------------------------------------------------------------------------
            | Generate QR codes
            |--------------------------------------------------------------------------
            */
    
            foreach ($statuses as $status) {
    
                if (!$status->package_status_id) {
                    continue;
                }
    
                $url = sprintf(
                    'https://mmc.metro-ltd.com/entry.php?id=%s&delivery_id=%s',
                    $status->package_status_id,
                    $delivery->delivery_id
                );
    
                $result = (new PngWriter())->write(
                    new QrCode($url)
                );
    
                $qrCodes[$status->package_status_id] =
                    'data:image/png;base64,' .
                    base64_encode($result->getString());
    
    
                /*
                |--------------------------------------------------------------------------
                | QR LABEL
                |--------------------------------------------------------------------------
                |
                | package_status
                |      ↓
                | package
                |      ↓
                | package_content
                |      ↓
                | item
                |
                */
    
                $itemNames = collect();
    
                if ($status->package) {
    
                    foreach (
                        $status->package->packageContent
                        as $content
                    ) {
    
                        if ($content->item) {
    
                            $itemName = $content->item->item_name;
    
                            if ($itemName) {
                                $itemNames->push($itemName);
                            }
                        }
                    }
                }
    
                $status->qr_label = $itemNames->isNotEmpty()
                    ? $itemNames->unique()->implode(', ')
                    : 'Unknown Item';
            }
        }
    
    
        /*
        |--------------------------------------------------------------------------
        | Generate PDF
        |--------------------------------------------------------------------------
        */
    
        return Pdf::loadView('deliveries.ar-layout', [
            'deliveries' => $deliveries,
            'qrCodes'    => $qrCodes,
            'signerName' => Auth::user()?->name
                ?? 'Authorized Representative',
        ])
        ->setPaper('legal', 'portrait')
        ->stream('deliveries-batch.pdf');
    }

    // =========================
    // GENERATE LABELS PDF
    // =========================


    public function generateLabels(Request $request)
    {
        ini_set('memory_limit', '1024M');
        set_time_limit(0);
    
        /*
        |--------------------------------------------------------------------------
        | 1. GET SELECTED DELIVERY IDS
        |--------------------------------------------------------------------------
        */
    
        $selectedIds = collect(
            explode(',', (string) $request->ids)
        )
            ->map(fn ($id) => (int) trim($id))
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values();
    
        if ($selectedIds->isEmpty()) {
            abort(422, 'No deliveries selected.');
        }
    
    
        /*
        |--------------------------------------------------------------------------
        | 2. GET PROJECT + SCHOOL COMBINATIONS FROM SELECTED DELIVERIES
        |--------------------------------------------------------------------------
        |
        | IMPORTANT:
        |
        | The packing list hierarchy is:
        |
        | PROJECT
        |     SCHOOL
        |         LOT
        |             KEYSTAGE
        |                 ITEM
        |
        | We must NOT load every delivery belonging to the school
        | from unrelated projects.
        |
        */
    
        $selectedDeliveries = DB::table('deliveries')
            ->whereIn('delivery_id', $selectedIds)
            ->select(
                'delivery_id',
                'project_id',
                'school_id'
            )
            ->get();
    
        if ($selectedDeliveries->isEmpty()) {
            abort(404, 'Selected deliveries were not found.');
        }
    
    
        /*
        |--------------------------------------------------------------------------
        | 3. GET SELECTED PROJECT IDS
        |--------------------------------------------------------------------------
        */
    
        $projectIds = $selectedDeliveries
            ->pluck('project_id')
            ->filter(fn ($id) => (int) $id > 0)
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();
    
        if ($projectIds->isEmpty()) {
            abort(404, 'No project found from the selected deliveries.');
        }
    
    
        /*
        |--------------------------------------------------------------------------
        | 4. GET SELECTED SCHOOL IDS
        |--------------------------------------------------------------------------
        */
    
        $schoolIds = $selectedDeliveries
            ->pluck('school_id')
            ->filter(fn ($id) => (int) $id > 0)
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();
    
        if ($schoolIds->isEmpty()) {
            abort(404, 'No schools found from the selected deliveries.');
        }
    
    
        /*
        |--------------------------------------------------------------------------
        | 5. LOAD ALL DELIVERIES FOR:
        |
        | PROJECT + SCHOOL
        |
        | This is the important part.
        |
        | We allow all lots belonging to the selected
        | project/school combination.
        |--------------------------------------------------------------------------
        */
    
        $deliveries = DB::table('deliveries as d')
    
            ->join(
                'school as s',
                's.school_id',
                '=',
                'd.school_id'
            )
    
            ->leftJoin(
                'lot as l',
                'l.lot_id',
                '=',
                'd.lot_id'
            )
    
            ->leftJoin(
                'projects as p',
                'p.project_id',
                '=',
                'd.project_id'
            )
    
            ->whereIn(
                'd.project_id',
                $projectIds
            )
    
            ->whereIn(
                'd.school_id',
                $schoolIds
            )
    
            ->select(
                'd.delivery_id',
                'd.dr_no',
                'd.project_id',
                'd.school_id',
                'd.lot_id',
                'd.keystage_id',
                'd.package_qty',
    
                's.school_id as school_table_id',
                's.school_name',
                's.municipality',
                's.division',
                's.region',
    
                'l.lot_name',
    
                'p.project_name'
            )
    
            /*
            |--------------------------------------------------------------------------
            | SORTING
            |--------------------------------------------------------------------------
            */
    
            ->orderBy('d.project_id')
            ->orderBy('s.school_name')
            ->orderBy('d.lot_id')
            ->orderBy('d.keystage_id')
            ->orderBy('d.delivery_id')
    
            ->get();
    
    
        if ($deliveries->isEmpty()) {
            abort(
                404,
                'No deliveries found for the selected project and school(s).'
            );
        }
    
    
        /*
        |--------------------------------------------------------------------------
        | 6. INITIAL DATA
        |--------------------------------------------------------------------------
        */
    
        $data = [];
    
    
        /*
        |--------------------------------------------------------------------------
        | 7. CACHE KEYSTAGES
        |--------------------------------------------------------------------------
        |
        | Avoid querying the keystage table repeatedly.
        |
        */
    
        $keystageCache = [];
    
    
        /*
        |--------------------------------------------------------------------------
        | 8. CACHE PACKAGE CONTENT
        |--------------------------------------------------------------------------
        |
        | Avoid repeatedly querying the same package contents.
        |
        */
    
        $packageContentCache = [];
    
    
        /*
        |--------------------------------------------------------------------------
        | 9. PROCESS EVERY DELIVERY
        |--------------------------------------------------------------------------
        */
    
        foreach ($deliveries as $delivery) {
    
            /*
            |--------------------------------------------------------------------------
            | PROJECT
            |--------------------------------------------------------------------------
            */
    
            $projectId = $delivery->project_id !== null
                ? (int) $delivery->project_id
                : null;
    
            if (!$projectId) {
                continue;
            }
    
    
            $projectName = trim(
                (string) (
                    $delivery->project_name ?? ''
                )
            );
    
            if ($projectName === '') {
                $projectName = 'PROJECT ' . $projectId;
            }
    
    
            /*
            |--------------------------------------------------------------------------
            | SCHOOL
            |--------------------------------------------------------------------------
            */
    
            $schoolId = (int) $delivery->school_id;
    
            if ($schoolId <= 0) {
                continue;
            }
    
    
            /*
            |--------------------------------------------------------------------------
            | CREATE PROJECT
            |--------------------------------------------------------------------------
            */
    
            $projectKey = 'project-' . $projectId;
    
            if (!isset($data[$projectKey])) {
    
                $data[$projectKey] = [
    
                    'project_id' =>
                        $projectId,
    
                    'project_name' =>
                        $projectName,
    
                    'schools' => [],
    
                ];
            }
    
    
            /*
            |--------------------------------------------------------------------------
            | CREATE SCHOOL
            |--------------------------------------------------------------------------
            */
    
            $schoolKey = 'school-' . $schoolId;
    
            if (!isset(
                $data[$projectKey]['schools'][$schoolKey]
            )) {
    
                $data[$projectKey]['schools'][$schoolKey] = [
    
                    'info' => [
    
                        'school_name' =>
                            trim(
                                (string) (
                                    $delivery->school_name ?? ''
                                )
                            ),
    
                        'school_id' =>
                            $delivery->school_id ?? '',
    
                        'municipality' =>
                            trim(
                                (string) (
                                    $delivery->municipality ?? ''
                                )
                            ),
    
                        'division' =>
                            trim(
                                (string) (
                                    $delivery->division ?? ''
                                )
                            ),
    
                        'region' =>
                            trim(
                                (string) (
                                    $delivery->region ?? ''
                                )
                            ),
    
                    ],
    
                    'lots' => [],
    
                ];
            }
    
    
            /*
            |--------------------------------------------------------------------------
            | 10. DETERMINE LOT
            |--------------------------------------------------------------------------
            |
            | VERY IMPORTANT:
            |
            | LOT IS SCOPED UNDER PROJECT + SCHOOL.
            |
            | Therefore:
            |
            | Project 1 / School 100 / Lot 1
            |
            | is different from:
            |
            | Project 2 / School 100 / Lot 1
            |
            |--------------------------------------------------------------------------
            */
    
            $lotId = $delivery->lot_id !== null
                ? (int) $delivery->lot_id
                : null;
    
    
            /*
            |--------------------------------------------------------------------------
            | LOT KEY
            |--------------------------------------------------------------------------
            */
    
            if ($lotId !== null && $lotId > 0) {
    
                $lotKey =
                    'project-' .
                    $projectId .
                    '-school-' .
                    $schoolId .
                    '-lot-' .
                    $lotId;
    
            } else {
    
                $lotKey =
                    'project-' .
                    $projectId .
                    '-school-' .
                    $schoolId .
                    '-no-lot';
            }
    
    
            /*
            |--------------------------------------------------------------------------
            | LOT NAME
            |--------------------------------------------------------------------------
            */
    
            $lotName = trim(
                (string) (
                    $delivery->lot_name ?? ''
                )
            );
    
            if ($lotName === '') {
                $lotName = 'NO LOT';
            }
    
    
            /*
            |--------------------------------------------------------------------------
            | CREATE LOT
            |--------------------------------------------------------------------------
            */
    
            if (!isset(
                $data[$projectKey]
                    ['schools'][$schoolKey]
                    ['lots'][$lotKey]
            )) {
    
                $data[$projectKey]
                    ['schools'][$schoolKey]
                    ['lots'][$lotKey] = [
    
                    'lot_id' =>
                        $lotId,
    
                    'lot_name' =>
                        $lotName,
    
                    'keystages' => [],
    
                ];
            }
    
    
            /*
            |--------------------------------------------------------------------------
            | 11. DELIVERY KEYSTAGE
            |--------------------------------------------------------------------------
            */
    
            $deliveryKeystageId =
                !empty($delivery->keystage_id)
                    ? (int) $delivery->keystage_id
                    : null;
    
    
            /*
            |--------------------------------------------------------------------------
            | 12. FIND PACKAGES
            |--------------------------------------------------------------------------
            |
            | PACKAGE MATCHING:
            |
            | LOT + KEYSTAGE
            |
            | This prevents packages from another lot from entering
            | the current lot.
            |--------------------------------------------------------------------------
            */
    
            $packageQuery = DB::table('package');
    
    
            /*
            |--------------------------------------------------------------------------
            | MATCH LOT
            |--------------------------------------------------------------------------
            */
    
            if ($lotId !== null && $lotId > 0) {
    
                $packageQuery->where(
                    'lot_id',
                    $lotId
                );
    
            } else {
    
                $packageQuery->whereNull(
                    'lot_id'
                );
            }
    
    
            /*
            |--------------------------------------------------------------------------
            | MATCH KEYSTAGE
            |--------------------------------------------------------------------------
            */
    
            if ($deliveryKeystageId !== null) {
    
                $packageQuery->where(
                    'keystage_id',
                    $deliveryKeystageId
                );
    
            } else {
    
                $packageQuery->whereNull(
                    'keystage_id'
                );
            }
    
    
            /*
            |--------------------------------------------------------------------------
            | GET PACKAGES
            |--------------------------------------------------------------------------
            */
    
            $packages = $packageQuery
    
                ->select(
                    'package_id',
                    'lot_id',
                    'keystage_id'
                )
    
                ->orderBy(
                    'package_id'
                )
    
                ->get();
    
    
            /*
            |--------------------------------------------------------------------------
            | 13. PROCESS PACKAGES
            |--------------------------------------------------------------------------
            */
    
            foreach ($packages as $package) {
    
                $packageId = (int) $package->package_id;
    
                if ($packageId <= 0) {
                    continue;
                }
    
    
                /*
                |--------------------------------------------------------------------------
                | VERIFY LOT
                |--------------------------------------------------------------------------
                */
    
                if ($lotId !== null) {
    
                    if (
                        $package->lot_id === null ||
                        (int) $package->lot_id !== $lotId
                    ) {
                        continue;
                    }
                }
    
    
                /*
                |--------------------------------------------------------------------------
                | VERIFY KEYSTAGE
                |--------------------------------------------------------------------------
                */
    
                if ($deliveryKeystageId !== null) {
    
                    if (
                        $package->keystage_id === null ||
                        (int) $package->keystage_id !==
                        $deliveryKeystageId
                    ) {
                        continue;
                    }
                }
    
    
                /*
                |--------------------------------------------------------------------------
                | 14. DETERMINE KEYSTAGE
                |--------------------------------------------------------------------------
                */
    
                $keystageId =
                    $package->keystage_id !== null
                        ? (int) $package->keystage_id
                        : $deliveryKeystageId;
    
    
                /*
                |--------------------------------------------------------------------------
                | KEYSTAGE KEY
                |--------------------------------------------------------------------------
                */
    
                $keystageKey =
                    $keystageId !== null
                        ? 'keystage-' . $keystageId
                        : 'no-keystage';
    
    
                /*
                |--------------------------------------------------------------------------
                | 15. GET KEYSTAGE INFORMATION
                |--------------------------------------------------------------------------
                */
    
                if ($keystageId !== null) {
    
                    if (!array_key_exists(
                        $keystageId,
                        $keystageCache
                    )) {
    
                        $keystageCache[$keystageId] =
                            DB::table('keystage')
                                ->where(
                                    'keystage_id',
                                    $keystageId
                                )
                                ->first();
                    }
    
                    $keystage =
                        $keystageCache[$keystageId];
    
                } else {
    
                    $keystage = null;
                }
    
    
                /*
                |--------------------------------------------------------------------------
                | 16. BUILD KEYSTAGE LABEL
                |--------------------------------------------------------------------------
                */
    
                if ($keystage) {
    
                    $number = trim(
                        (string) (
                            $keystage->keystage_num ?? ''
                        )
                    );
    
                    $description = trim(
                        (string) (
                            $keystage->description ?? ''
                        )
                    );
    
                    $parts = [];
    
                    if ($number !== '') {
    
                        $parts[] =
                            'Keystage ' . $number;
                    }
    
                    if ($description !== '') {
    
                        $parts[] =
                            $description;
                    }
    
                    if (!empty($parts)) {
    
                        $keystageLabel =
                            implode(
                                ' - ',
                                $parts
                            );
    
                    } else {
    
                        $keystageLabel =
                            'Keystage ' . $keystageId;
                    }
    
                } else {
    
                    $keystageLabel =
                        'No Keystage';
                }
    
    
                /*
                |--------------------------------------------------------------------------
                | 17. CREATE KEYSTAGE
                |--------------------------------------------------------------------------
                */
    
                if (!isset(
                    $data[$projectKey]
                        ['schools'][$schoolKey]
                        ['lots'][$lotKey]
                        ['keystages'][$keystageKey]
                )) {
    
                    $data[$projectKey]
                        ['schools'][$schoolKey]
                        ['lots'][$lotKey]
                        ['keystages'][$keystageKey] = [
    
                        'keystage_id' =>
                            $keystageId,
    
                        'label' =>
                            $keystageLabel,
    
                        'items' => [],
    
                    ];
                }
    
    
                /*
                |--------------------------------------------------------------------------
                | 18. GET PACKAGE CONTENT
                |--------------------------------------------------------------------------
                */
    
                if (!isset(
                    $packageContentCache[$packageId]
                )) {
    
                    $packageContentCache[$packageId] =
                        DB::table(
                            'package_content as pc'
                        )
    
                        ->join(
                            'item as i',
                            'i.item_id',
                            '=',
                            'pc.item_id'
                        )
    
                        ->where(
                            'pc.package_id',
                            $packageId
                        )
    
                        ->select(
                            'i.item_id',
                            'i.item_name',
                            'i.unit',
                            'pc.qty'
                        )
    
                        ->get();
                }
    
    
                $contents =
                    $packageContentCache[$packageId];
    
    
                /*
                |--------------------------------------------------------------------------
                | 19. PROCESS ITEMS
                |--------------------------------------------------------------------------
                */
    
                foreach ($contents as $content) {
    
                    $itemId =
                        (int) $content->item_id;
    
                    if ($itemId <= 0) {
                        continue;
                    }
    
    
                    /*
                    |--------------------------------------------------------------------------
                    | ITEM NAME
                    |--------------------------------------------------------------------------
                    */
    
                    $itemName = trim(
                        (string) (
                            $content->item_name ?? ''
                        )
                    );
    
                    if ($itemName === '') {
                        continue;
                    }
    
    
                    /*
                    |--------------------------------------------------------------------------
                    | PACKAGE CONTENT QTY
                    |--------------------------------------------------------------------------
                    */
    
                    $contentQty =
                        (int) (
                            $content->qty ?? 0
                        );
    
                    if ($contentQty <= 0) {
                        $contentQty = 1;
                    }
    
    
                    /*
                    |--------------------------------------------------------------------------
                    | DELIVERY PACKAGE QTY
                    |--------------------------------------------------------------------------
                    */
    
                    $packageQty =
                        (int) (
                            $delivery->package_qty ?? 0
                        );
    
                    if ($packageQty <= 0) {
                        $packageQty = 1;
                    }
    
    
                    /*
                    |--------------------------------------------------------------------------
                    | FINAL QUANTITY
                    |--------------------------------------------------------------------------
                    */
    
                    $finalQty =
                        $contentQty *
                        $packageQty;
    
                    if ($finalQty <= 0) {
                        continue;
                    }
    
    
                    /*
                    |--------------------------------------------------------------------------
                    | ITEM KEY
                    |--------------------------------------------------------------------------
                    |
                    | Item is already inside:
                    |
                    | PROJECT
                    |   SCHOOL
                    |      LOT
                    |         KEYSTAGE
                    |
                    | So the same item may safely exist in multiple
                    | lots and keystages.
                    |--------------------------------------------------------------------------
                    */
    
                    $itemKey =
                        'item-' . $itemId;
    
    
                    /*
                    |--------------------------------------------------------------------------
                    | GET CURRENT ITEMS
                    |--------------------------------------------------------------------------
                    */
    
                    $items =
                        $data[$projectKey]
                            ['schools'][$schoolKey]
                            ['lots'][$lotKey]
                            ['keystages'][$keystageKey]
                            ['items'];
    
    
                    /*
                    |--------------------------------------------------------------------------
                    | MERGE ITEM
                    |--------------------------------------------------------------------------
                    */
    
                    if (isset(
                        $items[$itemKey]
                    )) {
    
                        $items[$itemKey]['qty'] +=
                            $finalQty;
    
                    } else {
    
                        $items[$itemKey] = [
    
                            'item_id' =>
                                $itemId,
    
                            'item_name' =>
                                $itemName,
    
                            'qty' =>
                                $finalQty,
    
                            'unit' =>
                                trim(
                                    (string) (
                                        $content->unit ?? ''
                                    )
                                ),
    
                        ];
                    }
    
    
                    /*
                    |--------------------------------------------------------------------------
                    | SAVE ITEMS
                    |--------------------------------------------------------------------------
                    */
    
                    $data[$projectKey]
                        ['schools'][$schoolKey]
                        ['lots'][$lotKey]
                        ['keystages'][$keystageKey]
                        ['items'] = $items;
                }
            }
        }
    
    
        /*
        |--------------------------------------------------------------------------
        | 20. REMOVE EMPTY KEYSTAGES / LOTS / SCHOOLS / PROJECTS
        |--------------------------------------------------------------------------
        */
    
        foreach ($data as $projectKey => &$project) {
    
            foreach (
                $project['schools']
                as $schoolKey => &$school
            ) {
    
                foreach (
                    $school['lots']
                    as $lotKey => &$lot
                ) {
    
                    foreach (
                        $lot['keystages']
                        as $keystageKey => &$keystage
                    ) {
    
                        /*
                        | Remove empty keystage
                        */
    
                        if (
                            empty(
                                $keystage['items']
                            )
                        ) {
    
                            unset(
                                $lot['keystages']
                                    [$keystageKey]
                            );
    
                            continue;
                        }
    
    
                        /*
                        | Re-index items
                        */
    
                        $keystage['items'] =
                            array_values(
                                $keystage['items']
                            );
                    }
    
                    unset($keystage);
    
    
                    /*
                    | Remove empty lot
                    */
    
                    if (
                        empty(
                            $lot['keystages']
                        )
                    ) {
    
                        unset(
                            $school['lots']
                                [$lotKey]
                        );
    
                        continue;
                    }
    
    
                    /*
                    | Re-index keystages
                    */
    
                    $lot['keystages'] =
                        array_values(
                            $lot['keystages']
                        );
                }
    
                unset($lot);
    
    
                /*
                | Remove empty school
                */
    
                if (
                    empty(
                        $school['lots']
                    )
                ) {
    
                    unset(
                        $project['schools']
                            [$schoolKey]
                    );
    
                    continue;
                }
    
    
                /*
                | Re-index lots
                */
    
                $school['lots'] =
                    array_values(
                        $school['lots']
                    );
            }
    
            unset($school);
    
    
            /*
            | Remove empty project
            */
    
            if (
                empty(
                    $project['schools']
                )
            ) {
    
                unset(
                    $data[$projectKey]
                );
    
                continue;
            }
    
    
            /*
            | Re-index schools
            */
    
            $project['schools'] =
                array_values(
                    $project['schools']
                );
        }
    
        unset($project);
    
    
        /*
        |--------------------------------------------------------------------------
        | 21. CHECK DATA
        |--------------------------------------------------------------------------
        */
    
        if (empty($data)) {
    
            abort(
                404,
                'No package items found for the selected project and school(s).'
            );
        }
    
    
        /*
        |--------------------------------------------------------------------------
        | 22. AR SETTINGS
        |--------------------------------------------------------------------------
        |
        | Use the first selected project for the label settings.
        |
        */
    
        $firstProjectId =
            $projectIds->first();
    
        $arSettings = ARSetting::where(
            'project_id',
            $firstProjectId
        )->first();
    
    
        $showSchoolID = (bool) (
            $arSettings?->label_school_id ?? false
        );
    
        $showMunicipality = (bool) (
            $arSettings?->label_municipality ?? false
        );
    
        $showDivision = (bool) (
            $arSettings?->label_division ?? false
        );
    
        $showRegion = (bool) (
            $arSettings?->label_region ?? false
        );
    
    
        /*
        |--------------------------------------------------------------------------
        | 23. GENERATE PDF
        |--------------------------------------------------------------------------
        */
    
        return Pdf::loadView(
            'deliveries.label-layout',
            [
    
                'data' =>
                    $data,
    
                'showSchoolID' =>
                    $showSchoolID,
    
                'showMunicipality' =>
                    $showMunicipality,
    
                'showDivision' =>
                    $showDivision,
    
                'showRegion' =>
                    $showRegion,
    
            ]
        )
    
            ->setPaper(
                'a4',
                'portrait'
            )
    
            ->stream(
                'Packing_List_' .
                now()->format('Ymd_His') .
                '.pdf'
            );
    }


}