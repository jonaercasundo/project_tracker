<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProjectFinancialPlanRequest;
use App\Http\Requests\UpdateProjectFinancialPlanRequest;
use App\Models\MI_Product_Factory_Cost;
use App\Models\PplForm;
use App\Models\Project;
use App\Models\ProjectFinancialPlan;
use App\Models\ProjectInformation;
use App\Models\ProjectLot;
use App\Services\ProjectFinancialPlanCalculator;
use App\Services\ProjectFinancialPlanService;
use Brick\Math\BigDecimal;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProjectFinancialPlanController extends Controller
{
    public function dashboard(Request $request): View
    {
        $companyId = $this->currentCompanyId();
        $isMmcPplWorkspace = request()->user()?->currentCompany()?->code === 'MMC';
        $plans = ProjectFinancialPlan::query()
            ->forCompany($companyId)
            ->with('latestVersion')
            ->latest('updated_at')
            ->get();
        $versions = $plans->map(fn (ProjectFinancialPlan $plan) => $plan->latestVersion)->filter();

        $monitoringPlans = null;
        $monitoringYears = collect();
        $monitoringStatuses = collect();
        $filters = ['year' => null, 'status' => null, 'search' => null];

        if ($isMmcPplWorkspace) {
            $filters = $this->monitoringFilters($request);
            $monitoringPlans = $this->monitoringQuery($companyId, $filters)->paginate(25)->withQueryString();
            $linkedFormYears = PplForm::query()
                ->whereIn('id', ProjectFinancialPlan::query()
                    ->forCompany($companyId)
                    ->whereNotNull('ppl_form_id')
                    ->select('ppl_form_id'))
                ->whereNotNull('year_of_revenue_recognition')
                ->distinct()
                ->orderBy('year_of_revenue_recognition')
                ->pluck('year_of_revenue_recognition');
            $importedPlanYears = Schema::hasColumn('project_financial_plans', 'revenue_year')
                ? ProjectFinancialPlan::query()->forCompany($companyId)->whereNotNull('revenue_year')->distinct()->orderBy('revenue_year')->pluck('revenue_year')
                : collect();
            $monitoringYears = $linkedFormYears->merge($importedPlanYears)->unique()->sort()->values();
            $monitoringStatuses = collect(['draft'])
                ->merge(Project::query()->whereNotNull('status')->distinct()->orderBy('status')->pluck('status'))
                ->merge(ProjectInformation::query()->whereNotNull('status')->distinct()->orderBy('status')->pluck('status'))
                ->unique()
                ->sort()
                ->values();
        }

        return view('finance.dashboard', [
            'planCount' => $plans->count(),
            'draftCount' => $plans->where('status', 'draft')->count(),
            'projectCount' => $plans->whereNotNull('project_id')->pluck('project_id')->unique()->count(),
            'projectedPplTotal' => $this->sumCalculation($versions, 'ppl'),
            'recentPlans' => $plans->take(8),
            'isMmcPplWorkspace' => $isMmcPplWorkspace,
            'monitoringPlans' => $monitoringPlans,
            'monitoringYears' => $monitoringYears,
            'monitoringStatuses' => $monitoringStatuses,
            'filters' => $filters,
        ]);
    }

    public function exportMonitoring(Request $request): StreamedResponse
    {
        abort_unless(request()->user()?->currentCompany()?->code === 'MMC', 403, 'MMC company context is required for PPL monitoring reports.');

        $filters = $this->monitoringFilters($request);
        $plans = $this->monitoringQuery($this->currentCompanyId(), $filters);

        return response()->streamDownload(function () use ($plans): void {
            $output = fopen('php://output', 'wb');
            if ($output === false) {
                throw new \RuntimeException('Unable to open the MMC PPL monitoring report stream.');
            }

            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, [
                'Project', 'Lot', 'Revenue year', 'Status', 'ABC', 'Contract amount', 'Net sales',
                'Factory cost', 'Total cost of sales', 'Operating expenses', 'Gross profit', 'Gross margin', 'Net profit after tax', 'Projected PPL',
                'Net margin', 'Version', 'Project ID', 'Bidding ID', 'PPL Form ID',
            ]);

            $plans->chunk(500, function ($rows) use ($output): void {
                foreach ($rows as $plan) {
                    $version = $plan->latestVersion;
                    $results = $version?->calculation_results ?? [];
                    $fputCsv = fn (mixed $value): mixed => is_string($value) ? $this->safeCsvValue($value) : $value;

                    fputcsv($output, [
                        $fputCsv($plan->project?->project_name ?? $plan->bidding?->project_name ?? $plan->pplForm?->project_title ?? $plan->name),
                        $plan->lot?->lot_no ?? $plan->pplForm?->lot_number,
                        $plan->revenue_year ?? $plan->pplForm?->year_of_revenue_recognition ?? data_get($version?->input_data, 'workbook_import.revenue_year'),
                        $fputCsv($plan->project?->status ?? $plan->bidding?->status ?? $plan->status),
                        $results['abc'] ?? '', $results['contract_amount'] ?? '', $results['net_sales'] ?? '',
                        $results['factory_cost'] ?? '', $results['total_cost_of_sales'] ?? '', $results['opex'] ?? '',
                        $results['gross_profit'] ?? '', $results['gpm'] ?? '',
                        $results['net_profit_after_tax'] ?? '', $results['ppl'] ?? '', $results['npm'] ?? '',
                        $version?->version_number, $plan->project_id, $plan->project_information_id, $plan->ppl_form_id,
                    ]);
                }
            });

            fclose($output);
        }, 'mmc-ppl-monitoring.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function index(): View
    {
        $plans = ProjectFinancialPlan::query()
            ->forCompany($this->currentCompanyId())
            ->with(['latestVersion', 'project'])
            ->withCount('versions')
            ->latest('updated_at')
            ->paginate(20);

        return view('finance.project-financial-plans.index', compact('plans'));
    }

    public function create(): View
    {
        return view('finance.project-financial-plans.form', $this->formData());
    }

    public function store(StoreProjectFinancialPlanRequest $request, ProjectFinancialPlanService $service): RedirectResponse
    {
        $plan = $service->create($request->validated(), $request->user(), $this->currentCompanyId());

        return redirect()->route('finance.plans.show', $plan)->with('success', 'Financial draft created.');
    }

    public function show(ProjectFinancialPlan $financialPlan): View
    {
        $plan = $this->scopedPlan($financialPlan)->load(['project', 'bidding', 'lot', 'pplForm', 'versions.costItems']);

        return view('finance.project-financial-plans.show', compact('plan'));
    }

    public function edit(ProjectFinancialPlan $financialPlan): View
    {
        $plan = $this->scopedPlan($financialPlan)->load(['latestVersion', 'project', 'bidding', 'lot']);
        $data = $this->formData();
        $data['plan'] = $plan;
        $data['version'] = $plan->latestVersion;
        $data['inputValues'] = array_replace(
            $data['defaultInputs'],
            $plan->latestVersion?->input_data['inputs'] ?? [],
            ['factory_cost_id' => $plan->latestVersion?->factory_cost_snapshot['id'] ?? ''],
        );
        $data['assumptionValues'] = $plan->latestVersion?->assumptions_snapshot['rates'] ?? $data['defaultAssumptions'];
        $data['additionalCostItems'] = $plan->latestVersion?->input_data['additional_cost_items'] ?? [];

        return view('finance.project-financial-plans.form', $data);
    }

    public function update(UpdateProjectFinancialPlanRequest $request, ProjectFinancialPlan $financialPlan, ProjectFinancialPlanService $service): RedirectResponse
    {
        $plan = $this->scopedPlan($financialPlan);
        $updated = $service->update($plan, $request->validated(), $request->user(), $this->currentCompanyId());

        return redirect()->route('finance.plans.show', $updated)->with('success', 'A new financial draft version was saved.');
    }

    public function destroy(Request $request, ProjectFinancialPlan $financialPlan): RedirectResponse
    {
        abort_unless($request->user()?->currentCompany()?->code === 'MMC', 403, 'MMC company context is required.');

        DB::transaction(function () use ($request, $financialPlan): void {
            $plan = ProjectFinancialPlan::query()
                ->forCompany($this->currentCompanyId())
                ->whereKey($financialPlan->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            abort_unless($plan->status === 'draft', 409, 'Only draft financial plans can be deleted.');
            $plan->deleted_by = $request->user()->getAuthIdentifier();
            $plan->save();
            $plan->delete();
        });

        return redirect()->route('finance.dashboard')->with('success', 'Financial plan removed from active monitoring.');
    }

    /** @return array<string, mixed> */
    private function formData(): array
    {
        $companyCode = request()->user()?->currentCompany()?->code;
        $canLinkMmcRecords = $companyCode === 'MMC';
        $projectColumns = ['project_id', 'project_name', 'project_code'];
        foreach (['ABC', 'contract_amount'] as $column) {
            if (Schema::hasColumn('projects', $column)) {
                $projectColumns[] = $column;
            }
        }
        $biddingColumns = ['id', 'project_id', 'project_name', 'status', 'approved_budget_contract_abc'];
        if (Schema::hasColumn('project_information', 'contract_amount')) {
            $biddingColumns[] = 'contract_amount';
        }

        return [
            'projects' => $canLinkMmcRecords ? Project::query()->orderBy('project_name')->get($projectColumns) : collect(),
            'biddingDocuments' => $canLinkMmcRecords ? ProjectInformation::query()->orderBy('project_name')->get($biddingColumns) : collect(),
            'lots' => $canLinkMmcRecords ? ProjectLot::query()->with('project')->orderBy('lot_no')->get(['id', 'project_id', 'lot_no']) : collect(),
            'pplForms' => $canLinkMmcRecords ? PplForm::query()->latest('id')->get(['id', 'project_code', 'lot_number', 'project_title', 'abc']) : collect(),
            'factoryCosts' => $this->factoryCosts(),
            'defaultInputs' => [
                'contract_amount' => '', 'abc' => '', 'factory_cost' => '',
                'pf1_rate' => '', 'pf2_rate' => '', 'pf3_rate' => '',
                'll_commission_rate' => '', 'interest_dst_rate' => '',
                'shipping_containers' => '', 'rate_per_container' => '', 'logistics_rate' => '',
                'warehouse_area' => '', 'warehouse_rent_per_sqm' => '', 'warehouse_months' => '',
                'other_expenses_rate' => '', 'manpower_rate' => '', 'assembly_rate' => '',
                'rd_rate' => '', 'facilitation_rate' => '', 'tax_lawyer_rate' => '',
                'opex_rate' => '', 'income_tax_rate' => '', 'incentive_rate' => '',
                'importation_vat_rate' => '0.13', 'sales_vat_rate' => '0.12',
            ],
            'defaultAssumptions' => app(ProjectFinancialPlanCalculator::class)->defaultAssumptions(),
        ];
    }

    private function currentCompanyId(): int
    {
        $company = request()->user()?->currentCompany();
        abort_unless($company !== null && $company->is_active, 403, 'Select an active company to access financial plans.');

        return (int) $company->company_id;
    }

    /** @return array{year?: string, status?: string, search?: string} */
    private function monitoringFilters(Request $request): array
    {
        return $request->validate([
            'year' => ['nullable', 'digits:4'],
            'status' => ['nullable', 'string', 'max:50'],
            'search' => ['nullable', 'string', 'max:120'],
        ]);
    }

    /** @param array{year?: string, status?: string} $filters */
    private function monitoringQuery(int $companyId, array $filters): Builder
    {
        $query = ProjectFinancialPlan::query()
            ->forCompany($companyId)
            ->with(['latestVersion', 'project', 'bidding', 'lot', 'pplForm'])
            ->latest('updated_at');

        if (! empty($filters['year'])) {
            $query->where(function (Builder $planQuery) use ($filters): void {
                if (Schema::hasColumn('project_financial_plans', 'revenue_year')) {
                    $planQuery->where('revenue_year', $filters['year'])
                        ->orWhereHas('pplForm', fn (Builder $pplQuery) => $pplQuery->where('year_of_revenue_recognition', $filters['year']));

                    return;
                }

                $planQuery->whereHas('pplForm', fn (Builder $pplQuery) => $pplQuery->where('year_of_revenue_recognition', $filters['year']));
            });
        }

        if (! empty($filters['status'])) {
            $status = $filters['status'];
            $query->where(function (Builder $planQuery) use ($status): void {
                $planQuery->where('status', $status)
                    ->orWhereHas('project', fn (Builder $projectQuery) => $projectQuery->where('status', $status))
                    ->orWhereHas('bidding', fn (Builder $biddingQuery) => $biddingQuery->where('status', $status));
            });
        }

        if (! empty($filters['search'])) {
            $search = trim($filters['search']);
            $query->where(function (Builder $planQuery) use ($search): void {
                $like = '%'.str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $search).'%';
                $planQuery->where('name', 'like', $like)
                    ->orWhereHas('project', fn (Builder $sourceQuery) => $sourceQuery->where('project_name', 'like', $like)->orWhere('project_code', 'like', $like))
                    ->orWhereHas('bidding', fn (Builder $sourceQuery) => $sourceQuery->where('project_name', 'like', $like)->orWhere('project_id', 'like', $like))
                    ->orWhereHas('pplForm', fn (Builder $sourceQuery) => $sourceQuery->where('project_title', 'like', $like)->orWhere('project_code', 'like', $like)->orWhere('lot_number', 'like', $like))
                    ->orWhereHas('lot', fn (Builder $sourceQuery) => $sourceQuery->where('lot_no', 'like', $like));
            });
        }

        return $query;
    }

    private function safeCsvValue(string $value): string
    {
        return preg_match('/^[\s]*[=+\-@]/', $value) === 1 ? "'".$value : $value;
    }

    private function scopedPlan(ProjectFinancialPlan $plan): ProjectFinancialPlan
    {
        return ProjectFinancialPlan::query()
            ->forCompany($this->currentCompanyId())
            ->whereKey($plan->getKey())
            ->firstOrFail();
    }

    /** @return Collection<int, MI_Product_Factory_Cost> */
    private function factoryCosts(): Collection
    {
        if (request()->user()?->currentCompany()?->code !== 'MI' || ! DB::getSchemaBuilder()->hasTable('mi_product_factory_costs')) {
            return collect();
        }

        return MI_Product_Factory_Cost::query()->with('product')->latest('effective_date')->limit(100)->get();
    }

    /** @param Collection<int, mixed> $versions */
    private function sumCalculation(Collection $versions, string $key): string
    {
        return $versions->reduce(function (string $sum, $version) use ($key): string {
            return (string) BigDecimal::of($sum)->plus((string) ($version->calculation_results[$key] ?? '0'))->toScale(2);
        }, '0.00');
    }
}
