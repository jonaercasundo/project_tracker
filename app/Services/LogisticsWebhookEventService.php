<?php

namespace App\Services;

use App\Jobs\ProcessLogisticsWebhookEvent;
use DateTimeImmutable;
use DateTimeZone;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class LogisticsWebhookEventService
{
    public function __construct(private Dispatcher $dispatcher) {}

    /**
     * @param  array<string, mixed>  $payload
     * @return array{event_id: string, result: string, queued: bool}
     */
    public function accept(array $payload, string $rawPayload, string $integrationId): array
    {
        $payloadHash = hash('sha256', $rawPayload);
        $occurredAt = (new DateTimeImmutable($payload['occurred_at']))
            ->setTimezone(new DateTimeZone('UTC'))
            ->format('Y-m-d H:i:s.u');

        $event = DB::transaction(function () use ($payload, $payloadHash, $integrationId, $occurredAt): object {
            DB::table('logistics_webhook_events')->insertOrIgnore([
                'integration_id' => $integrationId,
                'external_event_id' => $payload['event_id'],
                'event_type' => $payload['event_type'],
                'logistics_status' => substr($payload['event_type'], strlen('delivery.')),
                'dr_reference' => $payload['dr_reference'],
                'external_allocation_reference' => null,
                'payload' => json_encode($payload, JSON_THROW_ON_ERROR),
                'payload_hash' => $payloadHash,
                'event_occurred_at' => $occurredAt,
                'event_sequence' => (int) $payload['sequence'],
                'received_at' => now(),
                'processing_status' => 'received',
                'resolution_status' => 'pending',
                'reconciliation_status' => 'pending',
                'processing_attempts' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $event = DB::table('logistics_webhook_events')
                ->where('integration_id', $integrationId)
                ->where('external_event_id', $payload['event_id'])
                ->lockForUpdate()
                ->first();

            if (hash_equals($event->payload_hash, $payloadHash)) {
                app(DeliveryAuditTrailService::class)->recordWebhookEventByRowId((int) $event->id);
            }

            return $event;
        }, 3);

        if (! hash_equals($event->payload_hash, $payloadHash)) {
            return ['event_id' => $payload['event_id'], 'result' => 'conflict', 'queued' => false];
        }

        if ($event->processing_status === 'processed') {
            return ['event_id' => $payload['event_id'], 'result' => 'processed_duplicate', 'queued' => false];
        }

        if ($event->processing_status === 'failed') {
            return ['event_id' => $payload['event_id'], 'result' => 'failed', 'queued' => false];
        }

        if ($event->processing_status !== 'received' || $event->queued_at !== null) {
            return ['event_id' => $payload['event_id'], 'result' => 'duplicate', 'queued' => false];
        }

        try {
            $this->dispatchPendingEvent((int) $event->id);
        } catch (Throwable $exception) {
            Log::warning('Unable to queue a logistics webhook event.', [
                'integration_id' => $integrationId,
                'exception' => $exception::class,
            ]);

            return ['event_id' => $payload['event_id'], 'result' => 'queue_unavailable', 'queued' => false];
        }

        return ['event_id' => $payload['event_id'], 'result' => 'accepted', 'queued' => true];
    }

    /** @return array{queued: int, failed: int} */
    public function recoverUnqueued(int $limit = 100, bool $retryFailed = false): array
    {
        $limit = max(1, min($limit, 1000));

        if ($retryFailed) {
            DB::table('logistics_webhook_events')
                ->where('processing_status', 'failed')
                ->orderBy('id')
                ->limit($limit)
                ->update([
                    'processing_status' => 'received',
                    'processing_attempts' => 0,
                    'error_details' => null,
                    'processed_at' => null,
                    'queued_at' => null,
                    'updated_at' => now(),
                ]);
        }

        $eventIds = DB::table('logistics_webhook_events')
            ->where('processing_status', 'received')
            ->whereNull('queued_at')
            ->orderBy('id')
            ->limit($limit)
            ->pluck('id');

        $queued = 0;
        $failed = 0;

        foreach ($eventIds as $eventId) {
            try {
                if ($this->dispatchPendingEvent((int) $eventId)) {
                    $queued++;
                }
            } catch (Throwable $exception) {
                $failed++;
                Log::warning('Unable to recover a logistics webhook event.', [
                    'event_row_id' => (int) $eventId,
                    'exception' => $exception::class,
                ]);
            }
        }

        return ['queued' => $queued, 'failed' => $failed];
    }

    public function retryFailedEvent(int $eventId): bool
    {
        $eligible = DB::transaction(function () use ($eventId): bool {
            $event = DB::table('logistics_webhook_events')->where('id', $eventId)->lockForUpdate()->first();
            if ($event === null || $event->processing_status !== 'failed' || (int) $event->processing_attempts >= 10) {
                return false;
            }

            DB::table('logistics_webhook_events')->where('id', $eventId)->update([
                'processing_status' => 'received',
                'resolution_status' => 'pending',
                'reconciliation_status' => 'pending',
                'reconciliation_details' => null,
                'error_details' => null,
                'processed_at' => null,
                'queued_at' => null,
                'updated_at' => now(),
            ]);

            return true;
        }, 3);

        if (! $eligible) {
            return false;
        }

        try {
            $this->dispatchPendingEvent($eventId);
        } catch (Throwable $exception) {
            Log::warning('Unable to queue a manually retried logistics webhook event.', [
                'event_row_id' => $eventId,
                'exception' => $exception::class,
            ]);
        }

        return true;
    }

    private function dispatchPendingEvent(int $eventId): bool
    {
        $event = DB::table('logistics_webhook_events')
            ->where('id', $eventId)
            ->where('processing_status', 'received')
            ->whereNull('queued_at')
            ->first();

        if ($event === null) {
            return false;
        }

        $job = (new ProcessLogisticsWebhookEvent($eventId))
            ->onConnection('logistics_webhooks')
            ->onQueue('logistics-webhooks')
            ->afterCommit();

        $this->dispatcher->dispatch($job);

        DB::table('logistics_webhook_events')
            ->where('id', $eventId)
            ->where('processing_status', 'received')
            ->whereNull('queued_at')
            ->update(['queued_at' => now(), 'updated_at' => now()]);

        return true;
    }
}
