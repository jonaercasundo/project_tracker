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
        })->orderBy('project_name')->get();

        return view('deliveries.duplicate-cleanup', [
            'groups' => $this->cleanup->groups((int) $company->company_id, $filters),
            'summary' => $this->cleanup->summary((int) $company->company_id, $filters),
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
        fputcsv($stream, ['Project', 'Normalized DR Number', 'Review label', 'Delivery ID', 'School', 'Lot', 'Keystage', 'Delivery date', 'Status', 'Packages', 'Exact duplicate subset', 'Resolution blocked because']);
        foreach ($groups as $group) {
            foreach ($group->records as $record) {
                $matchingSet = $group->exactDuplicateSets->first(fn (object $set): bool => $set->records->contains('delivery_id', $record->delivery_id));
                $packages = $group->packages->where('delivery_id', $record->delivery_id)
                    ->map(fn (object $package): string => 'Package '.$package->package_num.' ('.$package->status.')')->implode('; ');
                fputcsv($stream, [
                    $group->project_name, $group->normalized_dr, 'Possible duplicate', $record->delivery_id,
                    $record->school_name, $record->lot_name, $record->keystage_id ?? '', $record->delivery_date ?? '', $record->status,
                    $packages, $matchingSet ? $matchingSet->records->pluck('delivery_id')->implode(', ') : '',
                    $matchingSet && ! $matchingSet->eligible ? $matchingSet->exclusion : '',
                ]);
            }
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
