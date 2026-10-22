<?php

namespace App\Http\Controllers;

use App\Http\Requests\ImportPplFormRequest;
use App\Http\Requests\StorePplFormRequest;
use App\Http\Requests\UpdatePplFormRequest;
use App\Models\Lot as LegacyLot;
use App\Models\MmcPplSourceLink;
use App\Models\PplForm;
use App\Models\Project;
use App\Models\ProjectFinancialPlan;
use App\Models\ProjectInformation;
use App\Models\ProjectLot;
use App\Services\DashboardService;
use App\Services\MmcPplSourceLinkService;
use App\Services\PplFormFinancialSummary;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class PplFormController extends Controller
{
    public function index(Request $request, DashboardService $dashboards, PplFormFinancialSummary $financialSummary): View
    {
        $company = $dashboards->currentCompany($request->user(), $request->session());
        abort_unless($company?->code === 'MMC', 403);
        Gate::authorize('viewAny', ProjectInformation::class);

        $hasSourceLinks = Schema::hasTable('mmc_ppl_source_links');
        $hasFinancialPlans = Schema::hasTable('project_financial_plans')
            && Schema::hasColumn('project_financial_plans', 'ppl_form_id')
            && Schema::hasColumn('project_financial_plans', 'company_id')
            && Schema::hasColumn('project_financial_plans', 'deleted_at');
        $query = PplForm::query();
        if (Schema::hasColumn('ppl_forms', 'company_id')) {
            $query->where(fn ($ownerQuery) => $ownerQuery->whereNull('company_id')->orWhere('company_id', $company->company_id));
        }
        $optionQuery = clone $query;

        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:120'],
            'status' => ['nullable', Rule::in([...array_keys(config('project_lifecycle.project_status_mapping')), 'Unlinked'])],
            'year' => ['nullable', 'integer', 'between:1901,2155'],
            'lot' => ['nullable', 'string', 'max:255'],
            'linkage' => ['nullable', Rule::in(['linked', 'unlinked'])],
            'bidding_id' => ['nullable', 'integer', Rule::when($hasSourceLinks, ['exists:project_information,id'])],
            'financial_plan' => ['nullable', Rule::in(['linked', 'unlinked'])],
            'created_from' => ['nullable', 'date'],
            'created_to' => ['nullable', 'date', 'after_or_equal:created_from'],
            'sort_by' => ['nullable', Rule::in(['project_title', 'project_code', 'project_id_no', 'lot_number', 'created_at', 'abc', 'id'])],
            'sort_order' => ['nullable', Rule::in(['asc', 'desc'])],
            'per_page' => ['nullable', Rule::in(['10', '25', '50'])],
        ]);

        $verifiedLink = function ($links) use ($company): void {
            $links->where('match_status', 'verified');
            if (Schema::hasColumn('mmc_ppl_source_links', 'company_id')) {
                $links->where('company_id', $company->company_id);
            }
            if (Schema::hasColumn('projects', 'company_id')) {
                $links->whereHas('project', fn ($projects) => $projects->whereNull('company_id')->orWhere('company_id', $company->company_id));
            }
            if (Schema::hasColumn('project_information', 'company_id')) {
                $links->whereHas('bidding', fn ($biddings) => $biddings->whereNull('company_id')->orWhere('company_id', $company->company_id));
            }
        };

        if (($search = trim((string) ($filters['search'] ?? ''))) !== '') {
            $query->where(function ($q) use ($search, $hasSourceLinks, $verifiedLink): void {
                $q->where('project_code', 'like', "%{$search}%")
                    ->orWhere('project_title', 'like', "%{$search}%")
                    ->orWhere('project_id_no', 'like', "%{$search}%")
                    ->orWhere('lot_number', 'like', "%{$search}%")
                    ->orWhere('region', 'like', "%{$search}%")
                    ->orWhere('bidder', 'like', "%{$search}%");

                if (is_numeric($search)) {
                    $q->orWhere('id', (int) $search);
                }
                if ($hasSourceLinks) {
                    $q->orWhereHas('sourceLink', function ($links) use ($verifiedLink, $search): void {
                        $verifiedLink($links);
                        $links->where(function ($sourceQuery) use ($search): void {
                            $sourceQuery->whereHas('project', fn ($projects) => $projects->where('project_name', 'like', "%{$search}%")
                                ->orWhere('project_code', 'like', "%{$search}%")
                                ->orWhere('ref_no', 'like', "%{$search}%")
                                ->orWhere('agency', 'like', "%{$search}%"))
                                ->orWhereHas('bidding', fn ($biddings) => $biddings->where('project_name', 'like', "%{$search}%")
                                    ->orWhere('project_code', 'like', "%{$search}%")
                                    ->orWhere('project_id', 'like', "%{$search}%")
                                    ->orWhere('procuring_entity', 'like', "%{$search}%"));
                        });
                    });
                }
            });
        }

        if (isset($filters['year']) && Schema::hasColumn('ppl_forms', 'year_of_revenue_recognition')) {
            $query->where('year_of_revenue_recognition', $filters['year']);
        }
        if (isset($filters['lot'])) {
            $query->where('lot_number', $filters['lot']);
        }
        if (isset($filters['linkage']) && $hasSourceLinks) {
            $linkQuery = function ($links) use ($verifiedLink): void {
                $verifiedLink($links);
                $links->whereNotNull('project_id');
            };
            if ($filters['linkage'] === 'linked') {
                $query->whereHas('sourceLink', $linkQuery);
            } else {
                $query->whereDoesntHave('sourceLink', $linkQuery);
            }
        } elseif (($filters['linkage'] ?? null) === 'linked') {
            $query->whereRaw('1 = 0');
        } elseif (($filters['linkage'] ?? null) === 'unlinked' && ! $hasSourceLinks) {
            // Without durable source links, the existing forms are unlinked.
        }
        if (isset($filters['bidding_id']) && $hasSourceLinks) {
            $query->whereHas('sourceLink', function ($links) use ($verifiedLink, $filters): void {
                $verifiedLink($links);
                $links->where('project_information_id', $filters['bidding_id']);
            });
        }
        if (isset($filters['status'])) {
            if ($hasSourceLinks && Schema::hasTable('projects') && Schema::hasColumn('projects', 'status')) {
                if ($filters['status'] === 'Unlinked') {
                    $query->whereDoesntHave('sourceLink', function ($links) use ($verifiedLink): void {
                        $verifiedLink($links);
                        $links->whereNotNull('project_id');
                    });
                } else {
                    $query->whereHas('sourceLink', function ($links) use ($verifiedLink, $filters): void {
                        $verifiedLink($links);
                        $links->whereNotNull('project_id')->whereHas('project', fn ($projects) => $projects->where('status', $filters['status']));
                    });
                }
            } elseif ($filters['status'] !== 'Unlinked') {
                $query->whereRaw('1 = 0');
            }
        }
        if (isset($filters['financial_plan']) && $hasFinancialPlans) {
            $planFormIds = ProjectFinancialPlan::query()->where('company_id', $company->company_id)->whereNotNull('ppl_form_id');
            if (Schema::hasColumn('project_financial_plans', 'deleted_at')) {
                $planFormIds->whereNull('deleted_at');
            }
            if ($filters['financial_plan'] === 'linked') {
                $query->whereIn('ppl_forms.id', $planFormIds->select('ppl_form_id'));
            } else {
                $query->whereNotIn('ppl_forms.id', $planFormIds->select('ppl_form_id'));
            }
        }
        if (isset($filters['created_from'])) {
            $query->whereDate('created_at', '>=', $filters['created_from']);
        }
        if (isset($filters['created_to'])) {
            $query->whereDate('created_at', '<=', $filters['created_to']);
        }

        $summary = $hasFinancialPlans ? $financialSummary->forFilteredForms(clone $query, (int) $company->company_id) : null;

        $sortBy = $filters['sort_by'] ?? 'id';
        $sortOrder = $filters['sort_order'] ?? 'desc';
        $query->orderBy($sortBy, $sortOrder)->orderBy('id', 'desc');
        if ($hasFinancialPlans) {
            $query->withCount(['financialPlans as has_financial_plan' => function ($plans) use ($company): void {
                $plans->where('company_id', $company->company_id);
                if (Schema::hasColumn('project_financial_plans', 'deleted_at')) {
                    $plans->whereNull('deleted_at');
                }
            }]);
            $query->with(['financialPlans' => fn ($plans) => $plans->where('company_id', $company->company_id)->with('latestVersion')]);
        }
        $data = $query->with($hasSourceLinks ? [
            'sourceLink' => fn ($links) => $verifiedLink($links),
            'sourceLink.project',
            'sourceLink.bidding',
        ] : [])
            ->paginate((int) ($filters['per_page'] ?? 10))->withQueryString();

        $projectStatuses = collect();
        $biddingRecords = collect();
        $years = Schema::hasColumn('ppl_forms', 'year_of_revenue_recognition')
            ? (clone $optionQuery)->reorder()->whereNotNull('year_of_revenue_recognition')->distinct()->orderBy('year_of_revenue_recognition')->pluck('year_of_revenue_recognition')
            : collect();
        $lots = (clone $optionQuery)->reorder()->whereNotNull('lot_number')->distinct()->orderBy('lot_number')->pluck('lot_number');
        if ($hasSourceLinks) {
            $links = MmcPplSourceLink::query();
            $verifiedLink($links);
            if (Schema::hasTable('projects') && Schema::hasColumn('projects', 'status')) {
                $projectStatuses = Project::query()->whereIn('project_id', (clone $links)->whereNotNull('project_id')->select('project_id'))
                    ->whereNotNull('status')->distinct()->orderBy('status')->pluck('status');
            }
            if (Schema::hasTable('project_information')) {
                $biddingQuery = ProjectInformation::query()->whereIn('id', (clone $links)->whereNotNull('project_information_id')->select('project_information_id'));
                if (Schema::hasColumn('project_information', 'company_id')) {
                    $biddingQuery->where(fn ($ownerQuery) => $ownerQuery->whereNull('company_id')->orWhere('company_id', $company->company_id));
                }
                $biddingRecords = $biddingQuery->orderBy('project_name')->get(['id', 'project_name', 'project_code']);
            }
        }

        return view('finance.ppl_forms.index', [
            'data' => $data,
            'filters' => $filters,
            'projectStatuses' => $projectStatuses,
            'years' => $years,
            'lots' => $lots,
            'biddingRecords' => $biddingRecords,
            'hasSourceLinks' => $hasSourceLinks,
            'hasFinancialPlans' => $hasFinancialPlans,
            'financialSchemaIncomplete' => Schema::hasTable('project_financial_plans') && ! $hasFinancialPlans,
            'financialSummary' => $summary,
            'activeFilterCount' => count(array_filter($filters, fn ($value, $key): bool => ! in_array($key, ['sort_by', 'sort_order', 'per_page'], true) && $value !== null && $value !== '', ARRAY_FILTER_USE_BOTH)),
        ]);
    }

    public function create(Request $request, DashboardService $dashboards)
    {
        Gate::authorize('viewAny', ProjectInformation::class);
        $company = $dashboards->currentCompany($request->user(), $request->session());
        abort_unless($company?->code === 'MMC', 403);

        $biddingQuery = ProjectInformation::query()
            ->with(['lots:id,project_id,lot_no,region'])
            ->orderBy('project_name');
        if (Schema::hasColumn('project_information', 'company_id')) {
            $biddingQuery->where(fn ($ownerQuery) => $ownerQuery->whereNull('company_id')->orWhere('company_id', $company->company_id));
        }
        $biddingProjects = $biddingQuery->get(['id', 'project_id', 'project_code', 'project_name', 'region', 'date_of_bid_opening', 'approved_budget_contract_abc']);

        $legacyProjectLots = collect();
        if (Schema::hasTable('projects') && Schema::hasTable('lot')) {
            $legacyProjectLots = DB::table('project_information as bidding')
                ->join('projects as project', 'project.project_id', '=', 'bidding.project_id')
                ->join('lot as legacy_lot', 'legacy_lot.project_id', '=', 'project.project_id')
                ->whereNotNull('bidding.project_id')
                ->whereNotExists(fn ($query) => $query->selectRaw('1')->from('lots as bidding_lot')->whereColumn('bidding_lot.project_id', 'bidding.id'))
                ->orderBy('bidding.project_name')
                ->orderBy('legacy_lot.lot_name')
                ->when(Schema::hasColumn('project_information', 'company_id'), fn ($query) => $query->where(fn ($ownerQuery) => $ownerQuery->whereNull('bidding.company_id')->orWhere('bidding.company_id', $company->company_id)))
                ->when(Schema::hasColumn('projects', 'company_id'), fn ($query) => $query->where(fn ($ownerQuery) => $ownerQuery->whereNull('project.company_id')->orWhere('project.company_id', $company->company_id)))
                ->get([
                    'bidding.id as bidding_id', 'bidding.project_id as bidding_identifier', 'bidding.project_name as bidding_name',
                    'bidding.region as bidding_region', 'bidding.date_of_bid_opening', 'project.project_id as project_id',
                    'project.project_name as project_name', 'legacy_lot.lot_id as lot_id', 'legacy_lot.lot_name as lot_name',
                ]);
        }

        return view('finance.ppl_forms.create', compact('biddingProjects', 'legacyProjectLots'));
    }

    public function sourceMapping(ProjectInformation $bidding, ProjectLot $lot, Request $request, DashboardService $dashboards): JsonResponse
    {
        Gate::authorize('view', $bidding);
        $company = $dashboards->currentCompany($request->user(), $request->session());
        abort_unless($company?->code === 'MMC', 403);
        abort_if(Schema::hasColumn('project_information', 'company_id') && $bidding->company_id !== null && (int) $bidding->company_id !== (int) $company->company_id, 404);

        abort_unless((int) $lot->project_id === (int) $bidding->getKey(), 404);

        return response()->json([
            'project_title' => $bidding->project_name,
            'lot_number' => $lot->lot_no,
            'region' => $lot->region,
            'bid_opening' => $bidding->date_of_bid_opening,
        ]);
    }

    public function legacyLotSourceMapping(ProjectInformation $bidding, Project $project, LegacyLot $lot, Request $request, DashboardService $dashboards): JsonResponse
    {
        Gate::authorize('view', $bidding);
        $company = $dashboards->currentCompany($request->user(), $request->session());
        abort_unless($company?->code === 'MMC', 403);
        abort_if((Schema::hasColumn('project_information', 'company_id') && $bidding->company_id !== null && (int) $bidding->company_id !== (int) $company->company_id)
            || (Schema::hasColumn('projects', 'company_id') && $project->company_id !== null && (int) $project->company_id !== (int) $company->company_id), 404);

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

    public function store(StorePplFormRequest $request, DashboardService $dashboards, MmcPplSourceLinkService $sourceLinks)
    {
        $company = $dashboards->currentCompany($request->user(), $request->session());
        abort_unless($company?->code === 'MMC', 403);
        $validated = $request->validated();
        $sourceIds = [
            'project_id' => $validated['source_project_id'] ?? null,
            'project_information_id' => $validated['source_project_information_id'] ?? null,
            'bidding_lot_id' => $validated['source_bidding_lot_id'] ?? null,
            'legacy_lot_id' => $validated['source_legacy_lot_id'] ?? null,
        ];
        unset($validated['source_project_id'], $validated['source_project_information_id'], $validated['source_bidding_lot_id'], $validated['source_legacy_lot_id']);
        if (Schema::hasColumn('ppl_forms', 'company_id')) {
            $validated['company_id'] = $company->company_id;
        }

        DB::transaction(function () use ($validated, $sourceIds, $request, $company, $sourceLinks): void {
            $pplForm = PplForm::query()->create($validated);
            $sourceLinks->recordManualPplLink($pplForm, $sourceIds, $request->user(), (int) $company->company_id);
        });

        return redirect()->route('ppl_forms.index')->with('success', 'Saved successfully');
    }

    public function import(ImportPplFormRequest $request, DashboardService $dashboards)
    {
        $request->validated();
        $company = $dashboards->currentCompany($request->user(), $request->session());
        abort_unless($company?->code === 'MMC', 403);

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
                $attributes = [
                    'project_code' => $projectCode,
                    'lot_number' => $lotNumber,
                    'project_title' => $projectTitle,
                    'project_id_no' => $this->clean($row[$columns['project_id_no']] ?? null),
                    'region' => $this->clean($row[$columns['region']] ?? null),
                    'bid_opening' => $this->cleanDate($row[$columns['bid_opening']] ?? null),
                    'abc' => $this->cleanNumber($row[$columns['abc']] ?? null),
                    'bidder' => $this->clean($row[$columns['bidder']] ?? null),
                    'authorized_signatory' => $this->clean($row[$columns['authorized_signatory']] ?? null),
                ];
                if (Schema::hasColumn('ppl_forms', 'company_id')) {
                    $attributes['company_id'] = $company->company_id;
                }
                PplForm::create($attributes);
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

    public function show(Request $request, DashboardService $dashboards, int $id): View
    {
        $company = $dashboards->currentCompany($request->user(), $request->session());
        abort_unless($company?->code === 'MMC', 403);
        $query = PplForm::query()->whereKey($id);
        if (Schema::hasColumn('ppl_forms', 'company_id')) {
            $query->where(fn ($ownerQuery) => $ownerQuery->whereNull('company_id')->orWhere('company_id', $company->company_id));
        }
        $data = $query->firstOrFail();

        $source = null;
        if (Schema::hasTable('mmc_ppl_source_links')
            && Schema::hasColumn('mmc_ppl_source_links', 'company_id')
            && Schema::hasColumn('mmc_ppl_source_links', 'ppl_form_id')
            && Schema::hasColumn('mmc_ppl_source_links', 'match_status')) {
            $sourceQuery = MmcPplSourceLink::query()
                ->where('ppl_form_id', $data->getKey())
                ->where('company_id', $company->company_id)
                ->where('match_status', 'verified');
            if (Schema::hasColumn('projects', 'company_id')) {
                $sourceQuery->where(fn ($links) => $links->whereNull('project_id')
                    ->orWhereHas('project', fn ($projects) => $projects->whereNull('company_id')->orWhere('company_id', $company->company_id)));
            }
            if (Schema::hasColumn('project_information', 'company_id')) {
                $sourceQuery->where(fn ($links) => $links->whereNull('project_information_id')
                    ->orWhereHas('bidding', fn ($biddings) => $biddings->whereNull('company_id')->orWhere('company_id', $company->company_id)));
            }
            $source = $sourceQuery->with(['project', 'bidding', 'biddingLot', 'legacyLot'])->first();
            if ($source !== null && (
                ($source->biddingLot !== null && (int) $source->biddingLot->project_id !== (int) $source->project_information_id)
                || ($source->legacyLot !== null && (int) $source->legacyLot->project_id !== (int) $source->project_id)
            )) {
                $source = null;
            }
        }

        $plans = collect();
        $financialSchemaIncomplete = Schema::hasTable('project_financial_plans')
            && ! Schema::hasColumn('project_financial_plans', 'deleted_at');
        if (Schema::hasTable('project_financial_plans')
            && Schema::hasTable('project_financial_plan_versions')
            && Schema::hasColumn('project_financial_plans', 'company_id')
            && Schema::hasColumn('project_financial_plans', 'ppl_form_id')
            && ! $financialSchemaIncomplete) {
            $plans = ProjectFinancialPlan::query()->forCompany((int) $company->company_id)
                ->where('ppl_form_id', $data->getKey())->with('latestVersion')->orderBy('id')->get();
        }

        return view('finance.ppl_forms.show', compact('data', 'source', 'plans', 'financialSchemaIncomplete'));
    }

    public function edit(Request $request, DashboardService $dashboards, int $id): View
    {
        $company = $dashboards->currentCompany($request->user(), $request->session());
        abort_unless($company?->code === 'MMC', 403);
        Gate::authorize('viewAny', ProjectInformation::class);

        $query = PplForm::query()->whereKey($id);
        if (Schema::hasColumn('ppl_forms', 'company_id')) {
            $query->where(fn ($ownerQuery) => $ownerQuery->whereNull('company_id')->orWhere('company_id', $company->company_id));
        }

        return view('finance.ppl_forms.edit', ['data' => $query->firstOrFail()]);
    }

    public function update(UpdatePplFormRequest $request, DashboardService $dashboards, int $id)
    {
        $company = $dashboards->currentCompany($request->user(), $request->session());
        abort_unless($company?->code === 'MMC', 403);
        Gate::authorize('viewAny', ProjectInformation::class);

        DB::transaction(function () use ($request, $company, $id): void {
            $query = PplForm::query()->whereKey($id);
            if (Schema::hasColumn('ppl_forms', 'company_id')) {
                $query->where(fn ($ownerQuery) => $ownerQuery->whereNull('company_id')->orWhere('company_id', $company->company_id));
            }

            $query->lockForUpdate()->firstOrFail()->update($request->validated());
        });

        return redirect()->route('ppl_forms.show', ['id' => $id] + $request->query())
            ->with('success', 'PPL Form updated. Saved financial-plan versions were not recalculated.');
    }
}
