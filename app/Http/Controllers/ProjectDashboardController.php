<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Services\BillingDocumentStatusService;
use App\Services\ProjectDrProgressReportService;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProjectDashboardController extends Controller
{
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

    public function index()
    {
        $deliveries = DB::table('deliveries')
            ->join('projects', 'deliveries.project_id', '=', 'projects.project_id')
            ->join('school', 'deliveries.school_id', '=', 'school.school_id')
            ->join('municipality_coordinates as mc', function ($join) {
                $join->on('school.municipality', '=', 'mc.municipality')
                    ->on('school.region', '=', 'mc.region');
            })
            ->where('deliveries.status', 'delivered')
            ->groupBy('school.region')
            ->select(
                'school.region',
                DB::raw('COUNT(deliveries.delivery_id) AS total_deliveries'),
                DB::raw('COUNT(DISTINCT projects.project_id) AS total_projects'),
                DB::raw('AVG(mc.latitude) AS latitude'),
                DB::raw('AVG(mc.longitude) AS longitude')
            )
            ->orderBy('school.region')
            ->get();

        return view('projects.dashboard', [
            'totalProjects' => Project::count(),
            'pendingProjects' => Project::where('status', 'Pending')->count(),
            'deliveredProjects' => DB::table('deliveries')
                ->where('status', 'delivered')
                ->count(),
            'deliveries' => $deliveries,
        ]);
    }
}
