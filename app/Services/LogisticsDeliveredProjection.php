<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use RuntimeException;

class LogisticsDeliveredProjection
{
    public const INTEGRATIONS = ['Airspeed', 'Airspeed_logistic'];

    public function __construct(private DeliveryAuditTrailService $auditTrail) {}

    /** @return array<string, mixed>|null */
    public function reconcile(int $eventId, string $actor, bool $apply): ?array
    {
        return DB::transaction(function () use ($eventId, $actor, $apply): ?array {
            $event = DB::table('logistics_webhook_events')->where('id', $eventId)
                ->when($apply, fn ($query) => $query->lockForUpdate())
                ->first();

            if ($event === null || $event->event_type !== 'delivery.delivered'
                || $event->processing_status !== 'processed' || $event->resolution_status !== 'resolved'
                || ! in_array($event->integration_id, self::INTEGRATIONS, true)
                || $event->resolved_project_id === null || $event->resolved_school_id === null
                || $event->resolved_school_id === '' || $event->event_occurred_at === null
                || $event->event_sequence === null
            ) {
                return null;
            }

            $scope = [
                'integration_id' => $event->integration_id,
                'project_id' => $event->resolved_project_id,
                'school_scope_hash' => hash('sha256', $event->resolved_school_id),
                'dr_reference_hash' => hash('sha256', $event->dr_reference),
            ];

            $tracking = DB::table('logistics_dr_tracking')->where($scope)
                ->when($apply, fn ($query) => $query->lockForUpdate())
                ->first();

            if ($tracking !== null && ($tracking->integration_id !== $event->integration_id
                || $tracking->school_id !== $event->resolved_school_id || $tracking->dr_reference !== $event->dr_reference)
            ) {
                throw new RuntimeException("Tracking scope mismatch for webhook row {$eventId}.");
            }

            if ($tracking?->logistics_status === 'delivered'
                && [$tracking->event_occurred_at, (int) $tracking->latest_webhook_event_row_id]
                    >= [$event->event_occurred_at, $eventId]
            ) {
                return null;
            }

            $timestamp = now()->toDateTimeString();
            $values = [
                'logistics_status' => 'delivered',
                'latest_event_id' => $event->external_event_id,
                'latest_webhook_event_row_id' => $eventId,
                'event_occurred_at' => $event->event_occurred_at,
                'latest_sequence' => $event->event_sequence,
                'last_synchronized_at' => $timestamp,
                'updated_at' => $timestamp,
                'reconciliation_status' => 'projection_reconciled',
            ];

            $report = [
                'action' => $tracking === null ? 'insert' : 'update',
                'scope' => $scope + ['school_id' => $event->resolved_school_id, 'dr_reference' => $event->dr_reference],
                'tracking_id' => $tracking?->id,
                'webhook_reconciliation_status' => $event->reconciliation_status,
                'before' => $tracking === null ? null : array_intersect_key((array) $tracking, $values),
                'after' => $values + ($tracking === null ? ['created_at' => $timestamp] : []),
            ];

            if ($apply) {
                if ($tracking === null) {
                    $report['tracking_id'] = DB::table('logistics_dr_tracking')->insertGetId($report['scope'] + $report['after']);
                } else {
                    DB::table('logistics_dr_tracking')->where('id', $tracking->id)->update($values);
                }

                $this->auditTrail->record('logistics:delivered-projection:'.$eventId, [
                    'project_id' => $event->resolved_project_id,
                    'school_id' => $event->resolved_school_id,
                    'dr_no' => $event->dr_reference,
                    'integration_id' => $event->integration_id,
                    'event_type' => 'logistics_projection_reconciled',
                    'previous_status' => $tracking?->logistics_status,
                    'new_status' => 'delivered',
                    'source_module' => 'Logistics Tracking',
                    'actor_name' => $actor,
                    'related_type' => 'webhook_event',
                    'related_id' => $event->external_event_id,
                    'metadata' => $report,
                ]);
            }

            return $report;
        }, 3);
    }
}
