<?php

namespace App\Console\Commands;

use App\Services\LogisticsDeliveredProjection;
use App\Services\LogisticsDeliveryCompletionService;
use Illuminate\Console\Command;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\QueryException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ReconcileMmcDelivery extends Command
{
    protected $signature = 'logistics:reconcile-mmc-delivery {integration?} {event?} {project?} {school?} {dr?}
        {--all : Reconcile carrier-delivered DRs for both Airspeed integrations}
        {--through-event= : Highest webhook row ID to include in batch mode}
        {--apply : Write the reported package statuses and delivery history}';

    protected $description = 'Reconcile MMC delivery status from a confirmed Logistics Delivered webhook';

    public function handle(LogisticsDeliveryCompletionService $completion): int
    {
        $apply = (bool) $this->option('apply');

        $arguments = ['integration', 'event', 'project', 'school', 'dr'];
        $provided = array_filter(array_map(fn (string $key): ?string => $this->argument($key), $arguments), fn (?string $value): bool => $value !== null);

        if ($this->option('all') && $provided === []) {
            return $this->reconcileBatch($completion, $apply);
        }

        if (count($provided) !== count($arguments) || $this->option('all') || $this->option('through-event') !== null) {
            $this->error('Provide the exact event scope or use --all for the Airspeed batch.');

            return self::FAILURE;
        }

        try {
            $report = DB::transaction(fn (): array => $this->reconcile($completion, $apply, [
                'integration_id' => $this->argument('integration'),
                'external_event_id' => $this->argument('event'),
                'resolved_project_id' => $this->argument('project'),
                'resolved_school_id' => $this->argument('school'),
                'dr_reference' => $this->argument('dr'),
            ]), 3);
        } catch (RuntimeException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->line(json_encode($report, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT));

        return self::SUCCESS;
    }

    private function reconcileBatch(LogisticsDeliveryCompletionService $completion, bool $apply): int
    {
        $events = DB::table('logistics_webhook_events as event')
            ->join('logistics_dr_tracking as tracking', 'tracking.latest_webhook_event_row_id', '=', 'event.id')
            ->whereIn('event.integration_id', LogisticsDeliveredProjection::INTEGRATIONS)
            ->where('event.event_type', 'delivery.delivered')
            ->where('event.processing_status', 'processed')
            ->where('event.resolution_status', 'resolved')
            ->where('tracking.logistics_status', 'delivered')
            ->whereColumn('tracking.project_id', 'event.resolved_project_id')
            ->whereColumn('tracking.integration_id', 'event.integration_id')
            ->whereColumn('tracking.school_id', 'event.resolved_school_id')
            ->whereColumn('tracking.dr_reference', 'event.dr_reference');

        $cutoff = $this->option('through-event');
        if (($cutoff !== null && (! ctype_digit($cutoff) || (int) $cutoff < 1)) || ($apply && $cutoff === null)) {
            $this->error('Provide a positive --through-event when applying a reviewed batch.');

            return self::FAILURE;
        }

        $cutoff ??= (int) (clone $events)->max('event.id');
        $summary = ['mode' => $apply ? 'apply' : 'dry-run', 'through_event' => (int) $cutoff, 'scopes' => 0, 'changes' => 0, 'delivery_updates' => 0, 'package_inserts' => 0, 'skipped' => 0, 'package_previous_statuses' => []];
        $seen = [];
        foreach ($events->where('event.id', '<=', $cutoff)->orderByDesc('event.event_occurred_at')->orderByDesc('event.id')
            ->get(['event.integration_id', 'event.external_event_id', 'event.resolved_project_id', 'event.resolved_school_id', 'event.dr_reference']) as $event) {
            $scope = array_map(strval(...), (array) $event);
            $key = json_encode([$event->resolved_project_id, $event->resolved_school_id, $event->dr_reference], JSON_THROW_ON_ERROR);
            if (isset($seen[$key])) {
                continue;
            }

            $seen[$key] = true;
            $summary['scopes']++;

            try {
                $report = DB::transaction(fn (): array => $this->reconcile($completion, $apply, $scope), 3);
            } catch (RuntimeException $exception) {
                if ($exception instanceof QueryException) {
                    throw $exception;
                }

                $summary['skipped']++;
                $this->line(json_encode(['scope' => $scope, 'skipped' => $exception->getMessage()], JSON_THROW_ON_ERROR));

                continue;
            }

            if ($report['delivery_updates'] === [] && $report['package_inserts'] === []) {
                continue;
            }

            $summary['changes']++;
            $summary['delivery_updates'] += count($report['delivery_updates']);
            $summary['package_inserts'] += count($report['package_inserts']);
            foreach ($report['package_inserts'] as $package) {
                $status = $package['previous_status'] ?? '(missing)';
                $summary['package_previous_statuses'][$status] = ($summary['package_previous_statuses'][$status] ?? 0) + 1;
            }

            $this->line(json_encode($report, JSON_THROW_ON_ERROR));
        }

        $this->line(json_encode($summary, JSON_THROW_ON_ERROR));

        return self::SUCCESS;
    }

    /**
     * @param  array<string, string>  $scope
     * @return array<string, mixed>
     */
    private function reconcile(LogisticsDeliveryCompletionService $completion, bool $apply, array $scope): array
    {
        $event = DB::table('logistics_webhook_events')->where($scope)
            ->where(['event_type' => 'delivery.delivered', 'processing_status' => 'processed', 'resolution_status' => 'resolved'])
            ->when($apply, fn (Builder $query) => $query->lockForUpdate())
            ->first();

        if ($event === null || $event->event_occurred_at === null
            || array_map(fn (string $column): string => (string) $event->{$column}, array_keys($scope)) !== array_values($scope)
        ) {
            throw new RuntimeException('The resolved Delivered event does not match the supplied scope.');
        }

        $deliveries = DB::table('deliveries')->where([
            'project_id' => $event->resolved_project_id,
            'school_id' => $event->resolved_school_id,
            'dr_no' => $event->dr_reference,
        ])->orderBy('delivery_id')
            ->when($apply, fn (Builder $query) => $query->lockForUpdate())
            ->get()
            ->filter(fn (object $row): bool => $row->school_id === $event->resolved_school_id && $row->dr_no === $event->dr_reference);

        if ($deliveries->isEmpty()) {
            throw new RuntimeException('There are no deliveries in the exact resolved scope.');
        }

        $report = [
            'mode' => $apply ? 'apply' : 'dry-run',
            'scope' => $scope,
            'webhook_row_id' => $event->id,
            'webhook_reconciliation' => $event->reconciliation_status,
            'delivered_at' => $event->event_occurred_at,
            'delivery_updates' => [],
            'package_inserts' => [],
        ];
        $date = Carbon::parse($event->event_occurred_at)->toDateString();

        foreach ($deliveries as $delivery) {
            $allocation = $delivery->keystage_id === null ? 'lot_id' : 'keystage_id';
            $packageIds = DB::table('package')->where($allocation, $delivery->{$allocation})
                ->whereNotNull($allocation)->orderBy('package_id')->pluck('package_id');

            if ($packageIds->isEmpty() || in_array(strtolower((string) $delivery->status), ['cancelled', 'canceled', 'void', 'voided', 'deleted', 'rejected', 'returned'], true)) {
                throw new RuntimeException("Delivery {$delivery->delivery_id} is inactive or has no package allocation.");
            }

            $statuses = DB::table('package_status')->where('delivery_id', $delivery->delivery_id)
                ->whereIn('package_id', $packageIds)
                ->orderBy('package_status_id')
                ->when($apply, fn (Builder $query) => $query->lockForUpdate())
                ->pluck('status', 'package_id');

            foreach ($packageIds as $packageId) {
                $previousStatus = $statuses->get($packageId);

                if (in_array(strtolower((string) $previousStatus), ['delivered', 'accepted'], true)) {
                    continue;
                }

                $report['package_inserts'][] = [
                    'delivery_id' => $delivery->delivery_id,
                    'package_id' => $packageId,
                    'previous_status' => $previousStatus,
                    'status' => 'delivered',
                ];

                if ($apply) {
                    $completion->recordDeliveredPackage($event, (int) $delivery->delivery_id, (int) $packageId, $previousStatus, manual: true);
                }
            }

            if (! in_array(strtolower((string) $delivery->status), ['delivered', 'accepted'], true)) {
                $report['delivery_updates'][] = [
                    'delivery_id' => $delivery->delivery_id,
                    'previous_status' => $delivery->status,
                    'status' => 'delivered',
                    'delivered_date' => $date,
                ];

                if ($apply) {
                    DB::table('deliveries')->where('delivery_id', $delivery->delivery_id)
                        ->update(['status' => 'delivered', 'delivered_date' => $date]);
                }
            }
        }

        return $report;
    }
}
