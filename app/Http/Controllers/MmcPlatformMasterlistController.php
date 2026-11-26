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

                $rows = $this->recordsPage(
                    (int) $snapshot->id,
                    max(0, (int) $snapshot->record_count),
                    trim((string) ($filters['search'] ?? '')),
                    $perPage,
                    $currentPage,
                    $request,
                );
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

    private function recordsPage(
        int $syncId,
        int $recordCount,
        string $search,
        int $perPage,
        int $currentPage,
        Request $request,
    ): LengthAwarePaginator {
        $offset = ($currentPage - 1) * $perPage;
        $matchedCount = 0;
        $pageRecords = [];
        $searchTerm = mb_strtolower($search);

        foreach (DB::table('mmc_platform_project_sync_pages')
            ->where('sync_id', $syncId)
            ->orderBy('page')
            ->cursor() as $storedPage) {
            $records = json_decode($storedPage->records, true);
            if (! is_array($records)) {
                continue;
            }

            foreach ($records as $record) {
                if (! is_array($record)) {
                    continue;
                }

                if ($searchTerm !== '' && ! str_contains(mb_strtolower(json_encode($record, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: ''), $searchTerm)) {
                    continue;
                }

                if ($matchedCount >= $offset && count($pageRecords) < $perPage) {
                    $pageRecords[] = $this->presentRecord($record);
                }
                $matchedCount++;
            }

            if ($searchTerm === '' && count($pageRecords) >= $perPage) {
                break;
            }
        }

        return new LengthAwarePaginator(
            $pageRecords,
            $searchTerm === '' ? $recordCount : $matchedCount,
            $perPage,
            $currentPage,
            ['path' => $request->url(), 'query' => $request->query()],
        );
    }

    /** @param array<string, mixed> $record
     * @return array<string, mixed>
     */
    private function presentRecord(array $record): array
    {
        return collect(self::COLUMNS)
            ->mapWithKeys(fn (string $label, string $key): array => [
                $key => is_array($record[$key] ?? null) ? $this->displayValue($record[$key]) : ($record[$key] ?? null),
            ])
            ->all();
    }

    private function displayValue(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_array($value)) {
            return implode(', ', array_filter(array_map(
                fn (mixed $item): ?string => is_scalar($item) ? (string) $item : (is_array($item) ? (string) ($item['name'] ?? $item['label'] ?? $item['id'] ?? '') : null),
                $value,
            )));
        }

        return is_scalar($value) ? (string) $value : null;
    }
}
