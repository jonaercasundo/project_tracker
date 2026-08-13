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

        $ids = collect(
            explode(',', (string) $request->ids)
        )
            ->map(fn ($id) => (int) trim($id))
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            abort(422, 'No deliveries selected.');
        }


        /*
        |--------------------------------------------------------------------------
        | 2. GET PROJECT ID FROM SELECTED DELIVERIES
        |--------------------------------------------------------------------------
        */

        $projectId = Delivery::whereIn('delivery_id', $ids)
            ->whereNotNull('project_id')
            ->value('project_id');

        if (!$projectId) {
            abort(404, 'No project found for the selected deliveries.');
        }


        /*
        |--------------------------------------------------------------------------
        | 3. AR SETTINGS
        |--------------------------------------------------------------------------
        */

        $arSettings = ARSetting::where(
            'project_id',
            $projectId
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
        | 4. QUERY
        |--------------------------------------------------------------------------
        |
        | This follows the structure of your old PHP script:
        |
        | schools_project
        |      ↓
        | school
        |      ↓
        | deliveries
        |      ↓
        | lot
        |      ↓
        | package_status
        |      ↓
        | package
        |      ↓
        | package_content
        |      ↓
        | item
        |
        | IMPORTANT:
        |
        | Quantity is:
        |
        | package_content.qty × deliveries.package_qty
        |
        */

        $rows = DB::table('schools_project as sp')

            ->leftJoin(
                'school as s',
                's.school_id',
                '=',
                'sp.school_id'
            )

            ->leftJoin(
                'deliveries as d',
                function ($join) use ($ids, $projectId) {

                    $join->on(
                        'd.project_id',
                        '=',
                        'sp.project_id'
                    );

                    $join->on(
                        'd.school_id',
                        '=',
                        'sp.school_id'
                    );

                    /*
                    |--------------------------------------------------------------------------
                    | ONLY SELECTED DELIVERIES
                    |--------------------------------------------------------------------------
                    */

                    $join->whereIn(
                        'd.delivery_id',
                        $ids
                    );

                    $join->where(
                        'd.project_id',
                        $projectId
                    );
                }
            )

            ->leftJoin(
                'lot as l',
                'l.lot_id',
                '=',
                'd.lot_id'
            )

            ->leftJoin(
                'package_status as ps',
                'ps.delivery_id',
                '=',
                'd.delivery_id'
            )

            ->leftJoin(
                'package as p',
                'p.package_id',
                '=',
                'ps.package_id'
            )

            ->leftJoin(
                'package_content as pc',
                'pc.package_id',
                '=',
                'p.package_id'
            )

            ->leftJoin(
                'item as i',
                'i.item_id',
                '=',
                'pc.item_id'
            )

            ->where(
                'sp.project_id',
                $projectId
            )

            ->select(
                's.school_id',
                's.school_name',
                's.municipality',
                's.division',
                's.region',

                'sp.batch_id',

                'l.lot_id',
                'l.lot_name',

                'i.item_id',
                'i.item_name',
                'i.unit',

                DB::raw(
                    'SUM(
                        COALESCE(pc.qty, 1)
                        *
                        COALESCE(d.package_qty, 1)
                    ) AS total_qty'
                )
            )

            ->groupBy(
                's.school_id',
                's.school_name',
                's.municipality',
                's.division',
                's.region',

                'sp.batch_id',

                'l.lot_id',
                'l.lot_name',

                'i.item_id',
                'i.item_name',
                'i.unit'
            )

            ->orderBy(
                's.school_name'
            )

            ->orderBy(
                'sp.batch_id'
            )

            ->orderBy(
                'l.lot_name'
            )

            ->orderBy(
                'i.item_name'
            )

            ->get();


        /*
        |--------------------------------------------------------------------------
        | 5. CHECK DATA
        |--------------------------------------------------------------------------
        */

        if ($rows->isEmpty()) {
            abort(
                404,
                'No package data found for the selected deliveries.'
            );
        }


        /*
        |--------------------------------------------------------------------------
        | 6. BUILD DATA
        |--------------------------------------------------------------------------
        |
        | Structure:
        |
        | school
        |   └── info
        |   └── lots
        |         └── items
        |
        */

        $data = [];


        foreach ($rows as $row) {

            /*
            |--------------------------------------------------------------------------
            | SCHOOL ID
            |--------------------------------------------------------------------------
            */

            $schoolId = (int) $row->school_id;

            /*
            |--------------------------------------------------------------------------
            | SAFETY
            |--------------------------------------------------------------------------
            */

            if ($schoolId <= 0) {
                continue;
            }


            /*
            |--------------------------------------------------------------------------
            | CREATE SCHOOL
            |--------------------------------------------------------------------------
            */

            if (!isset($data[$schoolId])) {

                $data[$schoolId] = [

                    'info' => [

                        'school_name' =>
                            $row->school_name ?? '',

                        'school_id' =>
                            $row->school_id ?? '',

                        'municipality' =>
                            $row->municipality ?? '',

                        'division' =>
                            $row->division ?? '',

                        'region' =>
                            $row->region ?? '',

                    ],

                    'lots' => [],

                ];
            }


            /*
            |--------------------------------------------------------------------------
            | LOT
            |--------------------------------------------------------------------------
            |
            | Same concept as the old PHP:
            |
            | $data[$sid]['lots'][$lot]
            |
            */

            $lotName = trim(
                (string) ($row->lot_name ?? '')
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
                $data[$schoolId]['lots'][$lotName]
            )) {

                $data[$schoolId]['lots'][$lotName] = [];
            }


            /*
            |--------------------------------------------------------------------------
            | ITEM
            |--------------------------------------------------------------------------
            */

            $itemName = trim(
                (string) ($row->item_name ?? '')
            );

            if ($itemName === '') {
                continue;
            }


            /*
            |--------------------------------------------------------------------------
            | ITEM KEY
            |--------------------------------------------------------------------------
            |
            | Use item_id instead of item_name when available.
            |
            | This prevents two different items with the same name
            | from being mixed incorrectly.
            |
            */

            $itemKey = !empty($row->item_id)
                ? 'item-' . (int) $row->item_id
                : 'name-' . md5($itemName);


            /*
            |--------------------------------------------------------------------------
            | QUANTITY
            |--------------------------------------------------------------------------
            */

            $qty = (int) (
                $row->total_qty ?? 0
            );

            if ($qty <= 0) {
                continue;
            }


            /*
            |--------------------------------------------------------------------------
            | MERGE ITEM
            |--------------------------------------------------------------------------
            */

            if (
                isset(
                    $data[$schoolId]
                        ['lots'][$lotName]
                        [$itemKey]
                )
            ) {

                $data[$schoolId]
                    ['lots'][$lotName]
                    [$itemKey]['qty'] += $qty;

            } else {

                $data[$schoolId]
                    ['lots'][$lotName]
                    [$itemKey] = [

                    'item_id' =>
                        $row->item_id,

                    'item_name' =>
                        $itemName,

                    'qty' =>
                        $qty,

                    'unit' =>
                        $row->unit ?? '',

                ];
            }
        }


        /*
        |--------------------------------------------------------------------------
        | 7. REMOVE EMPTY SCHOOLS / LOTS
        |--------------------------------------------------------------------------
        */

        foreach ($data as $schoolId => &$schoolData) {

            foreach (
                $schoolData['lots']
                as $lotName => &$items
            ) {

                if (empty($items)) {

                    unset(
                        $schoolData['lots'][$lotName]
                    );

                    continue;
                }

                /*
                |--------------------------------------------------------------------------
                | REINDEX ITEMS
                |--------------------------------------------------------------------------
                */

                $items = array_values($items);
            }

            unset($items);


            /*
            |--------------------------------------------------------------------------
            | REMOVE EMPTY SCHOOL
            |--------------------------------------------------------------------------
            */

            if (empty($schoolData['lots'])) {

                unset(
                    $data[$schoolId]
                );
            }
        }

        unset($schoolData);


        /*
        |--------------------------------------------------------------------------
        | 8. CHECK FINAL DATA
        |--------------------------------------------------------------------------
        */

        if (empty($data)) {

            abort(
                404,
                'No package data found for the selected deliveries.'
            );
        }


        /*
        |--------------------------------------------------------------------------
        | 9. GENERATE PDF
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
            'Packing_List_Batch_' .
            now()->format('Ymd_His') .
            '.pdf'
        );
    }

}