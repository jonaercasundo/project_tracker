<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ProjectLifecycleTask;
use App\Models\ProjectLifecycleTrack;
use App\Models\UserDashboard;
use App\Services\BillingDocumentStatusService;
use App\Services\ProjectDeliveryProgressService;
use App\Services\ProjectDrProgressReportService;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class ProjectDashboardController extends Controller
{
    private const WIDGETS = [
        'project_status' => ['label' => 'Project status', 'kind' => 'chart', 'route' => 'projects.index'],
        'project_monitoring' => ['label' => 'Project monitoring', 'kind' => 'summary', 'route' => 'projects.index'],
        'lifecycle_monitoring' => ['label' => 'Lifecycle monitoring', 'kind' => 'summary', 'route' => 'operations.lifecycle.index'],
        'delivery_status' => ['label' => 'Delivery status', 'kind' => 'chart', 'route' => 'deliveries.tracking'],
        'delivered_assets' => ['label' => 'Delivered assets', 'kind' => 'metric', 'route' => 'deliveries.tracking'],
        'tracking_deliveries' => ['label' => 'Tracking deliveries', 'kind' => 'summary', 'route' => 'deliveries.tracking'],
        'delivery_monitoring' => ['label' => 'Delivery monitoring', 'kind' => 'summary', 'route' => 'deliveries.monitoring'],
        'billing_document_status' => ['label' => 'Billing document status', 'kind' => 'summary', 'route' => 'projects.reports.analytics'],
        'inventory_monitoring' => ['label' => 'Inventory monitoring', 'kind' => 'summary', 'route' => 'inventory.balances'],
    ];

    public function reportsAnalytics(Request $request, BillingDocumentStatusService $billingDocumentStatus, ProjectDrProgressReportService $drProgressReport): View
    {
        $projects = Project::query()->orderBy('project_name')->get([
            'project_id', 'project_name', 'report_delivery_start_date', 'report_cutoff_date',
        ]);
        $validated = $request->validate([
            'project_id' => ['nullable', 'integer', 'exists:projects,project_id'],
            'cutoff_date' => ['nullable', 'date_format:Y-m-d'],
            'delivery_start_date' => ['nullable', 'date_format:Y-m-d'],
        ]);
        $selectedProjectId = $validated['project_id'] ?? $projects->firstWhere('project_name', 'Billing Documentation')?->project_id ?? $projects->first()?->project_id;
        $selectedProject = $projects->firstWhere('project_id', $selectedProjectId);
        $cutoffDate = CarbonImmutable::parse(
            $validated['cutoff_date'] ?? $selectedProject?->report_cutoff_date ?? now()->toDateString(),
            config('app.timezone'),
        );
        $summary = $selectedProjectId !== null
            ? $billingDocumentStatus->summarize((int) $selectedProjectId)
            : ['total' => 0, 'processed' => 0, 'remaining' => 0, 'processed_today' => 0, 'source_error' => null];
        $deliveryStartDate = isset($validated['delivery_start_date'])
            ? CarbonImmutable::parse($validated['delivery_start_date'], config('app.timezone'))
            : ($selectedProject?->report_delivery_start_date
                ? CarbonImmutable::parse($selectedProject->report_delivery_start_date, config('app.timezone'))
                : null);

        return view('projects.reports-analytics', [
            'projects' => $projects,
            'selectedProject' => $selectedProject,
            'selectedProjectId' => $selectedProjectId,
            'summary' => $summary,
            'snapshotAt' => now(),
            'cutoffDate' => $cutoffDate,
            'drProgressReport' => $selectedProjectId !== null
                ? $drProgressReport->summarize(
                    (int) $selectedProjectId,
                    $cutoffDate,
                    $deliveryStartDate,
                )
                : null,
        ]);
    }

    public function updateReportDate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'project_id' => ['required', 'integer', 'exists:projects,project_id'],
            'date_field' => ['required', 'in:delivery_start_date,cutoff_date'],
            'date' => ['required', 'date_format:Y-m-d'],
        ]);

        $column = $validated['date_field'] === 'delivery_start_date'
            ? 'report_delivery_start_date'
            : 'report_cutoff_date';

        Project::query()
            ->where('project_id', $validated['project_id'])
            ->update([$column => $validated['date']]);

        return response()->json(['success' => true]);
    }

    public function reportsAnalyticsData(Request $request, BillingDocumentStatusService $billingDocumentStatus, ProjectDrProgressReportService $drProgressReport): JsonResponse
    {
        $validated = $request->validate([
            'project_id' => ['required', 'integer', 'exists:projects,project_id'],
            'cutoff_date' => ['nullable', 'date_format:Y-m-d'],
            'delivery_start_date' => ['nullable', 'date_format:Y-m-d'],
        ]);
        $project = Project::query()->findOrFail($validated['project_id']);
        $cutoffDate = CarbonImmutable::parse(
            $validated['cutoff_date'] ?? $project->report_cutoff_date ?? now()->toDateString(),
            config('app.timezone'),
        );
        $deliveryStartDate = isset($validated['delivery_start_date'])
            ? CarbonImmutable::parse($validated['delivery_start_date'], config('app.timezone'))
            : ($project->report_delivery_start_date
                ? CarbonImmutable::parse($project->report_delivery_start_date, config('app.timezone'))
                : null);

        return response()->json([
            'summary' => $billingDocumentStatus->summarize((int) $validated['project_id']),
            'dr_progress' => $drProgressReport->summarize(
                (int) $validated['project_id'],
                $cutoffDate,
                $deliveryStartDate,
            ),
            'updated_at' => now()->format('M d, Y · g:i A'),
        ])->header('Cache-Control', 'no-store, private');
    }

    public function index(
        Request $request,
        ProjectDeliveryProgressService $deliveryProgress,
        BillingDocumentStatusService $billingDocumentStatus,
    ): View {
        $filters = $request->validate([
            'dashboard_id' => ['nullable', 'integer'],
            'filters_applied' => ['nullable', 'in:1'],
            'search' => ['nullable', 'string', 'max:255'],
            'project' => ['nullable', 'integer', 'exists:projects,project_id'],
            'lot' => ['nullable', 'integer', 'exists:lot,lot_id'],
            'region' => ['nullable', 'string', 'max:120'],
            'division' => ['nullable', 'string', 'max:120'],
            'municipality' => ['nullable', 'string', 'max:120'],
            'assigned_logistics' => ['nullable', 'regex:/^(unassigned|[1-9][0-9]*)$/D'],
            'logistics_status' => ['nullable', Rule::in(['pending', 'logistics_received', 'delivered'])],
        ]);
        $userId = (int) $request->user()->getAuthIdentifier();
        $dashboards = UserDashboard::query()
            ->where('user_id', $userId)
            ->orderBy('sort_order')
            ->orderBy('dashboard_id')
            ->get();

        if ($dashboards->isEmpty()) {
            $dashboards = collect([UserDashboard::query()->create([
                'user_id' => $userId,
                'name' => 'Overview',
                'widgets' => [
                    'delivered_assets' => ['chart' => 'number'],
                    'project_status' => ['chart' => 'doughnut'],
                ],
                'is_default' => true,
            ])]);
        }

        $dashboard = $dashboards->firstWhere('dashboard_id', $request->integer('dashboard_id')) ?? $dashboards->first();
        $selectedWidgets = array_intersect_key($dashboard->widgets ?? [], self::WIDGETS);
        $widgetValues = [];
        $filterCriteria = array_filter(
            array_intersect_key($filters, array_flip(['search', 'project', 'lot', 'region', 'division', 'municipality', 'assigned_logistics', 'logistics_status'])),
            fn ($value): bool => $value !== null && $value !== '',
        );
        $filtersApplied = isset($filters['filters_applied']) && $filterCriteria !== [];
        $deliveryScopedFilters = array_filter(
            array_intersect_key($filters, array_flip(['search', 'lot', 'region', 'division', 'municipality', 'assigned_logistics', 'logistics_status'])),
            fn ($value): bool => $value !== null && $value !== '',
        );
        $deliveryQuery = $this->dashboardDeliveriesQuery($filters);
        $projectQuery = Project::query();

        if (! empty($filters['project'])) {
            $projectQuery->where('project_id', $filters['project']);
        }
        if ($deliveryScopedFilters !== []) {
            $projectQuery->whereIn('project_id', (clone $deliveryQuery)->select('d.project_id')->distinct());
        }

        $totalProjects = $filtersApplied ? (clone $projectQuery)->count() : 0;
        $pendingProjects = $filtersApplied ? (clone $projectQuery)->where('status', 'Pending')->count() : 0;
        $deliveredProjects = $filtersApplied ? (clone $deliveryQuery)->where('d.status', 'delivered')->count() : 0;

        if ($filtersApplied && isset($selectedWidgets['delivered_assets'])) {
            $widgetValues['delivered_assets'] = $deliveredProjects;
        }
        if ($filtersApplied && isset($selectedWidgets['project_status'])) {
            $widgetValues['project_status'] = (clone $projectQuery)
                ->select('status', DB::raw('COUNT(*) AS total'))
                ->groupBy('status')
                ->orderBy('status')
                ->get()
                ->map(fn ($row): array => ['label' => (string) ($row->status ?: 'Unassigned'), 'value' => (int) $row->total])
                ->all();
        }
        if ($filtersApplied && isset($selectedWidgets['project_monitoring'])) {
            $widgetValues['project_monitoring'] = [
                ['label' => 'Projects in scope', 'value' => $totalProjects],
                ['label' => 'Pending projects', 'value' => $pendingProjects],
                ['label' => 'Implementation / ongoing', 'value' => (clone $projectQuery)->whereIn('status', ['Implementation', 'Ongoing'])->count()],
                ['label' => 'Completed projects', 'value' => (clone $projectQuery)->where('status', 'Completed')->count()],
            ];
        }
        if ($filtersApplied && isset($selectedWidgets['delivery_status'])) {
            $widgetValues['delivery_status'] = (clone $deliveryQuery)
                ->select('d.status as status', DB::raw('COUNT(*) AS total'))
                ->groupBy('d.status')
                ->orderBy('d.status')
                ->get()
                ->map(fn ($row): array => ['label' => (string) ($row->status ?: 'Unassigned'), 'value' => (int) $row->total])
                ->all();
        }
        if ($filtersApplied && isset($selectedWidgets['tracking_deliveries'])) {
            $trackingSummary = (clone $deliveryQuery)
                ->selectRaw('COUNT(DISTINCT d.delivery_id) as total')
                ->selectRaw("COUNT(DISTINCT CASE WHEN d.status = 'delivered' THEN d.delivery_id END) as delivered")
                ->selectRaw("COUNT(DISTINCT CASE WHEN d.status = 'pending' THEN d.delivery_id END) as pending")
                ->first();
            $widgetValues['tracking_deliveries'] = [
                ['label' => 'Delivery records', 'value' => (int) ($trackingSummary->total ?? 0)],
                ['label' => 'Delivered', 'value' => (int) ($trackingSummary->delivered ?? 0)],
                ['label' => 'Pending', 'value' => (int) ($trackingSummary->pending ?? 0)],
            ];
        }
        if ($filtersApplied && isset($selectedWidgets['delivery_monitoring'])) {
            $progressFilters = ['active_only' => false, 'compact' => true, 'defer_lot_billing' => true];
            foreach (['lot' => 'lot_id', 'region' => 'region', 'division' => 'division', 'municipality' => 'municipality'] as $filter => $progressFilter) {
                if (! empty($filters[$filter])) {
                    $progressFilters[$progressFilter] = $filters[$filter];
                }
            }
            if (! empty($filters['project']) || $deliveryScopedFilters !== []) {
                $progressFilters['project_ids'] = (clone $projectQuery)->select('project_id')->pluck('project_id')->map(fn ($projectId): int => (int) $projectId)->all();
            }
            $progressSummary = $deliveryProgress->report($progressFilters)['summary'];
            $widgetValues['delivery_monitoring'] = [
                ['label' => 'Projects', 'value' => (int) ($progressSummary['projects_count'] ?? 0)],
                ['label' => 'Pending deliveries', 'value' => (int) ($progressSummary['pending_deliveries_count'] ?? 0)],
                ['label' => 'Completed deliveries', 'value' => (int) ($progressSummary['completed_deliveries_count'] ?? 0)],
            ];
        }
        if ($filtersApplied && isset($selectedWidgets['lifecycle_monitoring'])) {
            $company = $request->user()->currentCompany();
            $tracks = ProjectLifecycleTrack::query();
            if ($company === null) {
                $tracks->whereRaw('1 = 0');
            } else {
                $tracks->where('company_id', $company->company_id);
            }
            if (! empty($filters['project'])) {
                $tracks->where('project_id', $filters['project']);
            }
            $openTasks = ProjectLifecycleTask::query()
                ->where('status', 'open')
                ->whereHas('track', function ($query) use ($company, $filters): void {
                    if ($company === null) {
                        $query->whereRaw('1 = 0');
                    } else {
                        $query->where('company_id', $company->company_id);
                    }

                    if (! empty($filters['project'])) {
                        $query->where('project_id', $filters['project']);
                    }
                });
            $widgetValues['lifecycle_monitoring'] = [
                ['label' => 'Tracked records', 'value' => (clone $tracks)->count()],
                ['label' => 'Open actions', 'value' => (clone $openTasks)->count()],
                ['label' => 'Overdue actions', 'value' => (clone $tracks)->whereNull('outcome')->whereNotNull('next_action_due_at')->where('next_action_due_at', '<', now())->count()
                    + (clone $openTasks)->whereNotNull('due_at')->where('due_at', '<', now())->count()],
            ];
        }
        if ($filtersApplied && isset($selectedWidgets['billing_document_status'])) {
            $billingProjectId = $filters['project']
                ?? DB::table('projects')->where('project_name', 'Billing Documentation')->value('project_id')
                ?? DB::table('projects')->orderBy('project_id')->value('project_id');
            $billingSummary = $billingProjectId === null
                ? ['total' => 0, 'processed' => 0, 'remaining' => 0, 'processed_today' => 0, 'source_error' => null]
                : $billingDocumentStatus->summarize((int) $billingProjectId);
            $widgetValues['billing_document_status'] = [
                ['label' => 'Applicable schools', 'value' => $billingSummary['total']],
                ['label' => 'Processed', 'value' => $billingSummary['processed']],
                ['label' => 'Remaining', 'value' => $billingSummary['remaining']],
            ];
            $widgetValues['billing_document_status_notice'] = $billingSummary['source_error'];
        }
        if ($filtersApplied && isset($selectedWidgets['inventory_monitoring'])) {
            $inventoryQuery = DB::table('inventory as inventory')
                ->join('item as items', 'items.item_id', '=', 'inventory.item_id');
            $historyQuery = DB::table('inventory_history as history')
                ->join('item as items', 'items.item_id', '=', 'history.item_id');
            if (! empty($filters['project'])) {
                $inventoryQuery->where('items.project_id', $filters['project']);
                $historyQuery->where('items.project_id', $filters['project']);
            }
            if (! empty($filters['search'])) {
                $inventoryQuery->where('items.item_name', 'like', '%'.$filters['search'].'%');
                $historyQuery->where('items.item_name', 'like', '%'.$filters['search'].'%');
            }
            $widgetValues['inventory_monitoring'] = [
                ['label' => 'Inventory records', 'value' => (clone $inventoryQuery)->count('inventory.inventory_id')],
                ['label' => 'Stock-in movements', 'value' => (clone $historyQuery)->where('history.change_type', 'stock_in')->count()],
                ['label' => 'Stock-out movements', 'value' => (clone $historyQuery)->where('history.change_type', 'stock_out')->count()],
            ];
        }

        $projects = DB::table('projects')->orderBy('project_name')->get(['project_id', 'project_name']);
        $lots = DB::table('lot')->when(! empty($filters['project']), fn (Builder $query) => $query->where('project_id', $filters['project']))
            ->orderBy('lot_name')->get(['lot_id', 'lot_name']);
        $regions = $this->dashboardLocationOptions($filters, ['project', 'lot'], 'region');
        $divisions = $this->dashboardLocationOptions($filters, ['project', 'lot', 'region'], 'division');
        $municipalities = $this->dashboardLocationOptions($filters, ['project', 'lot', 'region', 'division'], 'municipality');
        $assignedProviders = DB::table('logistics as provider')
            ->whereExists(fn (Builder $query) => $query->selectRaw('1')->from('logistics_location')
                ->whereColumn('logistics_location.logistics_id', 'provider.logistic_id'))
            ->orderBy('provider.logistic_name')
            ->get(['provider.logistic_id', 'provider.logistic_name']);

        return view('projects.dashboard', [
            'totalProjects' => $totalProjects,
            'pendingProjects' => $pendingProjects,
            'deliveredProjects' => $deliveredProjects,
            'dashboards' => $dashboards,
            'dashboard' => $dashboard,
            'dashboardWidgets' => $selectedWidgets,
            'dashboardFiltersApplied' => $filtersApplied,
            'widgetCatalog' => self::WIDGETS,
            'widgetValues' => $widgetValues,
            'filters' => $filters,
            'projects' => $projects,
            'lots' => $lots,
            'regions' => $regions,
            'divisions' => $divisions,
            'municipalities' => $municipalities,
            'assignedProviders' => $assignedProviders,
            'filteredDeliveryCount' => $filtersApplied ? (clone $deliveryQuery)->count() : null,
        ]);
    }

    /** @param array<string, mixed> $filters */
    private function dashboardDeliveriesQuery(array $filters): Builder
    {
        $query = DB::table('deliveries as d')
            ->join('lot as l', 'l.lot_id', '=', 'd.lot_id')
            ->join('projects as p', 'p.project_id', '=', 'd.project_id')
            ->join('school as s', 's.school_id', '=', 'd.school_id')
            ->leftJoin('logistics_location as ll', 'll.logistics_location_id', '=', 'd.logistics_location_id')
            ->leftJoin('logistics as provider', 'provider.logistic_id', '=', 'll.logistics_id');

        if (! empty($filters['search'])) {
            $term = '%'.$filters['search'].'%';
            $query->where(fn (Builder $searchQuery) => $searchQuery
                ->where('d.dr_no', 'like', $term)
                ->orWhere('p.project_name', 'like', $term)
                ->orWhere('s.school_name', 'like', $term)
                ->orWhere('l.lot_name', 'like', $term));
        }
        if (! empty($filters['project'])) {
            $query->where('d.project_id', $filters['project']);
        }
        if (! empty($filters['lot'])) {
            $query->where('d.lot_id', $filters['lot']);
        }
        if (! empty($filters['region'])) {
            $query->where('s.region', $filters['region']);
        }
        if (! empty($filters['division'])) {
            $query->where('s.division', $filters['division']);
        }
        if (! empty($filters['municipality'])) {
            $query->where('s.municipality', $filters['municipality']);
        }
        if (($filters['assigned_logistics'] ?? null) === 'unassigned') {
            $query->whereNull('provider.logistic_id');
        } elseif (! empty($filters['assigned_logistics'])) {
            $query->where('provider.logistic_id', (int) $filters['assigned_logistics']);
        }

        $logisticsStatus = $filters['logistics_status'] ?? null;
        if ($logisticsStatus !== null) {
            if (! Schema::hasTable('logistics_dr_tracking')) {
                if ($logisticsStatus !== 'pending') {
                    $query->whereRaw('1 = 0');
                }
            } else {
                $trackingMatch = function (Builder $trackingQuery): void {
                    $trackingQuery->selectRaw('1')
                        ->from('logistics_dr_tracking as tracking')
                        ->whereColumn('tracking.project_id', 'd.project_id');

                    if (in_array(DB::connection()->getDriverName(), ['mysql', 'mariadb'], true)) {
                        $trackingQuery->whereRaw('tracking.school_scope_hash = SHA2(CAST(d.school_id AS BINARY), 256)')
                            ->whereRaw('tracking.dr_reference_hash = SHA2(CAST(d.dr_no AS BINARY), 256)')
                            ->whereRaw('BINARY tracking.school_id = BINARY d.school_id')
                            ->whereRaw('BINARY tracking.dr_reference = BINARY d.dr_no');
                    } else {
                        $trackingQuery->whereRaw('tracking.school_id = d.school_id COLLATE BINARY')
                            ->whereRaw('tracking.dr_reference = d.dr_no COLLATE BINARY');
                    }
                };

                if ($logisticsStatus === 'pending') {
                    $query->whereNotExists($trackingMatch);
                } else {
                    $query->whereExists(function (Builder $trackingQuery) use ($trackingMatch, $logisticsStatus): void {
                        $trackingMatch($trackingQuery);
                        $trackingQuery->where('tracking.logistics_status', $logisticsStatus);
                    });
                }
            }
        }

        return $query;
    }

    /** @param array<string, mixed> $filters
     * @param  list<string>  $parentFilters
     */
    private function dashboardLocationOptions(array $filters, array $parentFilters, string $column): Collection
    {
        $query = DB::table('deliveries as d')
            ->join('school as s', 's.school_id', '=', 'd.school_id')
            ->whereNotNull('s.'.$column);

        foreach ($parentFilters as $filter) {
            if (! empty($filters[$filter])) {
                $query->where($filter === 'project' ? 'd.project_id' : ($filter === 'lot' ? 'd.lot_id' : 's.'.$filter), $filters[$filter]);
            }
        }

        return $query->select('s.'.$column.' as value')->distinct()->orderBy('s.'.$column)->pluck('value');
    }

    public function storeDashboard(Request $request): RedirectResponse
    {
        $userId = (int) $request->user()->getAuthIdentifier();
        $validated = $this->validateDashboardLayout($request, $userId);
        $dashboard = UserDashboard::query()->create([
            'user_id' => $userId,
            'name' => $validated['name'],
            'widgets' => $this->formatDashboardWidgets($validated),
            'sort_order' => (int) UserDashboard::query()->where('user_id', $userId)->max('sort_order') + 1,
        ]);

        return redirect()->route('projects.dashboard', ['dashboard_id' => $dashboard->dashboard_id]);
    }

    public function updateDashboard(Request $request, int $dashboard): RedirectResponse
    {
        $userId = (int) $request->user()->getAuthIdentifier();
        $dashboardLayout = UserDashboard::query()
            ->where('user_id', $userId)
            ->where('dashboard_id', $dashboard)
            ->firstOrFail();
        $validated = $this->validateDashboardLayout($request, $userId, $dashboardLayout->dashboard_id);
        $dashboardLayout->update([
            'name' => $validated['name'],
            'widgets' => $this->formatDashboardWidgets($validated),
        ]);

        return redirect()->route('projects.dashboard', ['dashboard_id' => $dashboardLayout->dashboard_id]);
    }

    /** @return array{name: string, widgets: list<string>, charts?: array<string, string>} */
    private function validateDashboardLayout(Request $request, int $userId, ?int $dashboardId = null): array
    {
        $request->merge(['name' => trim((string) $request->input('name'))]);

        return $request->validate([
            'name' => [
                'required',
                'string',
                'max:80',
                Rule::unique('user_dashboards', 'name')->where('user_id', $userId)->ignore($dashboardId, 'dashboard_id'),
            ],
            'widgets' => ['required', 'array', 'min:1', 'max:9'],
            'widgets.*' => ['required', 'string', Rule::in(array_keys(self::WIDGETS))],
            'charts' => ['nullable', 'array'],
            'charts.*' => ['nullable', Rule::in(['doughnut', 'bar', 'column'])],
        ]);
    }

    /** @param array{name: string, widgets: list<string>, charts?: array<string, string>} $validated
     * @return array<string, array{chart: string}>
     */
    private function formatDashboardWidgets(array $validated): array
    {
        $widgets = [];

        foreach (array_unique($validated['widgets']) as $widgetKey) {
            $kind = self::WIDGETS[$widgetKey]['kind'];
            $widgets[$widgetKey] = [
                'chart' => $kind === 'chart' ? ($validated['charts'][$widgetKey] ?? 'bar') : 'number',
            ];
        }

        return $widgets;
    }

    public function destroyDashboard(Request $request, int $dashboard): RedirectResponse
    {
        $dashboardLayout = UserDashboard::query()
            ->where('user_id', $request->user()->getAuthIdentifier())
            ->where('dashboard_id', $dashboard)
            ->where('is_default', false)
            ->firstOrFail();
        $dashboardLayout->delete();

        $nextDashboard = UserDashboard::query()
            ->where('user_id', $request->user()->getAuthIdentifier())
            ->orderBy('sort_order')
            ->orderBy('dashboard_id')
            ->first();

        return redirect()->route('projects.dashboard', $nextDashboard ? ['dashboard_id' => $nextDashboard->dashboard_id] : []);
    }
}
