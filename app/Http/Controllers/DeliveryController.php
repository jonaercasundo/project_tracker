<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;
use Endroid\QrCode\QrCode;
use App\Models\Delivery;
use Illuminate\Support\Facades\Auth;

use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel\ErrorCorrectionLevelLow;
use Endroid\QrCode\RoundBlockSizeMode\RoundBlockSizeMode;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Writer\PngWriter;
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
        $years = DB::table('deliveries')
            ->selectRaw('YEAR(delivery_date) as year')
            ->distinct()
            ->orderBy('year', 'desc')
            ->pluck('year');

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

        // =========================
        // LOCATION FILTERS (BASED ON SCHOOL TABLE)
        // =========================
        if ($request->filled('region')) {
            $baseQuery->where('s.region', $request->region);
        }

        if ($request->filled('division')) {
            $baseQuery->where('s.division', $request->division);
        }

        if ($request->filled('municipality')) {
            $baseQuery->where('s.municipality', $request->municipality);
        }
        if ($request->filled('year')) {
            $baseQuery->whereYear('d.delivery_date', $request->year);
        }
        // =========================
        // TOTAL
        // =========================
        $total_rows = (clone $baseQuery)->count();
        $total_pages = (int) ceil($total_rows / $limit);

        // =========================
        // DATA
        // =========================
        $deliveries = $baseQuery
            ->select(
                'd.*',
                'p.project_name',
                's.school_name',
                's.address',
                's.region',
                's.division',
                's.municipality',
                'k.keystage_num',
                'k.description',
                'l.lot_name'
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
                    'region' => $row->region,
                    'division' => $row->division,
                    'municipality' => $row->municipality,
                    'delivery_date' => $row->delivery_date,
                    'status' => $row->status,
                    'deliveries' => []
                ];
            }

            $grouped[$dr]['deliveries'][] = $row;
        }

        // =========================
        // DROPDOWNS (FROM SCHOOL TABLE)
        // =========================
        $projects = DB::table('projects')->get();

        $regions = DB::table('school')
            ->select('region')
            ->distinct()
            ->orderBy('region')
            ->get();

        return view('deliveries.index', [
            'grouped_deliveries' => $grouped,
            'projects' => $projects,
            'regions' => $regions,
            'years' => $years,
            'page' => $page,
            'total_pages' => $total_pages,
            'total_rows' => $total_rows
        ]);
    }
    public function generate(Request $request)
    {
        $ids = collect(explode(',', $request->ids))
            ->map(fn($v) => trim($v))
            ->filter(fn($v) => is_numeric($v) && $v > 0)
            ->values();

        if ($ids->isEmpty()) {
            abort(422, "Invalid DR numbers.");
        }

        // =========================
        // EAGER LOAD (NO N+1)
        // =========================
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
        ])
        ->whereIn('dr_no', $ids)
        ->orderBy('dr_no')
        ->limit(1)
        ->get();


        if ($deliveries->isEmpty()) {
            abort(404, "No deliveries found.");
        }

        // =========================
        // REUSED QR WRITER (FAST)
        // =========================
        $writer = new PngWriter();
        $qrCodes = [];

        foreach ($deliveries as $delivery) {

            $ar = $delivery->project->arSetting ?? null;

            $packageCount = $delivery->packageStatuses->count();
            $i = 1;

            foreach ($delivery->packageStatuses as $status) {

                $url = "https://mmc.metro-ltd.com/entry.php?id="
                    . $status->package_status_id
                    . "&delivery_id="
                    . $delivery->delivery_id;

                $result = Builder::create()
                    ->writer(new PngWriter())
                    ->data($url)
                    ->size(150)
                    ->margin(0)
                    ->build();

                $qrCodes[$status->package_status_id] = $result->getDataUri();

                $status->package_label = "Package {$i} of {$packageCount}";

                $i++;
            }

            // attach AR config to delivery (LIKE PHP VERSION)
            $delivery->ar = $ar;
        }
        dd([
            'package_status_count' => $deliveries->first()->packageStatuses->count(),
            'qr_count' => count($qrCodes),
            'sample_qr' => reset($qrCodes),
            $deliveries->first()->delivery_id,
            $deliveries->first()->dr_no
        ]);

        return Pdf::loadView('deliveries.ar-layout', [
            'deliveries' => $deliveries,
            'qrCodes' => $qrCodes,
            'signerName' => auth()->user()?->name ?? 'Authorized Representative'
        ])
        ->setPaper('legal', 'portrait')
        ->stream('deliveries-batch.pdf');
    }
}