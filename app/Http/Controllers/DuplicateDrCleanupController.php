<?php

namespace App\Http\Controllers;

use App\Services\DuplicateDrCleanupService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class DuplicateDrCleanupController extends Controller
{
    public function __construct(private DuplicateDrCleanupService $cleanup) {}

    public function index(Request $request): View
    {
        $company = $request->user()->currentCompany();
        abort_unless($company?->code === 'MMC' && $company->is_active, 403);
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:120'],
            'project_id' => ['nullable', 'integer', 'min:1'],
            'status' => ['nullable', 'string', 'max:40'],
        ]);
        $projects = DB::table('projects')->when(Schema::hasColumn('projects', 'company_id'), function ($query) use ($company): void {
            $query->whereNull('company_id')->orWhere('company_id', $company->company_id);
        })->orderBy('project_name')->get(['project_id', 'project_name']);

        return view('deliveries.duplicate-cleanup', [
            'groups' => $this->cleanup->groups((int) $company->company_id, $filters),
            'projects' => $projects,
            'filters' => $filters,
        ]);
    }

    public function export(Request $request): Response
    {
        $company = $request->user()->currentCompany();
        abort_unless($company?->code === 'MMC' && $company->is_active, 403);
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:120'],
            'project_id' => ['nullable', 'integer', 'min:1'],
            'status' => ['nullable', 'string', 'max:40'],
        ]);
        $groups = $this->cleanup->allGroups((int) $company->company_id, $filters);
        $stream = fopen('php://temp', 'r+');
        fputcsv($stream, ['Project', 'DR Number', 'School', 'Lot', 'Delivery IDs', 'Statuses', 'Packages', 'Resolution eligibility', 'Excluded because']);
        foreach ($groups as $group) {
            fputcsv($stream, [
                $group->project_name, $group->dr_no, $group->school_name, $group->lot_name,
                $group->records->pluck('delivery_id')->implode(', '), $group->records->pluck('status')->implode(', '),
                $group->packages->map(fn ($package): string => 'Package '.$package->package_num.' ('.$package->status.')')->implode('; '),
                $group->eligible ? 'Review eligible' : 'Blocked', $group->exclusion,
            ]);
        }
        rewind($stream);
        $csv = stream_get_contents($stream);
        fclose($stream);

        return response($csv, 200, ['Content-Type' => 'text/csv; charset=UTF-8', 'Content-Disposition' => 'attachment; filename="duplicate-dr-cleanup-preview.csv"']);
    }

    public function resolve(Request $request): RedirectResponse
    {
        $company = $request->user()->currentCompany();
        abort_unless($company?->code === 'MMC' && $company->is_active, 403);
        $data = $request->validate([
            'keep_delivery_id' => ['required', 'integer', 'min:1'],
            'affected_delivery_ids' => ['required', 'array', 'min:1'],
            'affected_delivery_ids.*' => ['required', 'integer', 'distinct', 'min:1'],
        ]);
        abort_if(in_array((int) $data['keep_delivery_id'], array_map('intval', $data['affected_delivery_ids']), true), 422);
        $this->cleanup->resolve((int) $company->company_id, $request->user(), (int) $data['keep_delivery_id'], array_map('intval', $data['affected_delivery_ids']));

        return back()->with('status', 'Duplicate delivery rows were marked Cancelled. Linked records and audit history were preserved.');
    }
}
