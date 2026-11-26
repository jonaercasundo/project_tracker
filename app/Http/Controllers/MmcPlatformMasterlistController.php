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
                ->get(['program_id', 'program_name', 'record_count', 'synced_at']);

            if ($snapshots->isNotEmpty()) {
                if (filled($filters['program_id'] ?? null)) {
                    $snapshot = (clone $snapshotQuery)
                        ->where('program_id', $filters['program_id'])
                        ->first(['id', 'program_id', 'program_name', 'record_count', 'synced_at']);
                    abort_if($snapshot === null, 404);
                } else {
                    $snapshot = (clone $snapshotQuery)
                        ->orderByDesc('synced_at')
                        ->first(['id', 'program_id', 'program_name', 'record_count', 'synced_at']);
                }

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
        $items = $record['lines'] ?? $record['items'] ?? [];
        $items = is_array($items) ? array_values(array_filter($items, 'is_array')) : [];

        return [
            'delivery_id' => $this->displayValue($record['delivery_id'] ?? $record['deliveryId'] ?? null),
            'lot_id' => $this->displayValue($record['lot_id'] ?? $record['lotId'] ?? null),
            'lot_number' => $this->displayValue($record['lot_no'] ?? $record['lot_number'] ?? null),
            'contract_number' => $this->displayValue($record['contract_no'] ?? $record['contract_number'] ?? null),
            'dr_number' => $this->displayValue($record['dr_no'] ?? $record['dr_number'] ?? null),
            'school_id' => $record['school_id'] ?? $record['schoolId'] ?? $record['id'] ?? null,
            'school_name' => $this->displayValue($record['school_name'] ?? data_get($record, 'school.name') ?? $record['school'] ?? $record['name'] ?? null),
            'region' => $this->displayValue($record['region'] ?? data_get($record, 'school.region')),
            'division' => $this->displayValue($record['division'] ?? data_get($record, 'school.division')),
            'municipality' => $this->displayValue($record['municipality'] ?? $record['city_municipality'] ?? data_get($record, 'school.municipality')),
            'address' => $this->displayValue($record['address'] ?? data_get($record, 'school.address')),
            'contact_person' => $this->displayValue($record['contact_person'] ?? data_get($record, 'contact.person')),
            'contact_phone' => $this->displayValue($record['contact_phone'] ?? $record['contact_number'] ?? data_get($record, 'contact.phone')),
            'grade_levels' => $this->displayValue($record['grade_levels'] ?? $record['key_stages'] ?? $record['keystages'] ?? null),
            'items' => array_map(fn (array $item): array => [
                'name' => $this->displayValue($item['item_name'] ?? $item['name'] ?? $item['description'] ?? $item['item_description'] ?? null),
                'code' => $this->displayValue($item['item_code'] ?? $item['code'] ?? $item['item_id'] ?? null),
                'quantity' => $this->displayValue($item['quantity'] ?? $item['qty'] ?? $item['total_quantity'] ?? null),
            ], $items),
        ];
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
