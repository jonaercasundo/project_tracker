<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class MmcPlatformMasterlistController extends Controller
{
    private const COLUMNS = [
        'project_id' => 'Project ID',
        'lot_no' => 'Lot No.',
        'contract_no' => 'Contract No.',
        'dr_no' => 'DR No.',
        'school_id' => 'School ID',
        'school_name' => 'School Name',
        'region' => 'Region',
        'division' => 'Division',
        'municipality' => 'Municipality',
        'address' => 'Address',
        'contact_person' => 'Contact Person',
        'contact_phone' => 'Contact Phone',
        'grade_levels' => 'Grade Levels',
        'value' => 'Value',
        'status' => 'Status',
        'sap_dr_no' => 'SAP DR No.',
        'sap_encoded_at' => 'SAP Encoded At',
        'dr_printed_at' => 'DR Printed At',
        'iar_no' => 'IAR No.',
        'itr_no' => 'ITR No.',
        'ar_no' => 'AR No.',
        'received_by' => 'Received By',
        'receiver_designation' => 'Receiver Designation',
        'inspected_by' => 'Inspected By',
        'inspector_designation' => 'Inspector Designation',
        'date_received' => 'Date Received',
        'returned_to_office_at' => 'Returned to Office At',
        'delivered_at' => 'Delivered At',
        'pouch_completed_at' => 'Pouch Completed At',
        'billing_no' => 'Billing No.',
        'sequence_no' => 'Sequence No.',
        'updated_at' => 'Updated At',
    ];

    public function updateDeliveredAt(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'program_id' => ['required', 'string', 'max:255'],
            'school_ids' => ['required', 'string', 'max:20000'],
            'delivered_at' => ['required', 'date_format:Y-m-d'],
        ]);
        $schoolIds = collect(preg_split('/[,;\s]+/u', trim($validated['school_ids']), -1, PREG_SPLIT_NO_EMPTY) ?: [])
            ->map(fn (string $schoolId): string => trim($schoolId))
            ->filter()
            ->unique()
            ->values();

        if ($schoolIds->isEmpty()) {
            throw ValidationException::withMessages(['school_ids' => 'Enter at least one School ID.']);
        }
        if ($schoolIds->count() > 500) {
            throw ValidationException::withMessages(['school_ids' => 'Enter no more than 500 School IDs at a time.']);
        }

        $companyId = (int) $request->user()->currentCompany()->getKey();
        $snapshot = DB::table('mmc_platform_project_syncs')
            ->where('company_id', $companyId)
            ->where('program_id', $validated['program_id'])
            ->first(['program_id', 'tracker_project_id']);
        abort_if($snapshot === null, 404);
        abort_if($snapshot->tracker_project_id === null, 422, 'Assign this program to a Tracker project before updating Delivered At.');

        $records = DB::table('mmc_platform_masterlist_records')
            ->where('company_id', $companyId)
            ->where('tracker_project_id', $snapshot->tracker_project_id)
            ->where('source_program_id', $snapshot->program_id)
            ->whereIn('school_id', $schoolIds);
        $matchedCount = (clone $records)->count();
        $updatedCount = (clone $records)
            ->where(function ($query) use ($validated): void {
                $query->whereNull('delivered_at')
                    ->orWhere('delivered_at', '<>', $validated['delivered_at']);
            })
            ->update([
                'delivered_at' => $validated['delivered_at'],
                'updated_at' => now(),
            ]);
        $matchedSchoolIds = (clone $records)->distinct()->pluck('school_id')->filter()->all();
        $missingSchoolIds = $schoolIds->diff($matchedSchoolIds)->values();

        $message = "Set Delivered At to {$validated['delivered_at']} for {$updatedCount} record(s).";
        if ($matchedCount === 0) {
            $message = 'No records matched the School IDs in the selected synced program.';
        } elseif ($missingSchoolIds->isNotEmpty()) {
            $message .= ' No records matched '.number_format($missingSchoolIds->count()).' School ID(s).';
        }

        return redirect()
            ->route('integrations.mmc-platform.masterlist', ['program_id' => $snapshot->program_id])
            ->with('deliveredAtUpdate', $message);
    }

    public function index(Request $request): View
    {
        $filters = $request->validate([
            'program_id' => ['nullable', 'string', 'max:255'],
            'search' => ['nullable', 'string', 'max:255'],
            'per_page' => ['nullable', Rule::in(['25', '50', '100'])],
            'sort_by' => ['nullable', Rule::in(array_keys(self::COLUMNS))],
            'sort_direction' => ['nullable', Rule::in(['asc', 'desc'])],
        ]);
        $companyId = (int) $request->user()->currentCompany()->getKey();
        $perPage = (int) ($filters['per_page'] ?? 50);
        $sortBy = $filters['sort_by'] ?? 'school_name';
        $sortDirection = $filters['sort_direction'] ?? 'asc';
        $currentPage = max(1, $request->integer('page', 1));
        $snapshots = collect();
        $snapshot = null;
        $trackerProjectName = null;
        $rows = new LengthAwarePaginator([], 0, $perPage, $currentPage, [
            'path' => $request->url(),
            'query' => $request->query(),
        ]);

        if (Schema::hasTable('mmc_platform_project_syncs')) {
            $snapshotQuery = DB::table('mmc_platform_project_syncs')
                ->where('company_id', $companyId);
            $snapshots = (clone $snapshotQuery)
                ->orderBy('program_name')
                ->orderBy('program_id')
                ->get(['program_id', 'program_name', 'tracker_project_id', 'record_count', 'synced_at']);
            $trackerProjectNames = DB::table('projects')
                ->whereIn('project_id', $snapshots->pluck('tracker_project_id')->filter()->unique())
                ->pluck('project_name', 'project_id');
            $snapshots->each(function (object $availableSnapshot) use ($trackerProjectNames): void {
                $availableSnapshot->tracker_project_name = $trackerProjectNames[$availableSnapshot->tracker_project_id] ?? null;
            });

            if ($snapshots->isNotEmpty()) {
                if (filled($filters['program_id'] ?? null)) {
                    $snapshot = (clone $snapshotQuery)
                        ->where('program_id', $filters['program_id'])
                        ->first(['id', 'program_id', 'program_name', 'tracker_project_id', 'record_count', 'synced_at']);
                    abort_if($snapshot === null, 404);
                } else {
                    $snapshot = (clone $snapshotQuery)
                        ->orderByDesc('synced_at')
                        ->first(['id', 'program_id', 'program_name', 'tracker_project_id', 'record_count', 'synced_at']);
                }

                $trackerProjectName = $snapshot->tracker_project_id === null
                    ? null
                    : DB::table('projects')->where('project_id', $snapshot->tracker_project_id)->value('project_name');

                if (Schema::hasTable('mmc_platform_masterlist_records')) {
                    $masterlistQuery = DB::table('mmc_platform_masterlist_records')
                        ->where('company_id', $companyId)
                        ->where('source_program_id', $snapshot->program_id);
                    if ($snapshot->tracker_project_id !== null) {
                        $masterlistQuery->where('tracker_project_id', $snapshot->tracker_project_id);
                    } else {
                        $masterlistQuery->whereRaw('1 = 0');
                    }

                    $search = trim((string) ($filters['search'] ?? ''));
                    if ($search !== '') {
                        $masterlistQuery->where(function ($query) use ($search): void {
                            foreach (array_keys(self::COLUMNS) as $column) {
                                $query->orWhere(match ($column) {
                                    'updated_at' => 'source_updated_at',
                                    'project_id' => 'tracker_project_id',
                                    default => $column,
                                }, 'like', '%'.$search.'%');
                            }
                        });
                    }

                    $sortColumn = match ($sortBy) {
                        'project_id' => 'tracker_project_id',
                        'updated_at' => 'source_updated_at',
                        default => $sortBy,
                    };
                    $rows = $masterlistQuery
                        ->orderBy($sortColumn, $sortDirection)
                        ->orderBy('school_name')
                        ->orderBy('dr_no')
                        ->paginate($perPage)
                        ->withQueryString();
                    $rows->setCollection($rows->getCollection()->map(function (object $record) use ($snapshot): array {
                        $row = (array) $record;
                        $row['project_id'] = $snapshot->tracker_project_id;
                        $row['updated_at'] = $row['source_updated_at'];

                        return $row;
                    }));
                }
            }
        }

        return view('integrations.masterlist.index', [
            'snapshots' => $snapshots,
            'snapshot' => $snapshot,
            'rows' => $rows,
            'filters' => $filters,
            'perPage' => $perPage,
            'columns' => self::COLUMNS,
            'sortBy' => $sortBy,
            'sortDirection' => $sortDirection,
            'trackerProjectName' => $trackerProjectName ?? null,
        ]);
    }
}
