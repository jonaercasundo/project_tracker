<?php

namespace App\Jobs;

use App\Services\MmcPlatformApiClient;
use App\Services\TrackerUpdateBroadcaster;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Throwable;
use UnexpectedValueException;

class SyncMmcPlatformProject implements ShouldQueue
{
    use Queueable;

    private const MASTERLIST_COLUMNS = [
        'delivery_id', 'lot_id', 'lot_no', 'contract_no', 'dr_no', 'school_id', 'school_name',
        'region', 'division', 'municipality', 'address', 'contact_person', 'contact_phone',
        'grade_levels', 'value', 'status', 'sap_dr_no', 'sap_encoded_at', 'dr_printed_at',
        'iar_no', 'itr_no', 'ar_no', 'received_by', 'receiver_designation', 'inspected_by',
        'inspector_designation', 'date_received', 'returned_to_office_at', 'delivered_at',
        'pouch_completed_at', 'billing_no', 'sequence_no',
    ];

    public int $tries = 1;

    public int $timeout = 1800;

    public bool $failOnTimeout = true;

    /** @param array{program_id: string, tracker_project_id: int, program_name?: ?string, program_info?: ?array} $data */
    public function __construct(
        private string $statusKey,
        private int $companyId,
        private array $data,
    ) {
        $this->onConnection('mmc_platform_sync');
    }

    public static function statusKey(int $companyId, int $userId, string $syncId): string
    {
        return "mmc-platform-sync:{$companyId}:{$userId}:{$syncId}";
    }

    public function handle(MmcPlatformApiClient $client): void
    {
        try {
            $summary = DB::transaction(fn (): array => $this->saveSnapshot($client));
        } catch (UnexpectedValueException $exception) {
            report($exception);
            $this->storeStatus(['status' => 'failed', 'message' => $exception->getMessage()]);

            return;
        }

        $this->storeStatus([
            'status' => 'success',
            'message' => 'MMC project data saved to Tracker.',
            ...$summary,
        ]);

        if (in_array($this->data['program_id'], config('services.mmc_platform.report_programs', []), true)) {
            app(TrackerUpdateBroadcaster::class)->forCompany($this->companyId, ['mmc-platform-report']);
        }
    }

    /** @return array{record_count: int, entity_counts: array<string, int>, pages: int, created_count: int, updated_count: int, unchanged_count: int, deleted_count: int, synced_at: string} */
    private function saveSnapshot(MmcPlatformApiClient $client): array
    {
        $snapshots = DB::table('mmc_platform_project_syncs');
        $identity = ['company_id' => $this->companyId, 'program_id' => $this->data['program_id']];
        $startedAt = now();
        $snapshots->upsert([[
            ...$identity,
            'tracker_project_id' => $this->data['tracker_project_id'],
            'records' => '{}',
            'entity_counts' => '{}',
            'synced_at' => $startedAt,
            'created_at' => $startedAt,
            'updated_at' => $startedAt,
        ]], ['company_id', 'program_id'], ['tracker_project_id', 'updated_at']);

        $syncId = (clone $snapshots)->where($identity)->value('id');

        $recordCount = 0;
        $pageCount = 0;
        $seenDeliveryIds = [];
        $createdCount = 0;
        $updatedCount = 0;
        $unchangedCount = 0;
        $deletedCount = 0;
        $entityKeys = array_fill_keys(['schools', 'lots', 'keystages', 'items', 'packages'], []);
        $unkeyedCounts = array_fill_keys(array_keys($entityKeys), 0);
        foreach ($client->projectMasterListPages($this->data['program_id']) as $page => $records) {
            $pageCount = $page;
            $recordCount += count($records);
            $this->collectEntityKeys($records, $entityKeys, $unkeyedCounts);

            $masterlistRows = [];
            foreach ($records as $record) {
                if (! is_scalar($record['delivery_id'] ?? null) || trim((string) $record['delivery_id']) === '') {
                    throw new UnexpectedValueException('A master-list record is missing its delivery ID. No data was saved.');
                }

                $deliveryId = (string) $record['delivery_id'];
                $seenDeliveryIds[$deliveryId] = true;
                $masterlistRows[$deliveryId] = $this->masterlistRow($record, $startedAt);
            }

            if ($masterlistRows !== []) {
                $existingRows = DB::table('mmc_platform_masterlist_records')
                    ->where('company_id', $this->companyId)
                    ->where('source_program_id', $this->data['program_id'])
                    ->whereIn('delivery_id', array_keys($masterlistRows))
                    ->get()
                    ->keyBy('delivery_id');
                $rowsToUpsert = [];

                foreach ($masterlistRows as $deliveryId => $row) {
                    $existingRow = $existingRows->get($deliveryId);
                    if ($existingRow === null) {
                        $createdCount++;
                        $rowsToUpsert[] = $row;
                    } else {
                        if (! filled($row['delivered_at']) && filled($existingRow->delivered_at)) {
                            $row['delivered_at'] = $existingRow->delivered_at;
                        }

                        if ($this->masterlistRowHasChanges($existingRow, $row)) {
                            $updatedCount++;
                            $rowsToUpsert[] = $row;
                        } else {
                            $unchangedCount++;
                        }
                    }
                }

                foreach (array_chunk($rowsToUpsert, 100) as $rowsChunk) {
                    DB::table('mmc_platform_masterlist_records')->upsert(
                        $rowsChunk,
                        ['company_id', 'source_program_id', 'delivery_id'],
                        ['tracker_project_id', ...self::MASTERLIST_COLUMNS, 'source_updated_at', 'updated_at'],
                    );
                }
            }
        }

        if ($seenDeliveryIds !== [] || $recordCount === 0) {
            $staleDeliveryIds = [];
            foreach (DB::table('mmc_platform_masterlist_records')
                ->where('company_id', $this->companyId)
                ->where('source_program_id', $this->data['program_id'])
                ->pluck('delivery_id') as $existingDeliveryId) {
                if (! isset($seenDeliveryIds[(string) $existingDeliveryId])) {
                    $staleDeliveryIds[] = $existingDeliveryId;
                }
            }

            foreach (array_chunk($staleDeliveryIds, 500) as $deliveryIds) {
                $deletedCount += DB::table('mmc_platform_masterlist_records')
                    ->where('company_id', $this->companyId)
                    ->where('source_program_id', $this->data['program_id'])
                    ->whereIn('delivery_id', $deliveryIds)
                    ->delete();
            }
        }

        $counts = [];
        foreach ($entityKeys as $type => $keys) {
            $counts[$type] = $keys === [] ? $unkeyedCounts[$type] : count($keys);
        }

        $syncedAt = now();
        $snapshots->where('id', $syncId)->update([
            'program_name' => $this->data['program_name'] ?? null,
            'program_info' => json_encode($this->data['program_info'] ?? [], JSON_THROW_ON_ERROR),
            'records' => json_encode(['storage' => 'masterlist_table', 'pages' => $pageCount], JSON_THROW_ON_ERROR),
            'entity_counts' => json_encode($counts, JSON_THROW_ON_ERROR),
            'record_count' => $recordCount,
            'synced_at' => $syncedAt,
            'updated_at' => $syncedAt,
        ]);

        return [
            'record_count' => $recordCount,
            'entity_counts' => $counts,
            'pages' => $pageCount,
            'created_count' => $createdCount,
            'updated_count' => $updatedCount,
            'unchanged_count' => $unchangedCount,
            'deleted_count' => $deletedCount,
            'synced_at' => $syncedAt->toIso8601String(),
        ];
    }

    public function failed(?Throwable $exception): void
    {
        $this->storeStatus([
            'status' => 'failed',
            'message' => 'The project sync could not complete. Please try again.',
        ]);
    }

    /** @param array<string, mixed> $result */
    private function storeStatus(array $result): void
    {
        Cache::put($this->statusKey, $result, now()->addHours(2));
    }

    /**
     * @param  list<array<string, mixed>>  $records
     * @param  array<string, array<array-key, true>>  $keys
     * @param  array<string, int>  $unkeyed
     */
    private function collectEntityKeys(array $records, array &$keys, array &$unkeyed): void
    {
        $collect = function (string $type, mixed $key) use (&$keys, &$unkeyed): void {
            if (is_scalar($key)) {
                $keys[$type][(string) $key] = true;
            } else {
                $unkeyed[$type]++;
            }
        };
        $collectPackages = function (array $row) use ($collect): void {
            foreach ((array) ($row['packages'] ?? $row['package'] ?? []) as $package) {
                if (is_array($package)) {
                    $collect('packages', $package['package_id'] ?? $package['id'] ?? null);
                }
            }
        };

        foreach ($records as $record) {
            if (isset($record['school_id'])) {
                $collect('schools', $record['school_id']);
            }
            if (isset($record['lot_id'])) {
                $collect('lots', $record['lot_id']);
            }
            foreach (['keystages', 'key_stages', 'key_stages_data', 'grade_levels'] as $key) {
                $stages = $record[$key] ?? [];
                foreach (is_string($stages) ? explode(',', $stages) : (array) $stages as $stage) {
                    if (is_array($stage)) {
                        $collect('keystages', $stage['id'] ?? $stage['key_stage_id'] ?? $stage['name'] ?? null);
                    } elseif (is_string($stage) && trim($stage) !== '') {
                        $collect('keystages', trim($stage));
                    }
                }
            }
            foreach ((array) ($record['lines'] ?? []) as $item) {
                if (is_array($item)) {
                    $collect('items', $item['item_id'] ?? $item['item_code'] ?? $item['id'] ?? null);
                    $collectPackages($item);
                }
            }
            $collectPackages($record);
        }
    }

    /** @param array<string, mixed> $record
     * @return array<string, mixed>
     */
    private function masterlistRow(array $record, Carbon $syncedAt): array
    {
        $row = [
            'company_id' => $this->companyId,
            'tracker_project_id' => $this->data['tracker_project_id'],
            'source_program_id' => $this->data['program_id'],
            'created_at' => $syncedAt,
            'updated_at' => $syncedAt,
        ];

        foreach (self::MASTERLIST_COLUMNS as $column) {
            $value = $record[$column] ?? null;
            $row[$column] = $this->databaseValue($value);
        }
        $row['source_updated_at'] = $this->databaseValue($record['updated_at'] ?? null);

        return $row;
    }

    private function databaseValue(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if (is_scalar($value)) {
            return (string) $value;
        }

        return json_encode($value, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /** @param array<string, mixed> $newRow */
    private function masterlistRowHasChanges(object $existingRow, array $newRow): bool
    {
        foreach (['tracker_project_id', ...self::MASTERLIST_COLUMNS, 'source_updated_at'] as $column) {
            $existingValue = $existingRow->{$column} ?? null;
            $newValue = $newRow[$column] ?? null;
            if ($existingValue === null || $newValue === null) {
                if ($existingValue !== $newValue) {
                    return true;
                }

                continue;
            }

            if ((string) $existingValue !== (string) $newValue) {
                return true;
            }
        }

        return false;
    }
}
