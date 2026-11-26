<?php

namespace App\Services;

use App\Http\Middleware\ProfileProjectDashboard;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class BillingDocumentStatusService
{
    private const LOTS = ['2', '9', '11'];

    private const EXCLUDED_DELIVERY_STATUSES = ['cancelled', 'canceled', 'void', 'voided', 'deleted', 'rejected'];

    /** @return array{total: ?int, processed: ?int, remaining: ?int, processed_today: ?int, source_error: ?string} */
    public function summarize(int $projectId): array
    {
        if (! Schema::hasTable('mmc_platform_masterlist_records')) {
            return $this->unavailableSummary('MMC Masterlist data is unavailable. Sync the selected Tracker project first.');
        }

        $records = DB::table('mmc_platform_masterlist_records')
            ->where('tracker_project_id', $projectId)
            ->whereNotNull('school_id')
            ->where('school_id', '<>', '')
            ->whereNotNull('dr_no')
            ->where('dr_no', '<>', '')
            ->get(['school_id', 'dr_no', 'lot_no', 'status', 'date_received', 'received_by']);
        ProfileProjectDashboard::current()?->loaded($records->count());

        if ($records->isEmpty()) {
            return $this->unavailableSummary('No MMC Masterlist records are linked to the selected Tracker project.');
        }

        $today = now(config('app.timezone'))->toDateString();
        $schools = [];
        foreach ($records as $record) {
            if (in_array(mb_strtolower(trim((string) $record->status)), self::EXCLUDED_DELIVERY_STATUSES, true)) {
                continue;
            }

            $lotNumber = $this->lotNumber((string) $record->lot_no);
            $schoolId = $this->normalizeSchoolId((string) $record->school_id);
            if ($lotNumber === null || $schoolId === '') {
                continue;
            }

            $schools[$schoolId][] = [
                'date_received' => trim((string) $record->date_received),
                'received_by' => trim((string) $record->received_by),
            ];
        }

        $total = count($schools);
        $processed = 0;
        $processedToday = 0;
        foreach ($schools as $schoolRecords) {
            $isProcessed = collect($schoolRecords)->every(fn (array $record): bool => $record['date_received'] !== '' && $record['received_by'] !== '');
            if ($isProcessed) {
                $processed++;
                $wasReceivedToday = collect($schoolRecords)->contains(fn (array $record): bool => $record['date_received'] !== ''
                    && $record['received_by'] !== ''
                    && $this->dateInApplicationTimezone($record['date_received']) === $today,
                );
                $processedToday += (int) $wasReceivedToday;
            }
        }

        return [
            'total' => $total,
            'processed' => $processed,
            'remaining' => max(0, $total - $processed),
            'processed_today' => $processedToday,
            'source_error' => null,
        ];
    }

    /** @return array{total: null, processed: null, remaining: null, processed_today: null, source_error: string} */
    private function unavailableSummary(string $message): array
    {
        return [
            'total' => null,
            'processed' => null,
            'remaining' => null,
            'processed_today' => null,
            'source_error' => $message,
        ];
    }

    private function dateInApplicationTimezone(string $value): ?string
    {
        $timezone = config('app.timezone');
        $formats = match (true) {
            preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) === 1 => ['!Y-m-d'],
            preg_match('/^\d{1,2}\/\d{1,2}\/\d{4}$/', $value) === 1 => ['!m/d/Y', '!d/m/Y'],
            preg_match('/^\d{1,2}[-\/]\p{L}{3}[-\/]\d{4}$/u', $value) === 1 => ['!d-M-Y', '!d/M/Y'],
            default => [],
        };

        foreach ($formats as $format) {
            try {
                $date = CarbonImmutable::createFromFormat($format, $value, $timezone);
                if ($date !== false) {
                    return $date->setTimezone($timezone)->toDateString();
                }
            } catch (\Throwable) {
                continue;
            }
        }

        try {
            return CarbonImmutable::parse($value, $timezone)
                ->setTimezone($timezone)
                ->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }

    private function lotNumber(string $lotName): ?string
    {
        if (preg_match('/\b(?:lot\s*)?(2|9|11)\b/i', trim($lotName), $matches) === 1
            && in_array($matches[1], self::LOTS, true)) {
            return $matches[1];
        }

        return null;
    }

    private function normalizeSchoolId(string $schoolId): string
    {
        return mb_strtolower(trim($schoolId));
    }
}
