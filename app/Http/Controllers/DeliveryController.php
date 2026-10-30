<?php

namespace App\Http\Controllers;

use App\Models\ARSetting;
use App\Models\Delivery;
use App\Models\PackageStatus;
use App\Services\DeliveryBillingQrCodeService;
use App\Services\ProjectDeliveryProgressService;
use Barryvdh\DomPDF\Facade\Pdf;
use Dompdf\Dompdf;
use Dompdf\Options;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use setasign\Fpdi\Fpdi;

class DeliveryController extends Controller
{
    public function __construct(private ProjectDeliveryProgressService $deliveryProgress) {}

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

        if (! $lot) {
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

        if ($request->filled('project')) {
            $query->where('d.project_id', $request->project);
        }
        if ($request->filled('lot')) {
            $query->where('d.lot_id', $request->lot);
        }

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

        if ($request->filled('project')) {
            $query->where('d.project_id', $request->project);
        }
        if ($request->filled('lot')) {
            $query->where('d.lot_id', $request->lot);
        }
        if ($request->filled('region')) {
            $query->where('s.region', $request->region);
        }

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

        if ($request->filled('project')) {
            $query->where('d.project_id', $request->project);
        }
        if ($request->filled('lot')) {
            $query->where('d.lot_id', $request->lot);
        }
        if ($request->filled('region')) {
            $query->where('s.region', $request->region);
        }
        if ($request->filled('division')) {
            $query->where('s.division', $request->division);
        }

        return response()->json(
            $query->orderBy('s.municipality')->get()
        );
    }

    // =========================
    // INDEX
    // =========================

    public function index(Request $request)
    {
        if ($request->query->has('year')) {
            $query = $request->query();
            unset($query['year']);

            return redirect()->route('deliveries.tracking', $query);
        }

        if ($request->filled('project')) {
            $projectValue = $request->input('project');
            $projectId = is_scalar($projectValue) ? filter_var($projectValue, FILTER_VALIDATE_INT) : false;
            $projectExists = $projectId !== false && DB::table('projects')->where('project_id', $projectId)->exists();

            if (! $projectExists) {
                $query = $request->query();
                unset($query['project'], $query['year']);

                return redirect()->route('deliveries.tracking', $query);
            }
        }

        $projects = DB::table('projects')->orderBy('project_name')->get(['project_id', 'project_name']);
        if (! $request->filled('project')) {
            return view('deliveries.index', [
                'grouped_deliveries' => [],
                'projects' => $projects,
                'lots' => collect(),
                'page' => 1,
                'total_pages' => 0,
                'total_rows' => 0,
                'stats' => [],
            ]);
        }

        $limit = (int) $request->input('per_page', 10);
        if (! in_array($limit, [10, 20, 30, 50, 100])) {
            $limit = 10;
        }

        $page = max(1, (int) $request->get('page', 1));
        $offset = ($page - 1) * $limit;

        // =========================
        // BASE QUERY (no item joins)
        // =========================
        $baseQuery = DB::table('deliveries as d')
            ->leftJoin('keystage as k', 'k.keystage_id', '=', 'd.keystage_id')
            ->join('lot as l', 'l.lot_id', '=', 'd.lot_id')
            ->join('projects as p', 'p.project_id', '=', 'd.project_id')
            ->join('school as s', 's.school_id', '=', 'd.school_id');

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
        // FILTERS
        // =========================
        if ($request->filled('status')) {
            $baseQuery->where('d.status', $request->status);
        }
        if ($request->filled('project')) {
            $baseQuery->where('d.project_id', $request->project);
        }
        if ($request->filled('lot')) {
            $baseQuery->where('d.lot_id', $request->lot);
        }
        if ($request->filled('region')) {
            $baseQuery->where('s.region', $request->region);
        }
        if ($request->filled('division')) {
            $baseQuery->where('s.division', $request->division);
        }
        if ($request->filled('municipality')) {
            $baseQuery->where('s.municipality', $request->municipality);
        }
        if (in_array($request->input('logistics_status'), ['pending', 'logistics_received', 'delivered'], true)) {
            $status = $request->input('logistics_status');
            $trackingMatch = function (Builder $trackingQuery): void {
                $trackingQuery->selectRaw('1')
                    ->from('logistics_dr_tracking as tracking')
                    ->whereColumn('tracking.project_id', 'd.project_id');
                if (in_array(DB::connection()->getDriverName(), ['mysql', 'mariadb'], true)) {
                    $trackingQuery->whereRaw('BINARY tracking.school_id = BINARY d.school_id')
                        ->whereRaw('BINARY tracking.dr_reference = BINARY d.dr_no');
                } else {
                    $trackingQuery->whereRaw('tracking.school_id = d.school_id COLLATE BINARY')
                        ->whereRaw('tracking.dr_reference = d.dr_no COLLATE BINARY');
                }
            };

            if ($status === 'pending') {
                if (Schema::hasTable('logistics_dr_tracking')) {
                    $baseQuery->whereNotExists($trackingMatch);
                }
            } elseif (Schema::hasTable('logistics_dr_tracking')) {
                $baseQuery->whereExists(function (Builder $trackingQuery) use ($trackingMatch, $status): void {
                    $trackingMatch($trackingQuery);
                    $trackingQuery->where('tracking.logistics_status', $status);
                });
            } else {
                $baseQuery->whereRaw('1 = 0');
            }
        }

        // =========================
        // TOTAL (DISTINCT DR NUMBERS, not delivery rows)
        // Pagination is now per DR# group, so count distinct dr_no.
        // =========================
        $scopedDrQuery = clone $baseQuery;
        if (in_array(DB::connection()->getDriverName(), ['mysql', 'mariadb'], true)) {
            $scopedDrQuery->selectRaw('d.project_id, BINARY d.school_id as school_id, BINARY d.dr_no as dr_no');
        } else {
            $scopedDrQuery->select('d.project_id', 'd.school_id', 'd.dr_no');
        }
        $scopedDrQuery->distinct();
        $total_rows = DB::query()->fromSub($scopedDrQuery, 'scoped_deliveries')->count();
        $total_pages = (int) ceil($total_rows / $limit);

        // =========================
        // PAGINATED DR NUMBERS ONLY
        // =========================
        $drScopesQuery = clone $baseQuery;
        if (in_array(DB::connection()->getDriverName(), ['mysql', 'mariadb'], true)) {
            $drScopesQuery->selectRaw('d.project_id, BINARY d.school_id as school_id, BINARY d.dr_no as dr_no');
        } else {
            $drScopesQuery->select('d.project_id', 'd.school_id', 'd.dr_no');
        }
        $drScopes = $drScopesQuery
            ->distinct()
            ->orderBy('d.dr_no', 'asc')
            ->orderBy('d.project_id')
            ->orderBy('d.school_id')
            ->limit($limit)
            ->offset($offset)
            ->get();

        // =========================
        // FULL DATA WITH ITEMS
        // only for paginated DR numbers
        // =========================
        $rows = DB::table('deliveries as d')
            ->leftJoin('keystage as k', 'k.keystage_id', '=', 'd.keystage_id')
            ->join('lot as l', 'l.lot_id', '=', 'd.lot_id')
            ->join('projects as p', 'p.project_id', '=', 'd.project_id')
            ->join('school as s', 's.school_id', '=', 'd.school_id')
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
            ->leftJoin('item as i', 'i.item_id', '=', 'pc.item_id')
            ->leftJoin('package_status as ps', function ($join) {
                $join->on('ps.delivery_id', '=', 'd.delivery_id')
                    ->on('ps.package_id', '=', 'pk.package_id');
            })
            ->where(function ($query) use ($drScopes): void {
                if ($drScopes->isEmpty()) {
                    $query->whereRaw('1 = 0');

                    return;
                }

                foreach ($drScopes as $scope) {
                    $query->orWhere(function ($scopeQuery) use ($scope): void {
                        $scopeQuery->where('d.project_id', $scope->project_id);
                        $this->whereExactColumnValue($scopeQuery, 'd.school_id', (string) $scope->school_id);
                        $this->whereExactColumnValue($scopeQuery, 'd.dr_no', (string) $scope->dr_no);
                    });
                }
            })
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
        $logisticsByScope = $this->latestLogisticsByScope($drScopes);

        foreach ($rows as $row) {

            $scopeKey = $this->deliveryScopeKey((int) $row->project_id, (string) $row->school_id, (string) $row->dr_no);

            if (! isset($grouped[$scopeKey])) {
                $grouped[$scopeKey] = [
                    'dr_no' => $row->dr_no,
                    'delivery_id' => $row->delivery_id,
                    'project_id' => $row->project_id,
                    'project_name' => $row->project_name,
                    'school_id' => $row->school_id,
                    'school_name' => $row->school_name,
                    'address' => $row->address,
                    'region' => $row->region,
                    'division' => $row->division,
                    'municipality' => $row->municipality,
                    'delivery_date' => $row->delivery_date,
                    'status' => $row->status,
                    'logistics' => $logisticsByScope[$scopeKey] ?? null,
                    'deliveries' => [],
                ];
            }

            $deliveryId = $row->delivery_id;

            if (! isset($grouped[$scopeKey]['deliveries'][$deliveryId])) {
                $delivery = clone $row;
                $delivery->packages = [];
                $grouped[$scopeKey]['deliveries'][$deliveryId] = $delivery;
            }

            $delivery = $grouped[$scopeKey]['deliveries'][$deliveryId];

            if (! empty($row->package_id)) {

                if (! isset($delivery->packages[$row->package_id])) {
                    $delivery->packages[$row->package_id] = [
                        'package_id' => $row->package_id,
                        'status' => $row->package_status ?: 'pending',
                        'items' => [],
                    ];
                }

                if (! empty($row->item_name)) {
                    $qty = (int) ($row->content_qty ?? 1) * (int) ($row->package_qty ?? 1);
                    $delivery->packages[$row->package_id]['items'][$row->item_name] =
                        $row->item_name.' ('.$qty.')';
                }
            }
        }

        // =========================
        // FINALIZE: package rn/total, clean items, reindex,
        // AND compute each DR group's overall status
        // (uniform across every package in every delivery under that DR;
        //  otherwise flagged as 'mixed').
        // =========================
        foreach ($grouped as &$g) {

            $allStatusesInGroup = [];

            foreach ($g['deliveries'] as &$delivery) {

                $packages = array_values($delivery->packages);
                usort($packages, fn ($a, $b) => $a['package_id'] <=> $b['package_id']);

                $total = count($packages);

                $delivery->packages = array_values(array_map(function ($pkg, $i) use ($total) {
                    return [
                        'package_num' => $i + 1,
                        'total_packages' => $total,
                        'status' => $pkg['status'],
                        'items' => array_values($pkg['items']),
                    ];
                }, $packages, array_keys($packages)));

                foreach ($delivery->packages as $pkg) {
                    $allStatusesInGroup[] = strtolower($pkg['status']);
                }

                unset($delivery->items_list);
            }
            unset($delivery);

            $g['deliveries'] = array_values($g['deliveries']);

            // Overall DR status: uniform status if every package agrees, else 'mixed'.
            // No packages at all -> null (nothing to report).
            if (empty($allStatusesInGroup)) {
                $g['overall_status'] = null;
            } else {
                $unique = array_unique($allStatusesInGroup);
                $g['overall_status'] = count($unique) === 1 ? $unique[0] : 'mixed';
            }
        }
        unset($g);

        // Project totals use the same DR status and billing rules as Operational Progress.
        $progressFilters = [
            'project_id' => (int) $request->input('project'),
            'active_only' => false,
            'compact' => true,
        ];
        foreach (['lot' => 'lot_id', 'region' => 'region', 'division' => 'division', 'municipality' => 'municipality'] as $filter => $metricFilter) {
            if ($request->filled($filter)) {
                $progressFilters[$metricFilter] = $request->input($filter);
            }
        }
        $progressSummary = $this->deliveryProgress->report($progressFilters)['summary'];
        $stats = [
            'total_pending' => (int) $progressSummary['pending_deliveries_count'],
            'total_stock_out' => max(0, (int) $progressSummary['stock_out_drs_count'] - (int) $progressSummary['completed_deliveries_count']),
            'total_delivered' => (int) $progressSummary['completed_deliveries_count'],
            'total_collection' => (int) $progressSummary['billing_drs_count'],
            'total_billed' => (int) $progressSummary['billed_delivery_drs_count'],
        ];

        // =========================
        // DROPDOWNS
        // =========================
        $lots = DB::table('lot')
            ->when($request->filled('project'), fn ($query) => $query->where('project_id', $request->project))
            ->when(! $request->filled('project') && $request->filled('lot'), fn ($query) => $query->where('lot_id', $request->lot))
            ->when(! $request->filled('project') && ! $request->filled('lot'), fn ($query) => $query->whereRaw('1 = 0'))
            ->orderBy('lot_name')
            ->get();

        return view('deliveries.index', [
            'grouped_deliveries' => $grouped,
            'projects' => $projects,
            'lots' => $lots,
            'page' => $page,
            'total_pages' => $total_pages,
            'total_rows' => $total_rows,
            'stats' => $stats,
        ]);
    }

    /**
     * @param  Collection<int, object>  $scopes
     * @return array<string, array{status: string, partner: string, occurred_at: string, reconciliation_status: string}>
     */
    private function latestLogisticsByScope(Collection $scopes): array
    {
        if ($scopes->isEmpty() || ! Schema::hasTable('logistics_dr_tracking')) {
            return [];
        }

        $query = DB::table('logistics_dr_tracking as tracking')
            ->leftJoin('logistics_integrations as integration', 'integration.integration_id', '=', 'tracking.integration_id')
            ->select([
                'tracking.project_id',
                'tracking.school_id',
                'tracking.dr_reference',
                'tracking.integration_id',
                'tracking.logistics_status',
                'tracking.event_occurred_at',
                'tracking.reconciliation_status',
                'tracking.latest_sequence',
                'integration.name as partner_name',
            ])
            ->where(function ($trackingQuery) use ($scopes): void {
                foreach ($scopes as $scope) {
                    $trackingQuery->orWhere(function ($scopeQuery) use ($scope): void {
                        $scopeQuery->where('tracking.project_id', $scope->project_id);
                        $this->whereExactColumnValue($scopeQuery, 'tracking.school_id', (string) $scope->school_id);
                        $this->whereExactColumnValue($scopeQuery, 'tracking.dr_reference', (string) $scope->dr_no);
                    });
                }
            })
            ->orderByDesc('tracking.event_occurred_at')
            ->orderByDesc('tracking.latest_sequence');

        $latestByScope = [];
        foreach ($query->get() as $tracking) {
            $key = $this->deliveryScopeKey((int) $tracking->project_id, (string) $tracking->school_id, (string) $tracking->dr_reference);
            if (isset($latestByScope[$key])) {
                continue;
            }

            $latestByScope[$key] = [
                'status' => (string) $tracking->logistics_status,
                'partner' => (string) ($tracking->partner_name ?? $tracking->integration_id ?? 'Unknown partner'),
                'occurred_at' => (string) $tracking->event_occurred_at,
                'reconciliation_status' => (string) $tracking->reconciliation_status,
            ];
        }

        return $latestByScope;
    }

    private function deliveryScopeKey(int $projectId, string $schoolId, string $drReference): string
    {
        return $projectId.':'.hash('sha256', $schoolId).':'.hash('sha256', $drReference);
    }

    private function whereExactColumnValue(Builder $query, string $column, string $value): void
    {
        if (in_array(DB::connection()->getDriverName(), ['mysql', 'mariadb'], true)) {
            $query->whereRaw('BINARY '.$column.' = BINARY ?', [$value]);

            return;
        }

        $query->whereRaw($column.' = ? COLLATE BINARY', [$value]);
    }

    // =========================
    // GENERATE QR PDF
    // =========================

    public function generate(Request $request)
    {
        $ids = collect(explode(',', $request->ids))
            ->map(fn ($v) => trim($v))
            ->filter(fn ($v) => is_numeric($v) && $v > 0)
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

            if (! empty($delivery->keystage_id)) {

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

                if (! $exists) {

                    DB::table('package_status')->insert([
                        'delivery_id' => $delivery->delivery_id,
                        'package_id' => $packageId,
                        'status' => 'pending',
                        'remarks' => null,
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

            if (! empty($delivery->keystage_id)) {

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

                if (! $status->package_status_id) {
                    continue;
                }

                $url = sprintf(
                    'https://philgeps.metro-mobilia.com/entry.php?id=%s&delivery_id=%s',
                    $status->package_status_id,
                    $delivery->delivery_id
                );

                $result = (new PngWriter)->write(
                    new QrCode($url)
                );

                $qrCodes[$status->package_status_id] =
                    'data:image/png;base64,'.
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
                        $status->package->packageContent as $content
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
        | Generate PDF - DR BY DR, BACK TO BACK
        |--------------------------------------------------------------------------
        |
        | Each DR # is rendered as its own standalone PDF using the
        | existing 'deliveries.ar-layout' view (unchanged), so per-DR
        | page count can actually be measured.
        |
        | ANY DR that renders to an ODD page count automatically gets
        | ONE blank page appended - unconditionally, including the very
        | last DR in the batch. This guarantees every DR's own block is
        | always even, so each DR always starts on an odd page number.
        |
        | The individual PDFs are then merged into a single final PDF
        | using FPDI (composer require setasign/fpdi-fpdf).
        |
        */

        $drGroups = $deliveries->groupBy(
            fn ($delivery) => $delivery->dr_no
        );

        $tempFiles = [];

        $mergedPdf = new Fpdi;

        $runningTotalPages = 0;

        try {

            foreach ($drGroups as $drNo => $drDeliveries) {

                /*
                |----------------------------------------------------------------
                | RENDER THIS DR'S PDF ALONE
                |----------------------------------------------------------------
                |
                | IMPORTANT: Barryvdh\DomPDF's Pdf::loadView() facade is
                | bound as a SINGLETON. Calling it repeatedly inside this
                | loop reuses the SAME underlying Dompdf object across
                | DRs, which can carry over internal render state
                | (counters/tree/canvas) from the previous DR - this is
                | why padding worked for a single DR but silently broke
                | for multiple. We build a brand new, fully isolated
                | Dompdf instance per DR instead.
                |
                */

                $html = view('deliveries.ar-layout', [
                    'deliveries' => $drDeliveries->values(),
                    'qrCodes' => $qrCodes,
                    'signerName' => Auth::user()?->name
                        ?? 'Authorized Representative',
                ])->render();

                $dompdfOptions = new Options;
                $dompdfOptions->set('isHtml5ParserEnabled', true);
                $dompdfOptions->set('isRemoteEnabled', true);
                $dompdfOptions->set('defaultPaperSize', 'legal');
                $dompdfOptions->set('defaultPaperOrientation', 'portrait');

                // Without this, Dompdf blocks local file access (e.g. your
                // logo image referenced via public_path() or a local asset
                // path) outside its chroot. Laravel's Pdf::loadView()
                // wrapper normally sets this for you from
                // config/dompdf.php - since we're bypassing that wrapper,
                // we need to set it ourselves.
                $dompdfOptions->set('chroot', [
                    base_path(),
                    public_path(),
                    storage_path(),
                ]);

                $dompdf = new Dompdf($dompdfOptions);
                $dompdf->setPaper('legal', 'portrait');
                $dompdf->loadHtml($html);
                $dompdf->render();

                $drPdfContent = $dompdf->output();

                unset($dompdf, $html);

                $tempPath = tempnam(sys_get_temp_dir(), 'ar_dr_').'.pdf';

                file_put_contents($tempPath, $drPdfContent);

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

                    // Normalize orientation ('portrait'/'landscape' -> 'P'/'L')
                    // so AddPage() always receives what FPDF/FPDI expects,
                    // regardless of how getTemplateSize() reports it.
                    $orientation = strtoupper(
                        substr((string) $lastSize['orientation'], 0, 1)
                    );

                    $mergedPdf->AddPage(
                        $orientation,
                        [$lastSize['width'], $lastSize['height']]
                    );

                    $mergedPdf->useTemplate($templateId);
                }

                $runningTotalPages += $pageCount;

                /*
                |----------------------------------------------------------------
                | ALWAYS PAD WITH A BLANK PAGE IF THIS DR IS ODD
                |----------------------------------------------------------------
                |
                | Unconditional - applies even when this is the only /
                | last DR in the batch, so a single-DR request still
                | gets padded correctly.
                |
                */

                $blankPagesAdded = 0;

                if ($pageCount % 2 !== 0 && $lastSize) {

                    $orientation = strtoupper(
                        substr((string) $lastSize['orientation'], 0, 1)
                    );

                    $mergedPdf->AddPage(
                        $orientation,
                        [$lastSize['width'], $lastSize['height']]
                    );

                    $blankPagesAdded = 1;
                    $runningTotalPages += 1;
                }

                Log::info(sprintf(
                    'AR generate(): DR %s | own page count=%d | additional blank page=%d | total page (this DR)=%d | running total=%d',
                    $drNo,
                    $pageCount,
                    $blankPagesAdded,
                    $pageCount + $blankPagesAdded,
                    $runningTotalPages
                ));
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
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="deliveries-batch.pdf"',
        ]);
    }

    // =========================
    // GENERATE LABELS PDF
    // =========================

    public function generateLabels(Request $request, DeliveryBillingQrCodeService $billingQrCodes)
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

        if (! $projectId) {
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

            if (! isset($data[$deliveryProjectId])) {

                $data[$deliveryProjectId] = [

                    'info' => [

                        'project_id' => $delivery->project_id,

                        'project_name' => trim(
                            (string) (
                                $delivery->project_name ?? ''
                            )
                        ),

                        'division' => trim(
                            (string) (
                                $delivery->division ?? ''
                            )
                        ),

                        'region' => trim(
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

            if (! isset(
                $data[$deliveryProjectId]['schools'][$schoolId]
            )) {

                $data[$deliveryProjectId]['schools'][$schoolId] = [

                    'info' => [

                        'school_name' => trim(
                            (string) (
                                $delivery->school_name ?? ''
                            )
                        ),

                        'school_id' => $delivery->school_id ?? '',

                        'municipality' => trim(
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

                $lotKey = 'lot-'.$lotId;

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

            if (! isset(
                $data[$deliveryProjectId]['schools'][$schoolId]['lots'][$lotKey]
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

                if (! isset($lotQrCache[$lotQrValue])) {

                    $lotQrResult = (new PngWriter)->write(
                        new QrCode($lotQrValue)
                    );

                    $lotQrCache[$lotQrValue] =
                        'data:image/png;base64,'.
                        base64_encode($lotQrResult->getString());
                }

                $data[$deliveryProjectId]['schools'][$schoolId]['lots'][$lotKey] = [

                    'lot_id' => $lotId,

                    'lot_name' => $lotName,

                    'lot_qr' => $lotQrCache[$lotQrValue],

                    'keystages' => [],
                    'delivery_ids' => [],
                    'delivery_qrs' => [],
                ];
            }

            $data[$deliveryProjectId]['schools'][$schoolId]['lots'][$lotKey]['delivery_ids'][] = $deliveryId;

            /*
            |--------------------------------------------------------------------------
            | 9. DELIVERY KEYSTAGE
            |--------------------------------------------------------------------------
            */

            $deliveryKeystageId =
                ! empty($delivery->keystage_id)
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
                        ? 'keystage-'.$keystageId
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
                            'Keystage '.$number;
                    }

                    if ($description !== '') {

                        $parts[] =
                            $description;
                    }

                    $keystageLabel = ! empty($parts)
                        ? implode(' - ', $parts)
                        : 'Keystage '.$keystageId;

                } else {

                    $keystageLabel = 'No Keystage';
                }

                /*
                |--------------------------------------------------------------------------
                | 15. CREATE KEYSTAGE
                |--------------------------------------------------------------------------
                */

                if (! isset(
                    $data[$deliveryProjectId]['schools'][$schoolId]['lots'][$lotKey]['keystages'][$keystageKey]
                )) {

                    $data[$deliveryProjectId]['schools'][$schoolId]['lots'][$lotKey]['keystages'][$keystageKey] = [

                        'keystage_id' => $keystageId,

                        'label' => $keystageLabel,

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
                        'item-'.$itemId;

                    /*
                    |--------------------------------------------------------------------------
                    | GET CURRENT ITEMS
                    |--------------------------------------------------------------------------
                    */

                    $items =
                        $data[$deliveryProjectId]['schools'][$schoolId]['lots'][$lotKey]['keystages'][$keystageKey]['items'];

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

                            'item_id' => $itemId,

                            'item_name' => $itemName,

                            'qty' => $finalQty,

                            'unit' => trim(
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

                    $data[$deliveryProjectId]['schools'][$schoolId]['lots'][$lotKey]['keystages'][$keystageKey]['items'] = $items;
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
                $aDeliveryId = ! empty($a['delivery_ids'])
                    ? min($a['delivery_ids'])
                    : PHP_INT_MAX;

                $bDeliveryId = ! empty($b['delivery_ids'])
                    ? min($b['delivery_ids'])
                    : PHP_INT_MAX;

                return $aDeliveryId <=> $bDeliveryId;
            });

            foreach (
                $project['schools'] as $schoolId => &$school
            ) {

                foreach (
                    $school['lots'] as $lotKey => &$lot
                ) {

                    foreach (
                        $lot['keystages'] as $keystageKey => &$keystage
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

        $drNumbersWithBillingQr = [];
        $drNumberByDeliveryId = $deliveries->mapWithKeys(
            fn (object $delivery): array => [(int) $delivery->delivery_id => trim((string) $delivery->dr_no)]
        );

        foreach ($data as &$project) {
            foreach ($project['schools'] as &$school) {
                foreach ($school['lots'] as &$lot) {
                    foreach ($lot['delivery_ids'] as $deliveryId) {
                        $drNumber = $drNumberByDeliveryId[$deliveryId] ?? '';
                        if ($drNumber === '') {
                            continue;
                        }

                        $drNumberKey = 'dr:'.$drNumber;
                        if (isset($drNumbersWithBillingQr[$drNumberKey])) {
                            continue;
                        }

                        $deliveryQr = (new PngWriter)->write(
                            new QrCode($billingQrCodes->makeDrNumberPayload($drNumber))
                        );

                        $lot['delivery_qrs'][$drNumberKey] = [
                            'dr_no' => $drNumber,
                            'qr' => 'data:image/png;base64,'.base64_encode($deliveryQr->getString()),
                        ];
                        $drNumbersWithBillingQr[$drNumberKey] = true;
                    }
                }
                unset($lot);
            }
            unset($school);
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
        | 19b. DETERMINE WHICH SCHOOLS NEED A BLANK PAGE
        |--------------------------------------------------------------------------
        |
        | Each school prints as its own section (page-break-before on every new
        | school), so each school's own page count must be even. Rendering one
        | school in isolation gives the same page count it will have inside the
        | full combined document, since nothing before or after it affects its
        | own layout. We flag odd ones here, then do ONE final render.
        |
        */

        foreach ($data as $projectId => &$project) {

            foreach ($project['schools'] as $schoolId => &$school) {

                $countViewData = [
                    'data' => [
                        $projectId => [
                            'info' => $project['info'],
                            'schools' => [$schoolId => $school],
                        ],
                    ],
                    'showSchoolID' => $showSchoolID,
                    'showMunicipality' => $showMunicipality,
                    'showDivision' => $showDivision,
                    'showRegion' => $showRegion,
                ];

                $countPdf = Pdf::loadView(
                    'deliveries.label-layout',
                    $countViewData
                )
                    ->setPaper('a4', 'portrait');

                $countPdf->render();

                $schoolPageCount = $countPdf->getDomPDF()
                    ->getCanvas()
                    ->get_page_count();

                $school['needs_blank_page'] = ($schoolPageCount % 2 !== 0);
            }

            unset($school);
        }

        unset($project);

        /*
        |--------------------------------------------------------------------------
        | 20. GENERATE PDF
        |--------------------------------------------------------------------------
        */

        return Pdf::loadView(
            'deliveries.label-layout',
            [
                'data' => $data,
                'showSchoolID' => $showSchoolID,
                'showMunicipality' => $showMunicipality,
                'showDivision' => $showDivision,
                'showRegion' => $showRegion,
            ]
        )
            ->setPaper('a4', 'portrait')
            ->stream(
                'Packing_List_'.now()->format('Ymd_His').'.pdf'
            );
    }
}
