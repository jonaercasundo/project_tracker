<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Illuminate\Support\Facades\DB;

class ProjectDashboardController extends Controller
{
    public function index()
    {
        // Pre-aggregate fallback coordinates by (municipality, region).
        // 'region' alone isn't unique in municipality_coordinates -- a handful
        // of town names repeat within the same region but different provinces
        // (e.g. 'Burgos' appears in 4 Region I provinces). Averaging collapses
        // those into a single approximate point instead of duplicating the join.
        $municipalityFallback = DB::table('municipality_coordinates')
            ->select(
                'municipality',
                'region',
                DB::raw('AVG(latitude) as fallback_latitude'),
                DB::raw('AVG(longitude) as fallback_longitude')
            )
            ->groupBy('municipality', 'region');

        $deliveries = DB::table('deliveries')
            ->join('projects', 'deliveries.project_id', '=', 'projects.project_id')
            ->join('school', 'deliveries.school_id', '=', 'school.school_id')
            ->leftJoinSub($municipalityFallback, 'mc', function ($join) {
                $join->on('school.municipality', '=', 'mc.municipality')
                     ->on('school.region', '=', 'mc.region');
            })
            ->where('deliveries.status', 'delivered')
            // keep the row as long as EITHER an exact school coordinate OR a
            // municipality-level fallback is available
            ->where(function ($query) {
                $query->whereNotNull('school.latitude')
                      ->orWhereNotNull('mc.fallback_latitude');
            })
            ->where(function ($query) {
                $query->whereNotNull('school.longitude')
                      ->orWhereNotNull('mc.fallback_longitude');
            })
            ->select(
                'projects.project_name',
                'school.school_name',
                'school.municipality',
                'school.region',
                DB::raw('COALESCE(school.latitude, mc.fallback_latitude) as latitude'),
                DB::raw('COALESCE(school.longitude, mc.fallback_longitude) as longitude'),
                // true when we had to fall back to the town-level coordinate
                // instead of the school's own geocode
                DB::raw('CASE WHEN school.latitude IS NULL THEN 1 ELSE 0 END as is_approximate')
            )
            ->get();

        return view('projects.dashboard', [
            'totalProjects'      => Project::count(),

            'pendingProjects'    => Project::where('status', 'Pending')->count(),

            'deliveredProjects'  => DB::table('deliveries')
                ->where('status', 'delivered')
                ->count(),

            'deliveries'         => $deliveries,
        ]);
    }
}