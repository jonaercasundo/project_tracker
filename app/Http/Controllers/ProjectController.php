<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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

            'totalProjects' => Project::count(),
            'pendingProjects' => Project::where('status', 'Pending')->count(),
            'deliveredProjects' => Project::where('status', 'Delivered')->count(),
        ]);
    }


    public function store(Request $request)
    {
        $request->validate([
            'ref_no' => 'required',
            'project_name' => 'required',
            'agency' => 'required',
            'contract_amount' => 'required|numeric',
            'ABC' => 'required|numeric',
            'start_date' => 'required',
            'end_date' => 'required',
            'status' => 'required'
        ]);

        Project::create($request->all());

        return back()->with(
            'success',
            'Project added successfully.'
        );
    }


    public function update(Request $request, Project $project)
    {
        $request->validate([
            'ref_no' => 'required',
            'project_name' => 'required',
            'contract_amount' => 'required|numeric',
            'ABC' => 'required|numeric',
            'start_date' => 'required',
            'end_date' => 'required',
            'status' => 'required'
        ]);

        $project->update($request->all());

        return response()->json([
            'success' => true
        ]);
    }


    public function filter(Request $request)
    {
        $query = Project::query();

        if ($request->year) {
            $query->whereYear(
                'created_at',
                $request->year
            );
        }

        if ($request->agency) {
            $query->where(
                'agency',
                $request->agency
            );
        }

        if ($request->status) {
            $query->where(
                'status',
                $request->status
            );
        }

        return response()->json(
            $query
                ->orderBy('project_id')
                ->get()
        );
    }

    public function show(Project $project)
    {
        /*
        |--------------------------------------------------------------------------
        | AR SETTINGS
        |--------------------------------------------------------------------------
        */

        $arSettings = DB::table('AR_settings as ar')
            ->leftJoin(
                'projects as p',
                'p.project_id',
                '=',
                'ar.project_id'
            )
            ->where(
                'ar.project_id',
                $project->project_id
            )
            ->select([
                DB::raw(
                    'COALESCE(ar.project_name, p.project_name) as project_name'
                ),

                'ar.company',
                'ar.client',
                'ar.ar_company_footer',
                'ar.ar_address_footer',
                'ar.ar_contact_footer',

                DB::raw(
                    'COALESCE(ar.display_label, 0) as display_label'
                ),

                DB::raw(
                    "COALESCE(ar.ar_logo, 'logo.webp') as ar_logo"
                ),

                DB::raw(
                    'COALESCE(ar.display_school_id, 0) as display_school_id'
                ),

                DB::raw(
                    'COALESCE(ar.label_school_id, 0) as label_school_id'
                ),

                DB::raw(
                    'COALESCE(ar.label_municipality, 0) as label_municipality'
                ),

                DB::raw(
                    'COALESCE(ar.label_division, 0) as label_division'
                ),

                DB::raw(
                    'COALESCE(ar.label_region, 0) as label_region'
                ),
            ])
            ->first();


        /*
        |--------------------------------------------------------------------------
        | CREATE DEFAULT AR SETTINGS IF NONE EXIST
        |--------------------------------------------------------------------------
        */

        if (!$arSettings) {

            $arSettings = (object) [
                'project_name' => $project->project_name,

                'company' => '',
                'client' => '',

                'ar_company_footer' => '',
                'ar_address_footer' => '',
                'ar_contact_footer' => '',

                'display_label' => 0,
                'display_school_id' => 0,

                'ar_logo' => 'logo.webp',

                'label_school_id' => 0,
                'label_municipality' => 0,
                'label_division' => 0,
                'label_region' => 0,
            ];
        }


        /*
        |--------------------------------------------------------------------------
        | LOGO FILES
        |--------------------------------------------------------------------------
        */

        $logoPath = public_path(
            'assets/uploads/logo'
        );

        $logoFiles = [];

        if (is_dir($logoPath)) {

            $files = scandir($logoPath);

            foreach ($files as $file) {

                if (
                    $file === '.' ||
                    $file === '..'
                ) {
                    continue;
                }

                $extension = strtolower(
                    pathinfo(
                        $file,
                        PATHINFO_EXTENSION
                    )
                );

                if (
                    in_array(
                        $extension,
                        [
                            'png',
                            'jpg',
                            'jpeg',
                            'webp'
                        ]
                    )
                ) {
                    $logoFiles[] = $file;
                }
            }
        }


        /*
        |--------------------------------------------------------------------------
        | PROJECT VIEW
        |--------------------------------------------------------------------------
        */

        return view('projects.show', [
            'project' => $project,
            'arSettings' => $arSettings,
            'logoFiles' => $logoFiles,
        ]);
    }

}
