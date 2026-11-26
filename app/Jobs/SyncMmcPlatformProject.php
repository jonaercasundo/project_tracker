<?php

namespace App\Jobs;

use App\Services\MmcPlatformApiClient;
use App\Services\TrackerUpdateBroadcaster;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Throwable;
use UnexpectedValueException;

class SyncMmcPlatformProject implements ShouldQueue
{
    use Queueable;

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

    /** @return array{record_count: int, entity_counts: array<string, int>, pages: int, synced_at: string} */
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
        $storedPages = DB::table('mmc_platform_project_sync_pages');
        (clone $storedPages)->where('sync_id', $syncId)->delete();

        $recordCount = 0;
        $pageCount = 0;
        $entityKeys = array_fill_keys(['schools', 'lots', 'keystages', 'items', 'packages'], []);
        $unkeyedCounts = array_fill_keys(array_keys($entityKeys), 0);
        foreach ($client->projectMasterListPages($this->data['program_id']) as $page => $records) {
            $storedPages->insert([
                'sync_id' => $syncId,
                'page' => $page,
                'records' => json_encode($records, JSON_THROW_ON_ERROR),
            ]);
            $pageCount = $page;
            $recordCount += count($records);
            $this->collectEntityKeys($records, $entityKeys, $unkeyedCounts);
        }

        $counts = [];
        foreach ($entityKeys as $type => $keys) {
            $counts[$type] = $keys === [] ? $unkeyedCounts[$type] : count($keys);
        }

        $syncedAt = now();
        $snapshots->where('id', $syncId)->update([
            'program_name' => $this->data['program_name'] ?? null,
            'program_info' => json_encode($this->data['program_info'] ?? [], JSON_THROW_ON_ERROR),
            'records' => json_encode(['storage' => 'pages', 'pages' => $pageCount], JSON_THROW_ON_ERROR),
            'entity_counts' => json_encode($counts, JSON_THROW_ON_ERROR),
            'record_count' => $recordCount,
            'synced_at' => $syncedAt,
            'updated_at' => $syncedAt,
        ]);

        return [
            'record_count' => $recordCount,
            'entity_counts' => $counts,
            'pages' => $pageCount,
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
}
