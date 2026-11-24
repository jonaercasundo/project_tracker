<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\UserDashboard;
use App\Services\BillingDocumentStatusService;
use App\Services\ProjectDrProgressReportService;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ProjectDashboardController extends Controller
{
    private const WIDGETS = [
        'delivered_assets' => ['label' => 'Delivered assets', 'kind' => 'metric'],
        'project_status' => ['label' => 'Project status', 'kind' => 'chart'],
        'delivery_status' => ['label' => 'Delivery status', 'kind' => 'chart'],
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

    public function index(Request $request): View
    {
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

        if (isset($selectedWidgets['delivered_assets'])) {
            $widgetValues['delivered_assets'] = DB::table('deliveries')->where('status', 'delivered')->count();
        }
        if (isset($selectedWidgets['project_status'])) {
            $widgetValues['project_status'] = Project::query()
                ->select('status', DB::raw('COUNT(*) AS total'))
                ->groupBy('status')
                ->orderBy('status')
                ->get()
                ->map(fn ($row): array => ['label' => (string) ($row->status ?: 'Unassigned'), 'value' => (int) $row->total])
                ->all();
        }
        if (isset($selectedWidgets['delivery_status'])) {
            $widgetValues['delivery_status'] = DB::table('deliveries')
                ->select('status', DB::raw('COUNT(*) AS total'))
                ->groupBy('status')
                ->orderBy('status')
                ->get()
                ->map(fn ($row): array => ['label' => (string) ($row->status ?: 'Unassigned'), 'value' => (int) $row->total])
                ->all();
        }

        return view('projects.dashboard', [
            'totalProjects' => Project::count(),
            'pendingProjects' => Project::where('status', 'Pending')->count(),
            'deliveredProjects' => DB::table('deliveries')
                ->where('status', 'delivered')
                ->count(),
            'dashboards' => $dashboards,
            'dashboard' => $dashboard,
            'dashboardWidgets' => $selectedWidgets,
            'widgetCatalog' => self::WIDGETS,
            'widgetValues' => $widgetValues,
        ]);
    }

    public function storeDashboard(Request $request): RedirectResponse
    {
        $request->merge(['name' => trim((string) $request->input('name'))]);
        $userId = (int) $request->user()->getAuthIdentifier();
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:80', Rule::unique('user_dashboards', 'name')->where('user_id', $userId)],
        ]);
        $dashboard = UserDashboard::query()->create([
            'user_id' => $userId,
            'name' => trim($validated['name']),
            'widgets' => [
                'project_status' => ['chart' => 'bar'],
            ],
            'sort_order' => (int) UserDashboard::query()->where('user_id', $userId)->max('sort_order') + 1,
        ]);

        return redirect()->route('projects.dashboard', ['dashboard_id' => $dashboard->dashboard_id]);
    }

    public function updateDashboard(Request $request, int $dashboard): RedirectResponse
    {
        $dashboardLayout = UserDashboard::query()
            ->where('user_id', $request->user()->getAuthIdentifier())
            ->where('dashboard_id', $dashboard)
            ->firstOrFail();
        $validated = $request->validate([
            'widgets' => ['required', 'array', 'min:1', 'max:3'],
            'widgets.*' => ['required', 'string', Rule::in(array_keys(self::WIDGETS))],
            'charts' => ['nullable', 'array'],
            'charts.*' => ['nullable', Rule::in(['doughnut', 'bar', 'column'])],
        ]);
        $widgets = [];

        foreach (array_unique($validated['widgets']) as $widgetKey) {
            $kind = self::WIDGETS[$widgetKey]['kind'];
            $widgets[$widgetKey] = [
                'chart' => $kind === 'metric'
                    ? 'number'
                    : ($validated['charts'][$widgetKey] ?? 'bar'),
            ];
        }

        $dashboardLayout->update(['widgets' => $widgets]);

        return redirect()->route('projects.dashboard', ['dashboard_id' => $dashboardLayout->dashboard_id]);
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
