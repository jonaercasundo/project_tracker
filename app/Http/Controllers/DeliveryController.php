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
            ->leftJoin('keystage as k',         'k.keystage_id',  '=', 'd.keystage_id')
            ->join('lot as l',                  'l.lot_id',        '=', 'd.lot_id')
            ->join('projects as p',             'p.project_id',    '=', 'd.project_id')
            ->join('school as s',               's.school_id',     '=', 'd.school_id')
            ->leftJoin('package as pk', function ($join) {
                $join->on('pk.keystage_id', '=', 'd.keystage_id')
                    ->on('pk.lot_id', '=', 'd.lot_id')
                    ->whereRaw("pk.package_num = CAST(REPLACE(d.package_type, 'C', '') AS UNSIGNED)");
                })
            ->leftJoin('package_content as pc', 'pc.package_id',   '=', 'pk.package_id')
            ->leftJoin('item as i',             'i.item_id',       '=', 'pc.item_id')
            ->whereIn('d.delivery_id', $deliveryIds)
            ->select(
                'd.delivery_id',
                'd.dr_no',
                'd.delivery_date',
                'd.status',
                'd.school_id',
                'd.project_id',
                'p.project_name',
                's.school_name',
                's.address',
                's.region',
                's.division',
                's.municipality',
                'k.keystage_num',
                'k.description',
                'l.lot_name',
                'i.item_name'
            )
            ->orderByRaw('CAST(d.dr_no AS UNSIGNED) ASC')
            ->get();

        // =========================
        // GROUP BY DR
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
                    'lot_name'      => $row->lot_name,
                    'deliveries'    => [],
                    'items_list'    => [],
                ];
            }

            $grouped[$dr]['deliveries'][] = $row;

            if (!empty($row->item_name)) {
                $grouped[$dr]['items_list'][] = $row->item_name;
            }
        }

        foreach ($grouped as &$g) {
            $g['items_list'] = array_values(array_unique(array_filter($g['items_list'])));
        }

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

        if ($ids->isEmpty()) abort(422, 'Invalid DR numbers.');

        $deliveries = Delivery::with([
            'school',
            'project.arSetting',
            'lot',
            'keystage',
            'keystage.packages',
            'items',
            'package',
            'packageStatuses',
            'items.packageContent.package',
            'packageStatuses.package.packageContent.item',
        ])
        ->whereIn('delivery_id', $ids)
        ->orderBy('dr_no')
        ->get();

        if ($deliveries->isEmpty()) abort(404, 'No deliveries found.');

        $qrCodes = [];

        foreach ($deliveries as $delivery) {

            if ($delivery->packageStatuses->isEmpty()) {

                $packageIds = DB::table('package')
                    ->where('lot_id', $delivery->lot_id)
                    ->pluck('package_id');

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

                $delivery->unsetRelation('packageStatuses');
                $delivery->load(['packageStatuses.package.packageContent.item']);
            }

            $delivery->ar = $delivery->project->arSetting ?? null;

            $statuses = PackageStatus::with(['package.packageContent.item'])
                ->where('delivery_id', $delivery->delivery_id)
                ->get();

            $delivery->setRelation('packageStatuses', $statuses);

            foreach ($statuses as $status) {
                if (!$status->package_status_id) continue;

                $url = sprintf(
                    'https://mmc.metro-ltd.com/entry.php?id=%s&delivery_id=%s',
                    $status->package_status_id,
                    $delivery->delivery_id
                );
                $result = (new PngWriter())->write(new QrCode($url));

                $qrCodes[$status->package_status_id] =
                    'data:image/png;base64,' . base64_encode($result->getString());

                $items = $status->package?->packageContent?->pluck('item') ?? collect();
                $itemNames = $items->pluck('item_name')->filter();

                $status->qr_label = $itemNames->isNotEmpty()
                    ? $itemNames->implode(', ')
                    : 'Unknown Item';
            }
        }

        return Pdf::loadView('deliveries.ar-layout', [
            'deliveries' => $deliveries,
            'qrCodes'    => $qrCodes,
            'signerName' => Auth::user()?->name ?? 'Authorized Representative',
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

        $ids = collect(explode(',', $request->ids))
            ->map(fn($id) => (int) trim($id))
            ->filter()
            ->values();

        if ($ids->isEmpty()) abort(422, 'No deliveries selected.');

        $deliveries = Delivery::with([
            'school',
            'project.arSetting',
            'lot',
            'packageStatuses.package.packageContent.item',
        ])
        ->whereIn('delivery_id', $ids)
        ->orderBy('school_id')
        ->get();

        if ($deliveries->isEmpty()) abort(404, 'No deliveries found.');
        $deliveries = Delivery::whereIn('delivery_id', $ids)->get();

        foreach ($deliveries as $delivery) {

            $packageIds = DB::table('package')
                ->where('lot_id', $delivery->lot_id)
                ->pluck('package_id');

            foreach ($packageIds as $packageId) {

                DB::table('package_status')->updateOrInsert(
                    [
                        'delivery_id' => $delivery->delivery_id,
                        'package_id'  => $packageId,
                    ],
                    [
                        'status' => 'pending'
                    ]
                );
            }
        }
        $projectId  = $deliveries->first()->project_id;
        $arSettings = ARSetting::where('project_id', $projectId)->first();

        $showSchoolID     = (bool) ($arSettings?->label_school_id    ?? false);
        $showMunicipality = (bool) ($arSettings?->label_municipality  ?? false);
        $showDivision     = (bool) ($arSettings?->label_division      ?? false);
        $showRegion       = (bool) ($arSettings?->label_region        ?? false);

        $data = [];

        foreach ($deliveries as $delivery) {
            $school = $delivery->school;
            if (!$school) continue;

            $sid = $school->school_id;
            $lot = $delivery->lot?->lot_name ?? 'NO LOT';

            if (!isset($data[$sid])) {
                $data[$sid] = [
                    'info' => [
                        'school_name'         => $school->school_name,
                        'school_id'           => $school->school_id,
                        'municipality'        => $school->municipality,
                        'division'            => $school->division,
                        'region'              => $school->region,
                        'qty_teachers_manual' => $delivery->qty_teachers_manual,
                    ],
                    'lots' => [],
                ];
            }

            if (!isset($data[$sid]['lots'][$lot])) {
                $data[$sid]['lots'][$lot] = [];
            }

            foreach ($delivery->packageStatuses as $status) {
                if (!$status->package) continue;

                foreach ($status->package->packageContent as $content) {
                    if (!$content->item) continue;

                    $itemName = $content->item->item_name;
                    if (!$itemName) continue;

                    $isTeacherManual = str_contains(strtolower($itemName), 'teacher')
                                    || str_contains(strtolower($itemName), 'manual');

                    $qty = $isTeacherManual
                        ? (int) $delivery->qty_teachers_manual
                        : (int) ($content->qty ?? 1) * (int) ($delivery->package_qty ?? 1);

                    if (isset($data[$sid]['lots'][$lot][$itemName])) {
                        $data[$sid]['lots'][$lot][$itemName]['qty'] += $qty;
                    } else {
                        $data[$sid]['lots'][$lot][$itemName] = [
                            'item_name'           => $itemName,
                            'qty'                 => $qty,
                            'unit'                => $content->item->unit,
                            'qty_teachers_manual' => $delivery->qty_teachers_manual ?? 0,
                        ];
                    }
                }
            }
        }

        return Pdf::loadView('deliveries.label-layout', [
            'data'             => $data,
            'showSchoolID'     => $showSchoolID,
            'showMunicipality' => $showMunicipality,
            'showDivision'     => $showDivision,
            'showRegion'       => $showRegion,
        ])
        ->setPaper('a4', 'portrait')
        ->stream('Packing_List_' . now()->format('Ymd_His') . '.pdf');
    }
}