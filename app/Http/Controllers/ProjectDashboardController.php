<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Services\BillingDocumentStatusService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProjectDashboardController extends Controller
{
    public function reportsAnalytics(Request $request, BillingDocumentStatusService $billingDocumentStatus): View
    {
        $projects = Project::query()->orderBy('project_name')->get(['project_id', 'project_name']);
        $selectedProjectId = $request->validate([
            'project_id' => ['nullable', 'integer', 'exists:projects,project_id'],
        ])['project_id'] ?? $projects->firstWhere('project_name', 'Billing Documentation')?->project_id ?? $projects->first()?->project_id;
        $selectedProject = $projects->firstWhere('project_id', $selectedProjectId);
        $summary = $selectedProjectId !== null
            ? $billingDocumentStatus->summarize((int) $selectedProjectId)
            : ['total' => 0, 'processed' => 0, 'remaining' => 0, 'processed_today' => 0, 'source_error' => null];

        return view('projects.reports-analytics', [
            'projects' => $projects,
            'selectedProject' => $selectedProject,
            'selectedProjectId' => $selectedProjectId,
            'summary' => $summary,
            'snapshotAt' => now(),
        ]);
    }

    public function reportsAnalyticsData(Request $request, BillingDocumentStatusService $billingDocumentStatus): JsonResponse
    {
        $validated = $request->validate([
            'project_id' => ['required', 'integer', 'exists:projects,project_id'],
        ]);

        return response()->json([
            'summary' => $billingDocumentStatus->summarize((int) $validated['project_id']),
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
