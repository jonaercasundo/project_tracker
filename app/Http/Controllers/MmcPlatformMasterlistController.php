<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class MmcPlatformMasterlistController extends Controller
{
    private const COLUMNS = [
        'delivery_id' => 'Delivery ID',
        'lot_id' => 'Lot ID',
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

    public function index(Request $request): View
    {
        $filters = $request->validate([
            'program_id' => ['nullable', 'string', 'max:255'],
            'search' => ['nullable', 'string', 'max:255'],
            'per_page' => ['nullable', Rule::in(['25', '50', '100'])],
        ]);
        $companyId = (int) $request->user()->currentCompany()->getKey();
        $perPage = (int) ($filters['per_page'] ?? 50);
        $currentPage = max(1, $request->integer('page', 1));
        $snapshots = collect();
        $snapshot = null;
        $trackerProjectName = null;
        $rows = new LengthAwarePaginator([], 0, $perPage, $currentPage, [
            'path' => $request->url(),
            'query' => $request->query(),
        ]);

        if (Schema::hasTable('mmc_platform_project_syncs') && Schema::hasTable('mmc_platform_project_sync_pages')) {
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
                                $query->orWhere($column === 'updated_at' ? 'source_updated_at' : $column, 'like', '%'.$search.'%');
                            }
                        });
                    }

                    $rows = $masterlistQuery
                        ->orderBy('school_name')
                        ->orderBy('dr_no')
                        ->paginate($perPage)
                        ->withQueryString();
                    $rows->setCollection($rows->getCollection()->map(function (object $record): array {
                        $row = (array) $record;
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
            'trackerProjectName' => $trackerProjectName ?? null,
        ]);
    }
}
