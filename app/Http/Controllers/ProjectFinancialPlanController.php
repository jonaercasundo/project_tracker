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
        $filters = ['year' => null, 'status' => null];

        if ($isMmcPplWorkspace) {
            $filters = $request->validate([
                'year' => ['nullable', 'digits:4'],
                'status' => ['nullable', 'string', 'max:50'],
            ]);

            $monitoringQuery = ProjectFinancialPlan::query()
                ->forCompany($companyId)
                ->with(['latestVersion', 'project', 'bidding', 'lot', 'pplForm'])
                ->latest('updated_at');

            if (! empty($filters['year'])) {
                $monitoringQuery->whereHas('pplForm', fn (Builder $query) => $query->where('year_of_revenue_recognition', $filters['year']));
            }

            if (! empty($filters['status'])) {
                $status = $filters['status'];
                $monitoringQuery->where(function (Builder $query) use ($status): void {
                    $query->where('status', $status)
                        ->orWhereHas('project', fn (Builder $projectQuery) => $projectQuery->where('status', $status))
                        ->orWhereHas('bidding', fn (Builder $biddingQuery) => $biddingQuery->where('status', $status));
                });
            }

            $monitoringPlans = $monitoringQuery->paginate(25)->withQueryString();
            $monitoringYears = PplForm::query()
                ->whereNotNull('year_of_revenue_recognition')
                ->distinct()
                ->orderBy('year_of_revenue_recognition')
                ->pluck('year_of_revenue_recognition');
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
