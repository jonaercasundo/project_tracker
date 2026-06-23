<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    public function index()
    {
        $projects = Project::orderBy('project_id')->paginate(10);

        $years = Project::selectRaw('YEAR(created_at) year')
            ->distinct()
            ->pluck('year');

        $agencies = Project::distinct()->pluck('agency');
        $statuses = Project::distinct()->pluck('status');

        return view('projects.index', [
            'projects' => $projects,
            'years' => $years,
            'agencies' => $agencies,
            'statuses' => $statuses,

            // ✅ ADD THESE
            'totalProjects' => Project::count(),
            'pendingProjects' => Project::where('status', 'Pending')->count(),
            'deliveredProjects' => Project::where('status', 'Delivered')->count(),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'ref_no'=>'required',
            'project_name'=>'required',
            'agency'=>'required',
            'contract_amount'=>'required|numeric',
            'ABC'=>'required|numeric',
            'start_date'=>'required',
            'end_date'=>'required',
            'status'=>'required'
        ]);

        Project::create($request->all());

        return back()->with('success','Project added successfully.');
    }

    public function update(Request $request, Project $project)
    {
        $request->validate([
            'ref_no'=>'required',
            'project_name'=>'required',
            'contract_amount'=>'required|numeric',
            'ABC'=>'required|numeric',
            'start_date'=>'required',
            'end_date'=>'required',
            'status'=>'required'
        ]);

        $project->update($request->all());

        return response()->json([
            'success'=>true
        ]);
    }

    public function filter(Request $request)
    {
        $query = Project::query();

        if ($request->year) {
            $query->whereYear('created_at',$request->year);
        }

        if ($request->agency) {
            $query->where('agency',$request->agency);
        }

        if ($request->status) {
            $query->where('status',$request->status);
        }

        return response()->json(
            $query->orderBy('project_id')->get()
        );
    }
    public function show(Project $project)
    {
        return view('projects.show', compact('project'));
    }
}