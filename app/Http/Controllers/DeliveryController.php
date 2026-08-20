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
                ->orderByRaw('CAST(d.delivery_id AS UNSIGNED) ASC')
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
            ->orderByRaw('CAST(d.delivery_id AS UNSIGNED) ASC')
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
        | Generate PDF - PROJECT BY PROJECT, BACK TO BACK
        |--------------------------------------------------------------------------
        |
        | Each project is rendered as its own standalone PDF using the
        | existing 'deliveries.ar-layout' view (unchanged), so per-project
        | page count can actually be measured.
        |
        | Every project must START on an odd page. Since projects are
        | concatenated in order, this is guaranteed as long as EVERY
        | project's own page count is even - so if a project renders to
        | an odd number of pages, one blank page is appended to it before
        | moving on to the next project.
        |
        | The individual PDFs are then merged into a single final PDF
        | using FPDI (composer require setasign/fpdi-fpdf).
        |
        */
    
        $projectGroups = $deliveries->groupBy(
            fn ($delivery) => $delivery->project_id
        );
    
        $tempFiles = [];
    
        $mergedPdf = new \setasign\Fpdi\Fpdi();
        try {
    
            foreach ($projectGroups as $projectId => $projectDeliveries) {
    
                /*
                |----------------------------------------------------------------
                | RENDER THIS PROJECT'S PDF ALONE
                |----------------------------------------------------------------
                */
    
                $projectPdfContent = Pdf::loadView('deliveries.ar-layout', [
                    'deliveries' => $projectDeliveries->values(),
                    'qrCodes'    => $qrCodes,
                    'signerName' => Auth::user()?->name
                        ?? 'Authorized Representative',
                ])
                ->setPaper('legal', 'portrait')
                ->output();
    
    
                $tempPath = tempnam(sys_get_temp_dir(), 'ar_project_') . '.pdf';
    
                file_put_contents($tempPath, $projectPdfContent);
    
                $tempFiles[] = $tempPath;
    
    
                /*
                |----------------------------------------------------------------
                | IMPORT ITS PAGES INTO THE MERGED PDF
                |----------------------------------------------------------------
                */
    
                $pageCount = $mergedPdf->setSourceFile($tempPath);
    
                $lastSize = null;
    
                for ($page = 1; $page <= $pageCount; $page++) {
    
                    $templateId = $mergedPdf->importPage($page);
    
                    $lastSize = $mergedPdf->getTemplateSize($templateId);
    
                    $mergedPdf->AddPage(
                        $lastSize['orientation'],
                        [$lastSize['width'], $lastSize['height']]
                    );
    
                    $mergedPdf->useTemplate($templateId);
                }
    
    
                /*
                |----------------------------------------------------------------
                | PAD WITH A BLANK PAGE IF THIS PROJECT ENDED ON AN ODD PAGE
                |----------------------------------------------------------------
                |
                | Keeping every project's own page count even guarantees the
                | NEXT project always starts on an odd page.
                |
                */
    
                if ($pageCount % 2 !== 0 && $lastSize) {
    
                    $mergedPdf->AddPage(
                        $lastSize['orientation'],
                        [$lastSize['width'], $lastSize['height']]
                    );
                }
            }
    
    
            $mergedOutput = $mergedPdf->Output('S');
    
        } finally {
    
            /*
            |--------------------------------------------------------------------------
            | CLEAN UP TEMP FILES
            |--------------------------------------------------------------------------
            */
    
            foreach ($tempFiles as $tempFile) {
    
                if (file_exists($tempFile)) {
                    unlink($tempFile);
                }
            }
        }
    
    
        return response($mergedOutput, 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'inline; filename="deliveries-batch.pdf"',
        ]);
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
        | 2. GET PROJECT IDS AND SCHOOL IDS
        |--------------------------------------------------------------------------
        |
        | The selected deliveries determine:
        |
        | PROJECT
        | SCHOOL
        |
        | We then load all delivery records belonging to those
        | projects and schools.
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
            abort(404, 'Selected deliveries not found.');
        }


        $projectIds = $selectedDeliveries
            ->pluck('project_id')
            ->filter(fn ($id) => (int) $id > 0)
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();


        $schoolIds = $selectedDeliveries
            ->pluck('school_id')
            ->filter(fn ($id) => (int) $id > 0)
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();


        if ($projectIds->isEmpty()) {
            abort(404, 'No project found.');
        }

        if ($schoolIds->isEmpty()) {
            abort(404, 'No schools found.');
        }


        /*
        |--------------------------------------------------------------------------
        | 3. GET DELIVERIES
        |--------------------------------------------------------------------------
        |
        | IMPORTANT:
        |
        | We filter by BOTH:
        |
        |     project_id
        |     school_id
        |
        | This prevents deliveries from another project belonging to
        | the same school from being included.
        |
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

            ->orderBy('d.delivery_id', 'asc')

            ->get();


        if ($deliveries->isEmpty()) {
            abort(
                404,
                'No deliveries found for the selected project and school(s).'
            );
        }


        /*
        |--------------------------------------------------------------------------
        | 4. PROJECT ID FOR AR SETTINGS
        |--------------------------------------------------------------------------
        */

        $projectId = $deliveries
            ->pluck('project_id')
            ->filter()
            ->first();

        if (!$projectId) {
            abort(404, 'No project found.');
        }


        /*
        |--------------------------------------------------------------------------
        | 5. AR SETTINGS
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
        | 6. INITIAL DATA
        |--------------------------------------------------------------------------
        |
        | Structure:
        |
        | PROJECT
        |   SCHOOL
        |      LOT
        |         KEYSTAGE
        |            ITEMS
        |
        */

        $data = [];


        /*
        |--------------------------------------------------------------------------
        | 6b. LOT QR CACHE
        |--------------------------------------------------------------------------
        |
        | Same lot can appear under multiple schools. Generate each lot's
        | QR PNG only once and reuse it, keyed by lot_id (or lot name for
        | "no lot" deliveries). Same pattern/library as
        | DeliveryController@generate ($qrCodes).
        |
        */

        $lotQrCache = [];


        /*
        |--------------------------------------------------------------------------
        | 7. PROCESS EACH DELIVERY
        |--------------------------------------------------------------------------
        */

        foreach ($deliveries as $delivery) {

            $deliveryProjectId = (int) $delivery->project_id;
            $schoolId = (int) $delivery->school_id;
            $deliveryId = (int) $delivery->delivery_id;
            if ($deliveryProjectId <= 0) {
                continue;
            }

            if ($schoolId <= 0) {
                continue;
            }
            if ($deliveryId <= 0) {
                continue;
            }
            /*
            |--------------------------------------------------------------------------
            | CREATE PROJECT
            |--------------------------------------------------------------------------
            */

            if (!isset($data[$deliveryProjectId])) {

                $data[$deliveryProjectId] = [

                    'info' => [

                        'project_id' =>
                            $delivery->project_id,

                        'project_name' =>
                            trim(
                                (string) (
                                    $delivery->project_name ?? ''
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

                    'schools' => [],
                    'delivery_ids' => [],
                ];
            }

            $data[$deliveryProjectId]['delivery_ids'][] = $deliveryId;

            /*
            |--------------------------------------------------------------------------
            | CREATE SCHOOL (WITHIN PROJECT)
            |--------------------------------------------------------------------------
            */

            if (!isset(
                $data[$deliveryProjectId]
                    ['schools'][$schoolId]
            )) {

                $data[$deliveryProjectId]
                    ['schools'][$schoolId] = [

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
                    ],

                    'lots' => [],
                    'delivery_ids' => [],
                ];
            }


            /*
            |--------------------------------------------------------------------------
            | 8. LOT
            |--------------------------------------------------------------------------
            |
            | LOT IS ALWAYS IDENTIFIED BY lot_id.
            |
            | This prevents:
            |
            | LOT 42
            | LOT 43
            | LOT 44
            |
            | from being merged.
            |
            */

            $lotId = $delivery->lot_id !== null
                ? (int) $delivery->lot_id
                : null;


            if ($lotId !== null && $lotId > 0) {

                $lotKey = 'lot-' . $lotId;

            } else {

                $lotKey = 'no-lot';
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
                $data[$deliveryProjectId]
                    ['schools'][$schoolId]
                    ['lots'][$lotKey]
            )) {

                /*
                |----------------------------------------------------------------
                | LOT QR CODE
                |----------------------------------------------------------------
                |
                | Encoded value: lot_id, falling back to lot_name for
                | deliveries with no lot. Cached by that same value so the
                | PNG is only generated once even if the lot appears under
                | several schools.
                |
                */

                $lotQrValue = ($lotId !== null && $lotId > 0)
                    ? (string) $lotId
                    : $lotName;

                if (!isset($lotQrCache[$lotQrValue])) {

                    $lotQrResult = (new PngWriter())->write(
                        new QrCode($lotQrValue)
                    );

                    $lotQrCache[$lotQrValue] =
                        'data:image/png;base64,' .
                        base64_encode($lotQrResult->getString());
                }


                $data[$deliveryProjectId]
                    ['schools'][$schoolId]
                    ['lots'][$lotKey] = [

                    'lot_id' =>
                        $lotId,

                    'lot_name' =>
                        $lotName,

                    'lot_qr' =>
                        $lotQrCache[$lotQrValue],

                    'keystages' => [],
                    'delivery_ids' => [],
                ];
            }


            /*
            |--------------------------------------------------------------------------
            | 9. DELIVERY KEYSTAGE
            |--------------------------------------------------------------------------
            */

            $deliveryKeystageId =
                !empty($delivery->keystage_id)
                    ? (int) $delivery->keystage_id
                    : null;


            /*
            |--------------------------------------------------------------------------
            | 10. FIND PACKAGES
            |--------------------------------------------------------------------------
            |
            | IMPORTANT:
            |
            | package table DOES NOT HAVE project_id.
            |
            | Therefore DO NOT:
            |
            |     where('project_id', ...)
            |
            | We only match:
            |
            |     lot_id
            |     keystage_id
            |
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
            | 11. PROCESS PACKAGES
            |--------------------------------------------------------------------------
            */

            foreach ($packages as $package) {

                $packageId = (int) $package->package_id;

                if ($packageId <= 0) {
                    continue;
                }


                /*
                |--------------------------------------------------------------------------
                | SAFETY: LOT
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
                | SAFETY: KEYSTAGE
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
                | 12. DETERMINE KEYSTAGE
                |--------------------------------------------------------------------------
                */

                $keystageId =
                    $package->keystage_id !== null
                        ? (int) $package->keystage_id
                        : $deliveryKeystageId;


                $keystageKey =
                    $keystageId !== null
                        ? 'keystage-' . $keystageId
                        : 'no-keystage';


                /*
                |--------------------------------------------------------------------------
                | 13. GET KEYSTAGE INFORMATION
                |--------------------------------------------------------------------------
                */

                $keystage = null;

                if ($keystageId !== null) {

                    $keystage = DB::table('keystage')
                        ->where(
                            'keystage_id',
                            $keystageId
                        )
                        ->first();
                }


                /*
                |--------------------------------------------------------------------------
                | 14. KEYSTAGE LABEL
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

                    $keystageLabel = !empty($parts)
                        ? implode(' - ', $parts)
                        : 'Keystage ' . $keystageId;

                } else {

                    $keystageLabel = 'No Keystage';
                }


                /*
                |--------------------------------------------------------------------------
                | 15. CREATE KEYSTAGE
                |--------------------------------------------------------------------------
                */

                if (!isset(
                    $data[$deliveryProjectId]
                        ['schools'][$schoolId]
                        ['lots'][$lotKey]
                        ['keystages'][$keystageKey]
                )) {

                    $data[$deliveryProjectId]
                        ['schools'][$schoolId]
                        ['lots'][$lotKey]
                        ['keystages'][$keystageKey] = [

                        'keystage_id' =>
                            $keystageId,

                        'label' =>
                            $keystageLabel,

                        'items' => [],
                        'delivery_ids' => [],
                    ];
                }


                /*
                |--------------------------------------------------------------------------
                | 16. GET PACKAGE CONTENT
                |--------------------------------------------------------------------------
                */

                $contents = DB::table(
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


                /*
                |--------------------------------------------------------------------------
                | 17. PROCESS ITEMS
                |--------------------------------------------------------------------------
                */

                foreach ($contents as $content) {

                    $itemId = (int) $content->item_id;

                    if ($itemId <= 0) {
                        continue;
                    }


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

                    $contentQty = (int) (
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

                    $packageQty = (int) (
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
                    */

                    $itemKey =
                        'item-' . $itemId;


                    /*
                    |--------------------------------------------------------------------------
                    | GET CURRENT ITEMS
                    |--------------------------------------------------------------------------
                    */

                    $items =
                        $data[$deliveryProjectId]
                            ['schools'][$schoolId]
                            ['lots'][$lotKey]
                            ['keystages'][$keystageKey]
                            ['items'];


                    /*
                    |--------------------------------------------------------------------------
                    | MERGE SAME ITEM
                    |--------------------------------------------------------------------------
                    |
                    | Same item is merged ONLY within:
                    |
                    | PROJECT
                    | SCHOOL
                    | LOT
                    | KEYSTAGE
                    |
                    | It will NOT merge between different lots.
                    |
                    */

                    if (isset($items[$itemKey])) {

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

                    $data[$deliveryProjectId]
                        ['schools'][$schoolId]
                        ['lots'][$lotKey]
                        ['keystages'][$keystageKey]
                        ['items'] = $items;
                }
            }
        }


        /*
        |--------------------------------------------------------------------------
        | 18. REMOVE EMPTY DATA
        |--------------------------------------------------------------------------
        */

        foreach ($data as $projectId => &$project) {

            /*
            |--------------------------------------------------------------------------
            | SORT SCHOOLS BY SCHOOL ID
            |--------------------------------------------------------------------------
            */

            uasort($project['schools'], function ($a, $b) {
                $aDeliveryId = !empty($a['delivery_ids'])
                    ? min($a['delivery_ids'])
                    : PHP_INT_MAX;

                $bDeliveryId = !empty($b['delivery_ids'])
                    ? min($b['delivery_ids'])
                    : PHP_INT_MAX;

                return $aDeliveryId <=> $bDeliveryId;
            });

            foreach (
                $project['schools']
                as $schoolId => &$school
            ) {

                foreach (
                    $school['lots']
                    as $lotKey => &$lot
                ) {

                    foreach (
                        $lot['keystages']
                        as $keystageKey => &$keystage
                    ) {

                        if (
                            empty(
                                $keystage['items']
                            )
                        ) {

                            unset(
                                $lot['keystages'][$keystageKey]
                            );

                            continue;
                        }


                        $keystage['items'] =
                            array_values(
                                $keystage['items']
                            );
                    }

                    unset($keystage);


                    /*
                    |--------------------------------------------------------------------------
                    | REMOVE EMPTY LOT
                    |--------------------------------------------------------------------------
                    */

                    if (
                        empty(
                            $lot['keystages']
                        )
                    ) {

                        unset(
                            $school['lots'][$lotKey]
                        );

                        continue;
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | REINDEX KEYSTAGES
                    |--------------------------------------------------------------------------
                    */

                    $lot['keystages'] =
                        array_values(
                            $lot['keystages']
                        );
                }

                unset($lot);


                /*
                |--------------------------------------------------------------------------
                | REMOVE EMPTY SCHOOL
                |--------------------------------------------------------------------------
                */

                if (
                    empty(
                        $school['lots']
                    )
                ) {

                    unset(
                        $project['schools'][$schoolId]
                    );

                    continue;
                }


                /*
                |--------------------------------------------------------------------------
                | REINDEX LOTS
                |--------------------------------------------------------------------------
                */

                $school['lots'] =
                    array_values(
                        $school['lots']
                    );
            }

            unset($school);


            /*
            |--------------------------------------------------------------------------
            | REMOVE EMPTY PROJECT
            |--------------------------------------------------------------------------
            */

            if (
                empty(
                    $project['schools']
                )
            ) {

                unset(
                    $data[$projectId]
                );

                continue;
            }
        }

        unset($project);


        /*
        |--------------------------------------------------------------------------
        | 19. CHECK DATA
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
        | 20. GENERATE PDF
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