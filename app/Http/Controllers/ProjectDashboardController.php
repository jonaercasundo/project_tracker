<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Illuminate\Support\Facades\DB;

class ProjectDashboardController extends Controller
{
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
            'totalProjects'     => Project::count(),
            'pendingProjects'   => Project::where('status', 'Pending')->count(),
            'deliveredProjects' => DB::table('deliveries')
                                        ->where('status', 'delivered')
                                        ->count(),
            'deliveries' => $deliveries,
        ]);
    }
}