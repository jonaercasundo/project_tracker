<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ProjectDashboardController extends Controller
{
    public function reportsAnalytics(): View
    {
        $deliveryStatuses = DB::table('deliveries')
            ->selectRaw("COALESCE(NULLIF(TRIM(status), ''), 'pending') as status")
            ->selectRaw('COUNT(*) as total')
            ->groupBy('status')
            ->orderByDesc('total')
            ->get();

        $totalDeliveries = (int) $deliveryStatuses->sum('total');
        $completedDeliveries = (int) $deliveryStatuses
            ->filter(fn (object $status): bool => in_array(strtolower($status->status), ['delivered', 'accepted'], true))
            ->sum('total');
        $deliveryProgress = $totalDeliveries > 0
            ? round(($completedDeliveries / $totalDeliveries) * 100, 1)
            : 0;

        $returnStatuses = Schema::hasTable('delivery_returns')
            ? DB::table('delivery_returns')
                ->select(['return_type', 'status'])
                ->selectRaw('COUNT(*) as total')
                ->selectRaw('COALESCE(SUM(quantity_returned), 0) as quantity_returned')
                ->groupBy('return_type', 'status')
                ->get()
            : collect();

        $returnSummary = collect(['Item Return', 'Document Return'])->mapWithKeys(function (string $type) use ($returnStatuses): array {
            $rows = $returnStatuses->where('return_type', $type);
            $total = (int) $rows->sum('total');
            $received = (int) $rows->where('status', 'Received')->sum('total');

            return [$type => [
                'total' => $total,
                'received' => $received,
                'open' => (int) $rows->whereIn('status', ['Reported', 'In Transit'])->sum('total'),
                'rejected' => (int) $rows->where('status', 'Rejected')->sum('total'),
                'quantity_received' => (int) $rows->where('status', 'Received')->sum('quantity_returned'),
                'received_percent' => $total > 0 ? round(($received / $total) * 100, 1) : 0,
            ]];
        });

        $recentDeliveries = DB::table('deliveries')
            ->leftJoin('projects', 'projects.project_id', '=', 'deliveries.project_id')
            ->leftJoin('school', 'school.school_id', '=', 'deliveries.school_id')
            ->orderByDesc('deliveries.delivery_date')
            ->orderByDesc('deliveries.delivery_id')
            ->limit(8)
            ->get([
                'deliveries.delivery_id',
                'deliveries.dr_no',
                'deliveries.delivery_date',
                'deliveries.status',
                'projects.project_name',
                'school.school_id',
                'school.school_name',
            ]);

        return view('projects.reports-analytics', [
            'totalProjects' => Project::count(),
            'totalDeliveries' => $totalDeliveries,
            'completedDeliveries' => $completedDeliveries,
            'openReturns' => (int) $returnSummary->sum('open'),
            'deliveryProgress' => $deliveryProgress,
            'deliveryStatuses' => $deliveryStatuses,
            'returnSummary' => $returnSummary,
            'recentDeliveries' => $recentDeliveries,
            'snapshotAt' => now(),
        ]);
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
