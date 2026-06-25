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
use App\Models\PackageStatus;
class DeliveryController extends Controller
{
    public function index(Request $request)
    {
        $limit = 10;
        $page = max(1, (int) $request->get('page', 1));

        // =========================
        // ELOQUENT BASE QUERY (FIX)
        // =========================
        $query = Delivery::query()
            ->with([
                'school',
                'project',
                'lot',
                'keystage',
                'items.packageContent.item',
            ]);

        // =========================
        // SEARCH
        // =========================
        if ($request->filled('search')) {
            $search = $request->search;

            $query->where(function ($q) use ($search) {
                $q->where('dr_no', 'like', "%{$search}%")
                ->orWhereHas('project', fn($p) => $p->where('project_name', 'like', "%{$search}%"))
                ->orWhereHas('school', fn($s) => $s->where('school_name', 'like', "%{$search}%"))
                ->orWhereHas('lot', fn($l) => $l->where('lot_name', 'like', "%{$search}%"));
            });
        }

        // =========================
        // FILTERS
        // =========================
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('project')) {
            $query->where('project_id', $request->project);
        }

        if ($request->filled('lot')) {
            $query->where('lot_id', $request->lot);
        }

        if ($request->filled('region')) {
            $query->whereHas('school', fn($s) =>
                $s->where('region', $request->region)
            );
        }

        if ($request->filled('division')) {
            $query->whereHas('school', fn($s) =>
                $s->where('division', $request->division)
            );
        }

        if ($request->filled('municipality')) {
            $query->whereHas('school', fn($s) =>
                $s->where('municipality', $request->municipality)
            );
        }

        if ($request->filled('year')) {
            $query->whereYear('delivery_date', $request->year);
        }

        // =========================
        // PAGINATION
        // =========================
        $total_rows = (clone $query)->count();
        $total_pages = (int) ceil($total_rows / $limit);

        $deliveries = $query
            ->orderBy('status')
            ->orderBy('delivery_date')
            ->skip(($page - 1) * $limit)
            ->take($limit)
            ->get();

        // =========================
        // GROUP BY DR (CLEAN)
        // =========================
        $grouped = [];

        foreach ($deliveries as $delivery) {

            $dr = $delivery->dr_no;

            if (!isset($grouped[$dr])) {
                $grouped[$dr] = [
                    'dr_no' => $dr,
                    'delivery_id' => $delivery->delivery_id,
                    'project_id' => $delivery->project_id,
                    'project_name' => $delivery->project?->project_name,
                    'school_id' => $delivery->school_id,
                    'school_name' => $delivery->school?->school_name,
                    'address' => $delivery->school?->address,
                    'region' => $delivery->school?->region,
                    'division' => $delivery->school?->division,
                    'municipality' => $delivery->school?->municipality,
                    'delivery_date' => $delivery->delivery_date,
                    'status' => $delivery->status,
                    'deliveries' => []
                ];
            }

            // 🔥 SAFE: attach items label here (NO stdClass issue anymore)
            $delivery->items_contents = $delivery->items
                ->pluck('item_name')
                ->filter()
                ->implode(', ');

            $grouped[$dr]['deliveries'][] = $delivery;
        }

        // =========================
        // DROPDOWNS
        // =========================
        $projects = \App\Models\Project::all();

        $regions = \App\Models\School::select('region')
            ->distinct()
            ->orderBy('region')
            ->get();

        return view('deliveries.index', [
            'grouped_deliveries' => $grouped,
            'projects' => $projects,
            'regions' => $regions,
            'years' => DB::table('deliveries')
                ->selectRaw('YEAR(delivery_date) as year')
                ->distinct()
                ->orderBy('year', 'desc')
                ->pluck('year'),
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
            'packageStatuses.package.packageContent.item',
        ])
        ->whereIn('delivery_id', $ids)
        ->orderBy('dr_no')
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
                if ($delivery->packageStatuses->isEmpty()) {

                    // 1. Get package IDs safely
                    $packageIds = DB::table('package')
                        ->where('lot_id', $delivery->lot_id)
                        ->pluck('package_id');

                    foreach ($packageIds as $packageId) {

                        // 2. Prevent duplicate insert
                        $exists = DB::table('package_status')
                            ->where('delivery_id', $delivery->delivery_id)
                            ->where('package_id', $packageId)
                            ->exists();

                        if (!$exists) {
                            DB::table('package_status')->insert([
                                'delivery_id' => $delivery->delivery_id,
                                'package_id' => $packageId,
                                'status' => 'pending',
                                'remarks' => null,
                            ]);
                        }
                    }

                    // 3. FORCE reload relationship (important)
                    $delivery->unsetRelation('packageStatuses');

                    $delivery->load([
                        'packageStatuses.package.packageContent.item'
                    ]);
                }
            $delivery->ar = $delivery->project->arSetting ?? null;

            // ALWAYS rebuild clean from DB
            $statuses = PackageStatus::with([
                'package.packageContent.item'
            ])
            ->where('delivery_id', $delivery->delivery_id)
            ->get();

            $delivery->setRelation('packageStatuses', $statuses);

            // ❗ DO NOT skip delivery
            foreach ($statuses as $status) {
                if (!$status->package_status_id) {
                    continue;
                }
                // ================= QR =================
                $url = "https://mmc.metro-ltd.com/entry.php?id="
                    . $status->package_status_id
                    . "&delivery_id="
                    . $delivery->delivery_id;

                $result = (new PngWriter())
                    ->write(new QrCode($url));

                $qrCodes[$status->package_status_id] =
                    'data:image/png;base64,' . base64_encode($result->getString());

                // ================= ITEMS (FIXED) =================
                $items = $status->package?->packageContent?->pluck('item') ?? collect();

                $itemNames = $items->pluck('item_name')->filter();

                // ================= LABEL =================
                $status->qr_label = $itemNames->isNotEmpty()
                    ? $itemNames->implode(', ')
                    : 'Unknown Item';
            }
        }

        return Pdf::loadView('deliveries.ar-layout', [
            'deliveries' => $deliveries,
            'qrCodes' => $qrCodes,
            'signerName' => auth()->user()?->name ?? 'Authorized Representative'
        ])
        ->setPaper('legal', 'portrait')
        ->stream('deliveries-batch.pdf');
    }
}