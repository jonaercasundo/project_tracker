<?php

namespace App\Services;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class ProjectDrProgressReportService
{
    private const INVALID_DELIVERY_STATUSES = ['cancelled', 'canceled', 'void', 'voided', 'deleted', 'rejected'];

    /** @return array<string, mixed> */
    public function summarize(int $projectId, CarbonImmutable $cutoffDate): array
    {
        $today = CarbonImmutable::today(config('app.timezone'));
        $latestActualDate = $cutoffDate->min($today);

        $project = DB::table('projects')->where('project_id', $projectId)->first(['start_date']);
        $missingCreationDateRows = DB::table('deliveries')
            ->where('project_id', $projectId)
            ->whereNotNull('dr_no')
            ->whereRaw("TRIM(dr_no) <> ''")
            ->whereNull('created_at')
            ->get(['dr_no', 'status']);
        $deliveryRows = DB::table('deliveries')
            ->where('project_id', $projectId)
            ->whereNotNull('dr_no')
            ->whereRaw("TRIM(dr_no) <> ''")
            ->whereNotNull('created_at')
            ->whereDate('created_at', '<=', $cutoffDate->toDateString())
            ->orderBy('created_at')
            ->get([
                'delivery_id', 'dr_no', 'status', 'created_at', 'delivered_date', 'accepted_date',
                'keystage_id', 'lot_id',
            ]);

        $receipts = [];
        foreach ($deliveryRows as $row) {
            $key = (string) $row->dr_no;
            $receipts[$key] ??= ['created_at' => $row->created_at, 'rows' => [], 'invalid' => false];
            $receipts[$key]['created_at'] = min($receipts[$key]['created_at'], $row->created_at);
            $receipts[$key]['rows'][] = $row;
            if (in_array(mb_strtolower(trim((string) $row->status)), self::INVALID_DELIVERY_STATUSES, true)) {
                $receipts[$key]['invalid'] = true;
            }
        }

        $receipts = array_filter($receipts, fn (array $receipt): bool => ! $receipt['invalid']);
        $missingCreationDateReceipts = [];
        foreach ($missingCreationDateRows as $row) {
            $key = (string) $row->dr_no;
            $missingCreationDateReceipts[$key] ??= false;
            if (! in_array(mb_strtolower(trim((string) $row->status)), self::INVALID_DELIVERY_STATUSES, true)) {
                $missingCreationDateReceipts[$key] = true;
            }
        }
        $missingCreationDateReceipts = array_filter($missingCreationDateReceipts);
        $missingCreationDateReceipts = array_diff_key($missingCreationDateReceipts, $receipts);
        $deliveryIds = collect($receipts)->flatMap(fn (array $receipt): array => array_map(
            fn (object $row): int => (int) $row->delivery_id,
            $receipt['rows'],
        ))->unique()->values();

        $allocationsByDelivery = $this->expectedAllocations($deliveryRows, $deliveryIds->all());
        $completionData = $this->completedAllocationDates($deliveryIds->all());
        $completedAtByAllocation = $completionData['dates'];

        $createdByDate = [];
        $completedByDate = [];
        $unavailableReceipts = [];
        $scopedReceipts = [];
        foreach ($receipts as $drNumber => $receipt) {
            $createdAt = CarbonImmutable::parse($receipt['created_at'], config('app.timezone'));
            $createdDate = $createdAt->toDateString();
            $createdByDate[$createdDate] = ($createdByDate[$createdDate] ?? 0) + 1;
            $scopedReceipts[$drNumber] = $receipt;

            $completionDates = [];
            foreach ($receipt['rows'] as $row) {
                $rowAllocations = $allocationsByDelivery[(int) $row->delivery_id] ?? [];
                if ($rowAllocations !== []) {
                    foreach ($rowAllocations as $packageId) {
                        $allocationKey = (int) $row->delivery_id.':'.$packageId;
                        $completionDates[] = $completedAtByAllocation[$allocationKey] ?? null;
                        if (! isset($completedAtByAllocation[$allocationKey]) && isset($completionData['undated_completed'][$allocationKey])) {
                            $unavailableReceipts[$drNumber] = true;
                        }
                    }
                } else {
                    $rowCompletion = $this->rowCompletionDate($row);
                    $completionDates[] = $rowCompletion;
                    if ($rowCompletion === null && in_array(mb_strtolower(trim((string) $row->status)), ['delivered', 'accepted'], true)) {
                        $unavailableReceipts[$drNumber] = true;
                    }
                }
            }

            if ($completionDates === [] || in_array(null, $completionDates, true)) {
                continue;
            }

            $completedAt = max($completionDates);
            if (CarbonImmutable::parse($completedAt, config('app.timezone'))->lessThanOrEqualTo($latestActualDate->endOfDay())) {
                $completedDate = substr($completedAt, 0, 10);
                $completedByDate[$completedDate] = ($completedByDate[$completedDate] ?? 0) + 1;
            }
        }

        $earliestCreatedDate = collect($scopedReceipts)->min(fn (array $receipt): string => substr($receipt['created_at'], 0, 10));
        $projectStartDate = $project?->start_date ? CarbonImmutable::parse($project->start_date)->toDateString() : null;
        $startDate = $projectStartDate !== null && $projectStartDate <= ($earliestCreatedDate ?? $today->toDateString())
            ? $projectStartDate
            : ($earliestCreatedDate ?? $today->toDateString());
        $todayDate = $today->toDateString();
        $actualThroughDate = $latestActualDate->toDateString();
        $reportStart = CarbonImmutable::parse($startDate, config('app.timezone'));
        $timelineSpanDays = $reportStart->lessThan($cutoffDate) ? (int) $reportStart->diffInDays($cutoffDate) : 0;
        $midpointDate = $timelineSpanDays > 0
            ? $reportStart->addDays(intdiv($timelineSpanDays, 2))->toDateString()
            : $cutoffDate->toDateString();
        $elapsedPercentage = $cutoffDate->lessThanOrEqualTo($today)
            ? 100.0
            : ($today->lessThanOrEqualTo($reportStart) || $timelineSpanDays === 0
                ? 0.0
                : min(100.0, round(($reportStart->diffInDays($today) / $timelineSpanDays) * 100, 1)));
        $recordedThroughToday = array_sum(array_filter(
            $createdByDate,
            fn (int $count, string $recordedDate): bool => $recordedDate <= $todayDate,
            ARRAY_FILTER_USE_BOTH,
        ));

        $total = count($scopedReceipts);
        $completed = array_sum(array_filter($completedByDate, fn (int $count, string $date): bool => $date <= $latestActualDate->toDateString(), ARRAY_FILTER_USE_BOTH));
        $remaining = max(0, $total - $completed);
        $daysRemaining = $cutoffDate->greaterThan($today) ? (int) $today->diffInDays($cutoffDate) : 0;
        $requiredAverage = $daysRemaining > 0 && $remaining > 0 ? $remaining / $daysRemaining : null;
        $requiredPerDay = $requiredAverage === null ? null : round($requiredAverage, 2);
        $paceStart = $latestActualDate->subDays(6)->toDateString();
        $recentCompleted = array_sum(array_filter($completedByDate, fn (int $count, string $date): bool => $date >= $paceStart && $date <= $latestActualDate->toDateString(), ARRAY_FILTER_USE_BOTH));
        $recentAverage = $recentCompleted / 7;
        $recentPace = round($recentAverage, 2);
        $forecastDaysToComplete = $recentAverage > 0 ? (int) ceil($remaining / $recentAverage) : null;
        $forecastFinishDate = $forecastDaysToComplete === null
            ? null
            : $latestActualDate->addDays($forecastDaysToComplete)->toDateString();
        $limitations = [];
        if ($missingCreationDateReceipts !== []) {
            $limitations[] = count($missingCreationDateReceipts).' DR(s) have no deliveries.created_at value and cannot be assigned to the cutoff population.';
        }
        if ($unavailableReceipts !== []) {
            $limitations[] = count($unavailableReceipts).' DR(s) have a delivered/accepted status without a recorded completion date. They remain in scope but are not counted as completed.';
        }

        if ($cutoffDate->lessThan($today)) {
            $paceStatus = 'Cutoff passed';
        } elseif ($cutoffDate->equalTo($today)) {
            $paceStatus = $remaining === 0 ? 'Target met' : 'Cutoff is today';
        } elseif ($remaining === 0) {
            $paceStatus = 'Target met';
        } elseif ($requiredAverage !== null && $recentAverage >= $requiredAverage) {
            $paceStatus = 'On track';
        } else {
            $paceStatus = 'At this pace, cutoff will be missed';
        }

        $cutoffLabel = strtoupper($cutoffDate->format('M d'));
        if ($cutoffDate->lessThan($today)) {
            $paceAssessment = 'CUTOFF PASSED';
        } elseif ($cutoffDate->equalTo($today)) {
            $paceAssessment = $remaining === 0 ? 'TARGET MET' : 'CUTOFF TODAY';
        } elseif ($remaining === 0) {
            $paceAssessment = 'TARGET MET';
        } elseif ($forecastFinishDate === null) {
            $paceAssessment = 'WILL MISS '.$cutoffLabel.' BY A WIDE MARGIN';
        } elseif ($forecastFinishDate <= $cutoffDate->toDateString()) {
            $paceAssessment = 'ON TRACK TO MEET '.$cutoffLabel;
        } else {
            $missDays = (int) $cutoffDate->diffInDays(CarbonImmutable::parse($forecastFinishDate, config('app.timezone')));
            $wideMarginThreshold = max(7, (int) ceil($daysRemaining * 0.25));
            $paceAssessment = 'WILL MISS '.$cutoffLabel.($missDays >= $wideMarginThreshold
                ? ' BY A WIDE MARGIN'
                : ' BY '.$missDays.' DAYS');
        }

        return [
            'cutoff_date' => $cutoffDate->toDateString(),
            'today' => $today->toDateString(),
            'actual_data_through' => $actualThroughDate,
            'start_date' => $startDate,
            'midpoint_date' => $midpointDate,
            'elapsed_percentage' => $elapsedPercentage,
            'days_remaining' => $daysRemaining,
            'total_drs' => $total,
            'recorded_through_today' => $recordedThroughToday,
            'completed' => $completed,
            'percentage' => $total > 0 ? round(($completed / $total) * 100, 1) : 0,
            'remaining' => $remaining,
            'required_per_day' => $requiredPerDay,
            'recent_pace' => $recentPace,
            'forecast_finish_date' => $forecastFinishDate,
            'pace_assessment' => $paceAssessment,
            'pace_status' => $paceStatus,
            'data_limitation' => $limitations === [] ? null : implode(' ', $limitations),
            'creation_definition' => 'One distinct, nonblank project DR number with deliveries.created_at on or before the cutoff. Blank DRs and receipts containing cancelled, void, deleted or rejected delivery rows are excluded.',
            'completion_definition' => 'A DR is complete when all of its expected packages have dated delivered/accepted history; when no package allocations are expected, every delivery row must have a delivered_date or accepted_date.',
        ];
    }

    /** @param iterable<object> $rows @param list<int> $deliveryIds @return array<int, list<int>> */
    private function expectedAllocations(iterable $rows, array $deliveryIds): array
    {
        if ($deliveryIds === []) {
            return [];
        }

        $stageIds = [];
        $lotIds = [];
        foreach ($rows as $row) {
            if (! in_array((int) $row->delivery_id, $deliveryIds, true)) {
                continue;
            }
            if ($row->keystage_id !== null) {
                $stageIds[] = (int) $row->keystage_id;
            } elseif ($row->lot_id !== null) {
                $lotIds[] = (int) $row->lot_id;
            }
        }

        $packagesByStage = $stageIds === [] ? collect() : DB::table('package')->whereIn('keystage_id', array_unique($stageIds))->get(['keystage_id', 'package_id'])->groupBy('keystage_id');
        $packagesByLot = $lotIds === [] ? collect() : DB::table('package')->whereIn('lot_id', array_unique($lotIds))->get(['lot_id', 'package_id'])->groupBy('lot_id');
        $allocations = [];
        foreach ($rows as $row) {
            if (! in_array((int) $row->delivery_id, $deliveryIds, true)) {
                continue;
            }
            $packages = $row->keystage_id !== null
                ? $packagesByStage->get((string) $row->keystage_id, collect())
                : ($row->lot_id !== null ? $packagesByLot->get((string) $row->lot_id, collect()) : collect());
            $allocations[(int) $row->delivery_id] = $packages->pluck('package_id')->map(fn (mixed $id): int => (int) $id)->all();
        }

        return $allocations;
    }

    /** @param list<int> $deliveryIds @return array{dates: array<string, string>, undated_completed: array<string, true>} */
    private function completedAllocationDates(array $deliveryIds): array
    {
        if ($deliveryIds === []) {
            return ['dates' => [], 'undated_completed' => []];
        }

        $dates = [];
        $histories = DB::table('package_status as ps')
            ->join('delivery_history as h', 'h.package_status_id', '=', 'ps.package_status_id')
            ->whereIn('ps.delivery_id', $deliveryIds)
            ->whereIn(DB::raw('LOWER(TRIM(h.status))'), ['delivered', 'accepted'])
            ->whereNotNull('h.created_at')
            ->orderBy('h.created_at')
            ->get(['ps.delivery_id', 'ps.package_id', 'h.created_at']);
        foreach ($histories as $history) {
            $key = (int) $history->delivery_id.':'.(int) $history->package_id;
            $dates[$key] ??= $history->created_at;
        }

        $datedStatuses = DB::table('package_status')
            ->whereIn('delivery_id', $deliveryIds)
            ->whereIn(DB::raw('LOWER(TRIM(status))'), ['delivered', 'accepted'])
            ->whereNotNull('delivered_at')
            ->orderBy('delivered_at')
            ->get(['delivery_id', 'package_id', 'delivered_at']);
        foreach ($datedStatuses as $status) {
            $key = (int) $status->delivery_id.':'.(int) $status->package_id;
            $dates[$key] ??= $status->delivered_at;
        }

        $latestStatuses = DB::table('package_status as current_status')
            ->whereIn('current_status.delivery_id', $deliveryIds)
            ->whereRaw('current_status.package_status_id = (SELECT MAX(latest.package_status_id) FROM package_status as latest WHERE latest.delivery_id = current_status.delivery_id AND latest.package_id = current_status.package_id)')
            ->whereIn(DB::raw('LOWER(TRIM(current_status.status))'), ['delivered', 'accepted'])
            ->get(['current_status.delivery_id', 'current_status.package_id']);
        $undated = [];
        foreach ($latestStatuses as $status) {
            $key = (int) $status->delivery_id.':'.(int) $status->package_id;
            if (! isset($dates[$key])) {
                $undated[$key] = true;
            }
        }

        return ['dates' => $dates, 'undated_completed' => $undated];
    }

    private function rowCompletionDate(object $row): ?string
    {
        $dates = array_values(array_filter([$row->delivered_date, $row->accepted_date]));

        return $dates === [] ? null : min($dates);
    }
}
