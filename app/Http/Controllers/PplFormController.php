<?php

namespace App\Http\Controllers;

use App\Http\Requests\ImportPplFormRequest;
use App\Http\Requests\StorePplFormRequest;
use App\Models\Lot as LegacyLot;
use App\Models\PplForm;
use App\Models\Project;
use App\Models\ProjectInformation;
use App\Models\ProjectLot;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class PplFormController extends Controller
{
    public function index(Request $request)
    {
        $query = PplForm::query();

        // ===============================
        // SEARCH
        // ===============================
        if ($request->filled('search')) {
            $search = $request->search;

            $query->where(function ($q) use ($search) {
                $q->where('project_code', 'like', "%{$search}%")
                    ->orWhere('project_title', 'like', "%{$search}%")
                    ->orWhere('project_id_no', 'like', "%{$search}%")
                    ->orWhere('region', 'like', "%{$search}%")
                    ->orWhere('bidder', 'like', "%{$search}%");
            });
        }

        // ===============================
        // SORTING (SAFE)
        // ===============================
        $allowedSorts = [
            'id',
            'project_code',
            'lot_number',
            'project_title',
            'project_id_no',
            'region',
            'bid_opening',
            'abc',
            'bidder',
        ];

        $sortBy = $request->get('sort_by', 'id');
        $sortOrder = $request->get('sort_order', 'desc');

        $sortBy = in_array($sortBy, $allowedSorts) ? $sortBy : 'id';
        $sortOrder = $sortOrder === 'asc' ? 'asc' : 'desc';

        $query->orderBy($sortBy, $sortOrder);

        // ===============================
        // PAGINATION
        // ===============================
        $data = $query->paginate(10)->withQueryString();

        return view('finance.ppl_forms.index', compact('data'));
    }

    public function create()
    {
        Gate::authorize('viewAny', ProjectInformation::class);

        $biddingProjects = ProjectInformation::query()
            ->with(['lots:id,project_id,lot_no,region'])
            ->orderBy('project_name')
            ->get(['id', 'project_id', 'project_code', 'project_name', 'region', 'date_of_bid_opening', 'approved_budget_contract_abc']);

        $legacyProjectLots = collect();
        if (Schema::hasTable('projects') && Schema::hasTable('lot')) {
            $legacyProjectLots = DB::table('project_information as bidding')
                ->join('projects as project', 'project.project_id', '=', 'bidding.project_id')
                ->join('lot as legacy_lot', 'legacy_lot.project_id', '=', 'project.project_id')
                ->whereNotNull('bidding.project_id')
                ->whereNotExists(fn ($query) => $query->selectRaw('1')->from('lots as bidding_lot')->whereColumn('bidding_lot.project_id', 'bidding.id'))
                ->orderBy('bidding.project_name')
                ->orderBy('legacy_lot.lot_name')
                ->get([
                    'bidding.id as bidding_id', 'bidding.project_id as bidding_identifier', 'bidding.project_name as bidding_name',
                    'bidding.region as bidding_region', 'bidding.date_of_bid_opening', 'project.project_id as project_id',
                    'project.project_name as project_name', 'legacy_lot.lot_id as lot_id', 'legacy_lot.lot_name as lot_name',
                ]);
        }

        return view('finance.ppl_forms.create', compact('biddingProjects', 'legacyProjectLots'));
    }

    public function sourceMapping(ProjectInformation $bidding, ProjectLot $lot): JsonResponse
    {
        Gate::authorize('view', $bidding);

        abort_unless((int) $lot->project_id === (int) $bidding->getKey(), 404);

        return response()->json([
            'project_title' => $bidding->project_name,
            'lot_number' => $lot->lot_no,
            'region' => $lot->region,
            'bid_opening' => $bidding->date_of_bid_opening,
        ]);
    }

    public function legacyLotSourceMapping(ProjectInformation $bidding, Project $project, LegacyLot $lot): JsonResponse
    {
        Gate::authorize('view', $bidding);

        abort_unless(is_numeric($bidding->project_id)
            && (string) $project->getKey() === trim((string) $bidding->project_id)
            && (int) $lot->project_id === (int) $project->getKey()
            && ! $bidding->lots()->exists(), 404);

        return response()->json([
            'project_title' => $bidding->project_name,
            'lot_number' => $lot->lot_name,
            'region' => null,
            'bid_opening' => $bidding->date_of_bid_opening,
        ]);
    }

    public function store(StorePplFormRequest $request)
    {
        PplForm::create($request->validated());

        return redirect()->route('ppl_forms.index')->with('success', 'Saved successfully');
    }

    public function import(ImportPplFormRequest $request)
    {
        $request->validated();

        $file = $request->file('file');
        $spreadsheet = IOFactory::load($file->getPathname());
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray();

        $inserted = 0;
        $failed = 0;

        $headerRow = $this->findHeaderRow($rows);
        if ($headerRow === null) {
            return back()->withErrors(['file' => 'The workbook needs a recognizable project code and lot header row.']);
        }

        [$headerIndex, $columns] = $headerRow;
        foreach (array_slice($rows, $headerIndex + 1) as $row) {
            $projectCode = $this->clean($row[$columns['project_code']] ?? null);
            $lotNumber = $this->clean($row[$columns['lot_number']] ?? null);
            $projectTitle = $this->clean($row[$columns['project_title']] ?? null);
            if ($projectCode === null || $lotNumber === null || $projectTitle === null) {
                continue;
            }

            try {
                PplForm::create([
                    'project_code' => $projectCode,
                    'lot_number' => $lotNumber,
                    'project_title' => $projectTitle,
                    'project_id_no' => $this->clean($row[$columns['project_id_no']] ?? null),
                    'region' => $this->clean($row[$columns['region']] ?? null),
                    'bid_opening' => $this->cleanDate($row[$columns['bid_opening']] ?? null),
                    'abc' => $this->cleanNumber($row[$columns['abc']] ?? null),
                    'bidder' => $this->clean($row[$columns['bidder']] ?? null),
                    'authorized_signatory' => $this->clean($row[$columns['authorized_signatory']] ?? null),
                ]);
                $inserted++;
            } catch (\Throwable $exception) {
                report($exception);
                $failed++;
            }
        }

        return back()->with('success',
            "Import completed. Inserted: $inserted | Failed: $failed"
        );
    }

    // ---------------- HELPERS ----------------

    private function clean(mixed $value): ?string
    {
        if ($value === null || trim((string) $value) === '') {
            return null;
        }

        return trim(mb_convert_encoding($value, 'UTF-8', 'auto'));
    }

    private function cleanDate(mixed $value): ?string
    {
        if ($value === null || trim((string) $value) === '') {
            return null;
        }

        try {
            if (is_numeric($value)) {
                return ExcelDate::excelToDateTimeObject((float) $value)->format('Y-m-d');
            }

            $timestamp = strtotime((string) $value);

            return $timestamp === false ? null : date('Y-m-d', $timestamp);
        } catch (\Exception $e) {
            return null;
        }
    }

    private function cleanNumber(mixed $value): ?float
    {
        if ($value === null || trim((string) $value) === '') {
            return null;
        }

        $normalized = str_replace([',', ' ', '₱'], '', (string) $value);

        return is_numeric($normalized) ? (float) $normalized : null;
    }

    /** @param array<int, array<int, mixed>> $rows
     * @return array{int, array<string, int>}|null
     */
    private function findHeaderRow(array $rows): ?array
    {
        $requiredHeaders = [
            'project_code' => ['PROJECTCODE'],
            'lot_number' => ['LOT', 'LOTNUMBER', 'LOTNO'],
            'project_title' => ['PROJECTTITLE', 'PROJECTNAME'],
            'project_id_no' => ['PROJECTIDNO', 'PROJECTIDNUMBER'],
            'region' => ['REGION'],
            'bid_opening' => ['BIDOPENING', 'DATEOFBIDOPENING'],
            'abc' => ['ABC', 'APPROVEDBUDGETCONTRACT'],
            'bidder' => ['BIDDER'],
            'authorized_signatory' => ['AUTHORIZEDSIGNATORY'],
        ];

        foreach (array_slice($rows, 0, 10, true) as $rowIndex => $row) {
            $normalized = [];
            foreach ($row as $columnIndex => $value) {
                if (is_string($value)) {
                    $normalized[preg_replace('/[^A-Z0-9]/', '', mb_strtoupper($value))] = $columnIndex;
                }
            }

            $columns = [];
            foreach ($requiredHeaders as $field => $aliases) {
                foreach ($aliases as $alias) {
                    if (array_key_exists($alias, $normalized)) {
                        $columns[$field] = $normalized[$alias];
                        break;
                    }
                }
            }

            if (isset($columns['project_code'], $columns['lot_number'], $columns['project_title'])) {
                foreach (['project_id_no', 'region', 'bid_opening', 'abc', 'bidder', 'authorized_signatory'] as $optionalField) {
                    $columns[$optionalField] ??= -1;
                }

                return [$rowIndex, $columns];
            }
        }

        return null;
    }

    public function show($id)
    {
        $data = PplForm::findOrFail($id);

        return view('finance.ppl_forms.show', compact('data'));
    }
}
