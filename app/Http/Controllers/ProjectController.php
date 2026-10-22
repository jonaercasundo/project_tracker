<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Services\ProjectAwardSynchronizationService;
use App\Services\ProjectBiddingEvidenceService;
use App\Services\ProjectDetailsService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class ProjectController extends Controller
{
    public function index(): View
    {
        $projectsQuery = Project::query()->orderBy('project_id');
        if (Schema::hasTable('project_information')) {
            $projectsQuery->with('bidding');
        }
        $projects = $projectsQuery->paginate(10);
        if (! Schema::hasTable('project_information')) {
            $projects->getCollection()->each(fn (Project $project) => $project->setRelation('bidding', null));
        }

        $yearExpression = DB::connection()->getDriverName() === 'sqlite'
            ? "strftime('%Y', created_at)"
            : 'YEAR(created_at)';
        $years = Project::selectRaw($yearExpression.' year')
            ->distinct()
            ->pluck('year');

        $agencies = Project::distinct()->pluck('agency');
        $agencyOptions = collect(['Deped', 'Dpwh'])
            ->merge($agencies)
            ->filter(fn (?string $agency): bool => filled($agency) && $agency !== '__other__')
            ->unique()
            ->values();
        $statuses = Project::distinct()->pluck('status');

        return view('projects.index', [
            'projects' => $projects,
            'years' => $years,
            'agencies' => $agencies,
            'agencyOptions' => $agencyOptions,
            'statuses' => $statuses,

            'totalProjects' => Project::count(),
            'pendingProjects' => Project::where('status', 'Pending')->count(),
            'deliveredProjects' => Project::where('status', 'Delivered')->count(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'ref_no' => ['required', 'string', 'max:50', Rule::unique('projects', 'ref_no')],
            'contract_number' => ['nullable', 'string', 'max:255'],
            'project_name' => 'required|string',
            'agency' => 'required|string|max:255',
            'other_agency' => 'exclude_unless:agency,__other__|required|string|max:255',
            'contract_amount' => 'required|numeric|min:0.01',
            'ABC' => 'required|numeric|min:0.01',
            'notice_of_award_date' => 'nullable|date_format:Y-m-d',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'status' => ['required', Rule::in($this->projectStatuses())],
        ], [
            'ref_no.unique' => 'A project with this Reference No. already exists.',
            'other_agency.required' => 'Please enter an agency name.',
        ]);

        if ($validated['agency'] === '__other__') {
            $validated['agency'] = $validated['other_agency'];
        }

        unset($validated['other_agency']);

        $validated['project_code'] = $validated['contract_number'] ?? null;
        unset($validated['contract_number']);

        try {
            Project::create($validated);
        } catch (QueryException $e) {
            // Fallback in case two submissions race each other and both
            // pass the validation check above before either one inserts.
            if ($e->errorInfo[1] == 1062) {
                return back()->withInput()->withErrors([
                    'ref_no' => 'A project with this Reference No. already exists.',
                ]);
            }

            throw $e;
        }

        return back()->with(
            'success',
            'Project added successfully.'
        );
    }

    public function edit(Project $project): View
    {
        if (($bidding = $project->bidding()->first()) !== null) {
            Gate::authorize('update', $bidding);
        }

        $projectStatuses = $this->projectStatuses();
        if (is_string($project->status) && ! in_array($project->status, $projectStatuses, true)) {
            $projectStatuses[] = $project->status;
        }

        return view('projects.edit', compact('project', 'projectStatuses'));
    }

    public function update(Request $request, Project $project, ProjectAwardSynchronizationService $projectSync): JsonResponse|RedirectResponse
    {
        if (($bidding = $project->bidding()->first()) !== null) {
            Gate::authorize('update', $bidding);
        }

        $validated = $request->validate([
            'ref_no' => ['required', 'string', 'max:50', Rule::unique('projects', 'ref_no')->ignore($project->getKey(), 'project_id')],
            'contract_number' => ['nullable', 'string', 'max:255'],
            'project_name' => ['required', 'string'],
            'agency' => ['required', 'string', 'max:255'],
            'contract_amount' => ['required', 'numeric', 'min:0.01'],
            'ABC' => ['required', 'numeric', 'min:0.01'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'status' => ['required', Rule::in(array_values(array_unique([...$this->projectStatuses(), $project->status])))],
        ]);

        $validated['project_code'] = $validated['contract_number'] ?? null;
        unset($validated['contract_number']);

        DB::transaction(function () use ($project, $validated, $projectSync): void {
            $lockedProject = Project::query()->whereKey($project->getKey())->lockForUpdate()->firstOrFail();
            $lockedProject->update($validated);
            $projectSync->syncFromProject($lockedProject, $validated);
        }, 3);

        if ($request->expectsJson()) {
            return response()->json(['success' => true]);
        }

        return redirect()->route('projects.index')->with('success', 'Project updated successfully.');
    }

    public function destroy(Project $project): RedirectResponse
    {
        return DB::transaction(function () use ($project): RedirectResponse {
            $lockedProject = Project::query()->whereKey($project->getKey())->lockForUpdate()->firstOrFail();

            if (Schema::hasColumn('projects', 'project_information_id') && $lockedProject->bidding()->exists()) {
                return redirect()->route('projects.index')->with('error', 'Manage this project through its linked bidding record.');
            }

            if ($this->hasDependentRecords($lockedProject)) {
                return redirect()->route('projects.index')->with('error', 'This project has linked records and cannot be deleted.');
            }

            $lockedProject->delete();

            return redirect()->route('projects.index')->with('success', 'Project deleted successfully.');
        }, 3);
    }

    private function hasDependentRecords(Project $project): bool
    {
        foreach (['AR_settings', 'deliveries', 'documents', 'invoices', 'item', 'items', 'lot', 'sales_generation', 'schools_project', 'project_items', 'project_financial_plans'] as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'project_id') && DB::table($table)->where('project_id', $project->getKey())->exists()) {
                return true;
            }
        }

        return false;
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

    public function show(Project $project, ProjectDetailsService $details, ProjectBiddingEvidenceService $biddingEvidenceService): View
    {
        if (Schema::hasTable('project_information')) {
            $project->load('bidding');
            if ($project->bidding && Schema::hasTable('project_award_events')) {
                $project->bidding->load('awardEvents');
            }
        }
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

        if (! $arSettings) {

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
                            'webp',
                        ]
                    )
                ) {
                    $logoFiles[] = $file;
                }
            }
        }

        /*
        |--------------------------------------------------------------------------
        | PROJECT STRUCTURE
        |--------------------------------------------------------------------------
        */

        $summary = $details->summary($project);
        $biddingEvidence = $biddingEvidenceService->forProject($project, auth()->user()?->currentCompany());
        $structureIsSmall = $summary['lotCount'] <= 100 && $summary['keystageCount'] <= 100;
        $lots = $structureIsSmall ? $details->lotRecords($project)->limit(100)->get() : collect();
        $keystages = $structureIsSmall ? $details->keystageRecords($project)->limit(100)->get() : collect();
        $details->attachKeystages($lots, $keystages);

        /*
        |--------------------------------------------------------------------------
        | PROJECT VIEW
        |--------------------------------------------------------------------------
        */

        return view('projects.show', [
            'project' => $project,
            'structureIsSmall' => $structureIsSmall,
            'lots' => $lots,
            'keystages' => $keystages,
            'arSettings' => $arSettings,
            'logoFiles' => $logoFiles,
            'biddingEvidence' => $biddingEvidence,
        ] + $summary);
    }

    /** @return list<string> */
    private function projectStatuses(): array
    {
        return [
            'Pending', 'Ongoing', 'Implementation', 'PDI', 'Delivered', 'Completed', 'Cancelled',
        ];
    }

    public function schoolsData(Request $request, Project $project, ProjectDetailsService $details): JsonResponse
    {
        return $this->detailData($request, $project, $details, 'schools');
    }

    public function itemsData(Request $request, Project $project, ProjectDetailsService $details): JsonResponse
    {
        return $this->detailData($request, $project, $details, 'items');
    }

    public function packagesData(Request $request, Project $project, ProjectDetailsService $details): JsonResponse
    {
        return $this->detailData($request, $project, $details, 'packages');
    }

    public function lotsData(Request $request, Project $project, ProjectDetailsService $details): JsonResponse
    {
        return $this->detailData($request, $project, $details, 'lots');
    }

    public function keystagesData(Request $request, Project $project, ProjectDetailsService $details): JsonResponse
    {
        return $this->detailData($request, $project, $details, 'keystage');
    }

    public function detailOptions(Request $request, Project $project, ProjectDetailsService $details): JsonResponse
    {
        $filters = $this->detailFilters($request);
        $request->validate(['section' => ['required', 'in:schools,items,packages,keystage']]);
        if ($request->input('section') === 'schools') {
            $request->validate(['level' => ['required', 'in:region,division,municipality']]);
        }

        return response()->json($details->options($project, $request->input('section'), $filters))
            ->header('Cache-Control', 'private, no-store');
    }

    private function detailData(Request $request, Project $project, ProjectDetailsService $details, string $section): JsonResponse
    {
        $records = $details->records($project, $section, $this->detailFilters($request));
        $totalCount = match ($section) {
            'schools' => $details->schools($project)->count(),
            'items' => $details->items($project)->count(),
            'packages' => $details->packages($project)->count(),
            'lots' => $details->lots($project)->count(),
            'keystage' => $details->keystages($project)->count(),
        };

        return response()->json([
            'html' => view('projects.partials.'.$section.'-rows', [($section === 'keystage' ? 'keystages' : $section) => $records, 'project' => $project, 'rowOffset' => ($records->currentPage() - 1) * $records->perPage()])->render(),
            'current_page' => $records->currentPage(),
            'last_page' => $records->lastPage(),
            'per_page' => $records->perPage(),
            'total' => $records->total(),
            'total_count' => $totalCount,
            'from' => $records->firstItem(),
            'to' => $records->lastItem(),
        ])->header('Cache-Control', 'private, no-store');
    }

    /** @return array<string, mixed> */
    private function detailFilters(Request $request): array
    {
        return $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'region' => ['nullable', 'string', 'max:255'],
            'division' => ['nullable', 'string', 'max:255'],
            'municipality' => ['nullable', 'string', 'max:255'],
            'item_type' => ['nullable', 'string', 'max:255'],
            'package_type' => ['nullable', 'string', 'max:255'],
            'lot' => ['nullable', 'integer', 'min:1'],
            'keystage' => ['nullable', 'integer', 'min:1'],
            'level' => ['nullable', 'in:region,division,municipality,lot,keystage,type'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);
    }
}
