<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\PplForm;
use App\Models\ProjectFinancialPlan;
use App\Models\ProjectFinancialPlanVersion;
use App\Models\ProjectInformation;
use App\Services\MmcPplSourceLinkService;
use App\Services\PplFormFinancialSummary;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class MmcPplManagementController extends Controller
{
    public function analytics(Request $request, PplFormFinancialSummary $financialSummary): View
    {
        Gate::authorize('viewAny', ProjectInformation::class);
        $company = Company::query()->where('code', 'MMC')->firstOrFail();
        abort_unless($request->user()?->belongsToCompany((int) $company->company_id), 403);
        $companyId = (int) $company->company_id;
        $forms = PplForm::query();
        if (Schema::hasColumn('ppl_forms', 'company_id')) {
            $forms->where(fn ($owners) => $owners->whereNull('company_id')->orWhere('company_id', $companyId));
        }

        $hasPlans = Schema::hasTable('project_financial_plans') && Schema::hasTable('project_financial_plan_versions')
            && Schema::hasColumn('project_financial_plans', 'company_id') && Schema::hasColumn('project_financial_plans', 'ppl_form_id')
            && Schema::hasColumn('project_financial_plans', 'deleted_at');
        $summary = $hasPlans ? $financialSummary->forFilteredForms(clone $forms, $companyId) : null;
        $years = Schema::hasColumn('ppl_forms', 'year_of_revenue_recognition')
            ? (clone $forms)->whereNotNull('year_of_revenue_recognition')->distinct()->orderBy('year_of_revenue_recognition')->pluck('year_of_revenue_recognition')
            : collect();
        $annual = $hasPlans ? $years->map(function ($year) use ($forms, $financialSummary, $companyId): array {
            $yearSummary = $financialSummary->forFilteredForms((clone $forms)->where('year_of_revenue_recognition', $year), $companyId);

            return ['year' => $year, 'summary' => $yearSummary];
        })->filter(fn (array $row): bool => $row['summary']['plan_count'] > 0)->values() : collect();
        $maxRevenue = $annual->max(fn (array $row): float => abs((float) $row['summary']['amounts']['net_sales'])) ?: 0;

        $history = $hasPlans ? ProjectFinancialPlanVersion::query()
            ->whereHas('plan', fn ($plans) => $plans->forCompany($companyId)->whereNotNull('ppl_form_id'))
            ->with('plan:id,company_id,ppl_form_id,name')
            ->latest('id')->limit(20)->get() : collect();

        $variance = $hasPlans ? ProjectFinancialPlan::query()->forCompany($companyId)->whereNotNull('ppl_form_id')
            ->with(['versions' => fn ($versions) => $versions->limit(2)])
            ->latest('updated_at')->limit(25)->get()
            ->filter(fn (ProjectFinancialPlan $plan): bool => $plan->versions->count() >= 2)
            ->map(function (ProjectFinancialPlan $plan): array {
                $latest = $plan->versions[0];
                $previous = $plan->versions[1];
                $currentPpl = data_get($latest->calculation_results, 'ppl');
                $previousPpl = data_get($previous->calculation_results, 'ppl');

                return [
                    'plan' => $plan,
                    'latest' => $latest,
                    'previous' => $previous,
                    'delta' => is_numeric($currentPpl) && is_numeric($previousPpl)
                        ? (string) BigDecimal::of((string) $currentPpl)->minus((string) $previousPpl)->toScale(2, RoundingMode::HALF_UP)
                        : null,
                ];
            })->values() : collect();

        return view('finance.ppl_management.analytics', [
            'formCount' => (clone $forms)->count(),
            'summary' => $summary,
            'annual' => $annual,
            'maxRevenue' => $maxRevenue,
            'history' => $history,
            'variance' => $variance,
            'hasPlans' => $hasPlans,
        ]);
    }

    public function sources(Request $request, MmcPplSourceLinkService $sourceLinks): View
    {
        Gate::authorize('viewAny', ProjectInformation::class);
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:120'],
            'status' => ['nullable', Rule::in(['auto_matched', 'ambiguous', 'needs_review', 'unmatched', 'already_linked', 'already_manually_verified', 'ownership_conflict'])],
            'per_page' => ['nullable', Rule::in(['10', '25', '50'])],
        ]);
        $report = $sourceLinks->dryRunHistoricalLinks();
        $rows = collect($report['rows']);
        if (($search = trim((string) ($filters['search'] ?? ''))) !== '') {
            $rows = $rows->filter(fn (array $row): bool => str_contains(mb_strtolower(implode(' ', [
                $row['ppl_form_id'], $row['project_code'], $row['project_title'], $row['project_identifier'], $row['lot_number'],
            ])), mb_strtolower($search)));
        }
        if (isset($filters['status'])) {
            $rows = $rows->where('status', $filters['status']);
        }

        $perPage = (int) ($filters['per_page'] ?? 25);
        $page = max(1, (int) $request->integer('page', 1));
        $paginated = new LengthAwarePaginator(
            $rows->forPage($page, $perPage)->values(), $rows->count(), $perPage, $page,
            ['path' => $request->url(), 'query' => $request->query()],
        );

        return view('finance.ppl_management.sources', [
            'rows' => $paginated,
            'summary' => $report['summary'],
            'filters' => $filters,
            'canConfirm' => Schema::hasTable('mmc_ppl_source_links')
                && Schema::hasColumn('ppl_forms', 'company_id')
                && Schema::hasColumn('projects', 'company_id')
                && Schema::hasColumn('project_information', 'company_id'),
        ]);
    }

    public function confirm(Request $request, int $pplForm, MmcPplSourceLinkService $sourceLinks): RedirectResponse
    {
        Gate::authorize('viewAny', ProjectInformation::class);
        $company = Company::query()->where('code', 'MMC')->firstOrFail();
        abort_unless($request->user()?->belongsToCompany((int) $company->company_id), 403);
        $sourceIds = $request->validate([
            'project_id' => ['nullable', 'integer', 'min:1'],
            'project_information_id' => ['nullable', 'integer', 'min:1'],
            'bidding_lot_id' => ['nullable', 'integer', 'min:1'],
            'legacy_lot_id' => ['nullable', 'integer', 'min:1'],
        ]);

        $sourceLinks->confirmHistoricalLink($pplForm, $sourceIds, $request->user());

        return redirect()->route('finance.ppl.sources.index', $request->only(['search', 'status', 'per_page', 'page']))
            ->with('success', "PPL Form #{$pplForm} is linked to the verified project and lot.");
    }
}
