<?php

namespace App\Jobs;

use App\Services\DeliveryAuditTrailService;
use App\Services\LogisticsDrResolver;
use App\Services\LogisticsDrTrackingService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class ProcessLogisticsWebhookEvent implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 75;

    /** @var array<int, int> */
    public array $backoff = [10, 60];

    public function __construct(public int $eventRowId) {}

    public function handle(LogisticsDrResolver $drResolver, LogisticsDrTrackingService $trackingService): void
    {
        $event = DB::transaction(function (): ?object {
            $event = DB::table('logistics_webhook_events')
                ->where('id', $this->eventRowId)
                ->lockForUpdate()
                ->first();

            $staleProcessing = $event !== null
                && $event->processing_status === 'processing'
                && now()->subSeconds(75)->greaterThan(Carbon::parse($event->updated_at));

            if ($event === null || $event->processing_status === 'processed'
                || ($event->processing_status !== 'received' && ! $staleProcessing)
            ) {
                return null;
            }

            DB::table('logistics_webhook_events')
                ->where('id', $this->eventRowId)
                ->update([
                    'processing_status' => 'processing',
                    'processing_attempts' => DB::raw('processing_attempts + 1'),
                    'error_details' => null,
                    'updated_at' => now(),
                ]);

            app(DeliveryAuditTrailService::class)->recordWebhookProcessingAttempt($this->eventRowId);

            return $event;
        }, 3);

        if ($event === null) {
            return;
        }

        try {
            DB::transaction(function () use ($event, $drResolver, $trackingService): void {
                $event = DB::table('logistics_webhook_events')
                    ->where('id', $this->eventRowId)
                    ->lockForUpdate()
                    ->first();

                if ($event === null || $event->processing_status !== 'processing') {
                    return;
                }

                $payload = json_decode($event->payload, true, 512, JSON_THROW_ON_ERROR);

                if (! is_array($payload)
                    || ($payload['event_id'] ?? null) !== $event->external_event_id
                    || ($payload['event_type'] ?? null) !== $event->event_type
                    || ($payload['dr_reference'] ?? null) !== $event->dr_reference
                    || ($payload['sequence'] ?? null) !== (int) $event->event_sequence
                ) {
                    throw new RuntimeException('Stored event validation failed.');
                }

                $resolution = $drResolver->resolve($event->integration_id, $event->dr_reference);
                $reconciliationStatus = $resolution['status'];
                $details = $resolution['reason'];

                if ($resolution['status'] === 'resolved') {
                    $deliveryIds = $drResolver->lockReceiptRows(
                        $event->dr_reference,
                        (int) $resolution['project_id'],
                        (string) $resolution['school_id']
                    );

                    if ($deliveryIds === []) {
                        $resolution['status'] = 'unknown';
                        $resolution['project_id'] = null;
                        $resolution['school_id'] = null;
                        $reconciliationStatus = 'unknown';
                        $details = 'The resolved receipt scope no longer contains delivery rows.';
                    } else {
                        $latestEvent = $this->latestAcceptedEvent(
                            $event,
                            (int) $resolution['project_id'],
                            (string) $resolution['school_id']
                        );

                        if ($latestEvent !== null) {
                            $incomingSequence = (int) $event->event_sequence;
                            $latestSequence = (int) $latestEvent->event_sequence;

                            if ($incomingSequence === $latestSequence) {
                                $reconciliationStatus = 'duplicate_sequence';
                                $details = 'Another event in this receipt scope already used this sequence.';
                            } elseif ($incomingSequence < $latestSequence) {
                                $reconciliationStatus = 'out_of_order';
                                $details = 'The event sequence is lower than the latest accepted sequence.';
                            } elseif (Carbon::parse($event->event_occurred_at)->lessThanOrEqualTo(Carbon::parse($latestEvent->event_occurred_at))) {
                                $reconciliationStatus = 'stale';
                                $details = 'The event occurrence time is not newer than the latest accepted event.';
                            } else {
                                $reconciliationStatus = 'accepted';
                                $details = 'Resolved within the authenticated receipt scope; sequence gaps are allowed.';
                            }
                        } else {
                            $reconciliationStatus = 'accepted';
                            $details = 'Resolved within the authenticated receipt scope; this is the first accepted event.';
                        }

                        if ($reconciliationStatus === 'accepted') {
                            $trackingResult = $trackingService->synchronize(
                                $event,
                                (int) $resolution['project_id'],
                                (string) $resolution['school_id']
                            );
                            $reconciliationStatus = $trackingResult['status'];
                            $details = $trackingResult['details'];
                        }
                    }
                }

                DB::table('logistics_webhook_events')
                    ->where('id', $this->eventRowId)
                    ->where('processing_status', 'processing')
                    ->update([
                        'processing_status' => 'processed',
                        'resolved_project_id' => $resolution['project_id'],
                        'resolved_school_id' => $resolution['school_id'],
                        'resolution_status' => $resolution['status'],
                        'reconciliation_status' => $reconciliationStatus,
                        'reconciliation_details' => $details,
                        'error_details' => null,
                        'processed_at' => now(),
                        'updated_at' => now(),
                    ]);
                app(DeliveryAuditTrailService::class)->recordWebhookEventByRowId($this->eventRowId);
            }, 3);
        } catch (Throwable $exception) {
            DB::table('logistics_webhook_events')
                ->where('id', $this->eventRowId)
                ->where('processing_status', 'processing')
                ->update([
                    'processing_status' => 'received',
                    'error_details' => $exception::class,
                    'updated_at' => now(),
                ]);

            app(DeliveryAuditTrailService::class)->recordWebhookEventByRowId($this->eventRowId);

            Log::warning('Logistics webhook event reconciliation failed.', [
                'event_row_id' => $this->eventRowId,
                'exception' => $exception::class,
            ]);

            throw $exception;
        }
    }

    private function latestAcceptedEvent(object $event, int $projectId, string $schoolId): ?object
    {
        $query = DB::table('logistics_webhook_events')
            ->where('integration_id', $event->integration_id)
            ->where('resolved_project_id', $projectId)
            ->where('resolved_school_id', $schoolId)
            ->where('reconciliation_status', 'accepted')
            ->where('processing_status', 'processed');

        if (DB::connection()->getDriverName() === 'mysql') {
            $query->whereRaw('BINARY dr_reference = BINARY ?', [$event->dr_reference]);
        } else {
            $query->whereRaw('dr_reference = ? COLLATE BINARY', [$event->dr_reference]);
        }

        return $query->orderByDesc('event_sequence')
            ->orderByDesc('event_occurred_at')
            ->lockForUpdate()
            ->first();
    }

    public function failed(?Throwable $exception): void
    {
        DB::table('logistics_webhook_events')
            ->where('id', $this->eventRowId)
            ->whereIn('processing_status', ['received', 'processing'])
            ->update([
                'processing_status' => 'failed',
                'error_details' => $exception === null ? null : $exception::class,
                'updated_at' => now(),
            ]);

        app(DeliveryAuditTrailService::class)->recordWebhookEventByRowId($this->eventRowId);

        Log::warning('Logistics webhook event exhausted its queue retries.', [
            'event_row_id' => $this->eventRowId,
            'exception' => $exception === null ? null : $exception::class,
        ]);
    }
}
