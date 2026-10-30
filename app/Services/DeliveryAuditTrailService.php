<?php

namespace App\Services;

use App\Models\DeliveryAuditEvent;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class DeliveryAuditTrailService
{
    public function recordInventoryHistoryById(int $historyId): void
    {
        if (! Schema::hasTable('delivery_audit_events')) {
            return;
        }

        $row = DB::table('inventory_history')->where('history_id', $historyId)->first();
        if ($row === null) {
            return;
        }

        $delivery = $this->resolveInventoryDelivery($row);
        $deliveryRecord = is_object($delivery) ? $delivery : null;
        $metadata = (array) $row;
        if ($delivery === null && ! empty($row->dr_no)) {
            $metadata['mapping_review'] = 'No unique exact delivery match found for this DR number.';
        } elseif ($delivery === false) {
            $metadata['mapping_review'] = 'Multiple exact delivery records match this inventory transaction.';
        }
        $packageCode = $this->packageCode($row->package_id ?? null);

        $this->record('legacy:inventory_history:'.$historyId, [
            'event_at' => $row->changed_at ?? $row->created_at,
            'project_id' => $row->project_id ?? $deliveryRecord?->project_id ?? $this->projectIdForItem($row->item_id ?? null),
            'lot_id' => $row->lot_id ?? $deliveryRecord?->lot_id,
            'school_id' => $deliveryRecord?->school_id,
            'school_name' => $deliveryRecord?->school_name,
            'package_code' => $packageCode,
            'dr_no' => $row->dr_no ?? null,
            'event_type' => $row->change_type ?? 'inventory_change',
            'quantity' => $row->quantity ?? (isset($row->old_qty, $row->new_qty) ? abs($row->new_qty - $row->old_qty) : null),
            'warehouse_id' => $row->warehouse_id ?? null,
            'warehouse_name' => $this->warehouseName($row->warehouse_id ?? null),
            'performed_by' => $row->changed_by_user_id ?? null,
            'actor_name' => $row->changed_by ?? 'System',
            'source_module' => str_contains((string) ($row->remarks ?? ''), 'Sync Billing') ? 'Billing Sync' : 'Inventory',
            'related_type' => 'inventory_history',
            'related_id' => (string) $historyId,
            'notes' => $row->remarks ?? null,
            'metadata' => $metadata,
        ]);
    }

    public function recordDeliveryHistoryById(int $historyId): void
    {
        if (! Schema::hasTable('delivery_audit_events')) {
            return;
        }

        $row = DB::table('delivery_history as h')
            ->join('package_status as ps', 'ps.package_status_id', '=', 'h.package_status_id')
            ->join('deliveries as d', 'd.delivery_id', '=', 'ps.delivery_id')
            ->leftJoin('school as s', 's.school_id', '=', 'd.school_id')
            ->when(Schema::hasTable('users'), fn ($query) => $query->leftJoin('users as u', 'u.user_id', '=', 'h.user_id')->addSelect('u.name as user_name'))
            ->where('h.id', $historyId)
            ->select('h.*', 'ps.package_id', 'd.project_id', 'd.lot_id', 'd.school_id', 's.school_name', 'd.dr_no')
            ->first();
        if ($row === null) {
            return;
        }

        $this->record('legacy:delivery_history:'.$historyId, [
            'event_at' => $row->created_at,
            'project_id' => $row->project_id,
            'lot_id' => $row->lot_id,
            'school_id' => $row->school_id,
            'school_name' => $row->school_name,
            'package_code' => $this->packageCode($row->package_id ?? null),
            'dr_no' => $row->dr_no,
            'event_type' => 'delivery_status',
            'previous_status' => $row->previous_status ?? null,
            'new_status' => $row->status,
            'performed_by' => $row->user_id,
            'actor_name' => ($row->user_name ?? null)
                ?? (str_contains((string) $row->remarks, 'Logistics') ? 'Logistics Integration' : (str_contains((string) $row->remarks, 'Sync Billing') ? 'Billing Sync' : 'System')),
            'source_module' => 'Delivery',
            'related_type' => 'delivery_history',
            'related_id' => (string) $historyId,
            'notes' => $row->remarks,
            'metadata' => (array) $row,
        ]);
    }

    public function recordBillingHistoryById(int $historyId): void
    {
        if (! Schema::hasTable('delivery_audit_events')) {
            return;
        }

        $row = DB::table('delivery_billing_histories as h')
            ->join('delivery_billing_records as b', 'b.id', '=', 'h.delivery_billing_record_id')
            ->join('deliveries as d', 'd.delivery_id', '=', 'b.delivery_id')
            ->leftJoin('school as s', 's.school_id', '=', 'd.school_id')
            ->where('h.id', $historyId)
            ->select('h.*', 'b.delivery_id', 'b.billing_batch_id', 'd.project_id', 'd.lot_id', 'd.school_id', 'd.dr_no', 's.school_name')
            ->first();
        if ($row === null) {
            return;
        }

        $this->record('legacy:billing_history:'.$historyId, [
            'event_at' => $row->created_at,
            'project_id' => $row->project_id,
            'lot_id' => $row->lot_id,
            'school_id' => $row->school_id,
            'school_name' => $row->school_name,
            'dr_no' => $row->dr_no,
            'event_type' => 'billing_'.$row->new_status,
            'previous_status' => $row->previous_status,
            'new_status' => $row->new_status,
            'performed_by' => $row->changed_by,
            'actor_name' => $row->changed_by_name,
            'source_module' => 'Billing',
            'related_type' => $row->billing_batch_id === null ? 'delivery_billing_record' : 'billing_batch',
            'related_id' => (string) ($row->billing_batch_id ?? $row->delivery_billing_record_id),
            'notes' => $row->remarks,
            'metadata' => (array) $row,
        ]);
    }

    /** @param array<string, mixed> $data */
    public function record(string $sourceKey, array $data): DeliveryAuditEvent
    {
        $attributes = array_merge([
            'event_uuid' => (string) Str::uuid(),
            'source_key' => $sourceKey,
            'event_at' => now(),
            'source_module' => 'Project Tracker',
        ], $data);

        if (isset($attributes['metadata']) && is_array($attributes['metadata'])) {
            $attributes['metadata'] = json_encode($attributes['metadata'], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
        }

        DB::table('delivery_audit_events')->insertOrIgnore($attributes + [
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return DeliveryAuditEvent::query()->where('source_key', $sourceKey)->firstOrFail();
    }

    public function recordWebhookEventByRowId(int $eventRowId): void
    {
        if (! Schema::hasTable('delivery_audit_events')) {
            return;
        }

        $row = DB::table('logistics_webhook_events')->where('id', $eventRowId)->first();
        if ($row === null) {
            return;
        }

        $sourceHash = hash('sha256', $row->integration_id.'|'.$row->external_event_id);
        $base = [
            'integration_id' => $row->integration_id,
            'project_id' => $row->resolved_project_id ?? null,
            'school_id' => $row->resolved_school_id ?? null,
            'dr_no' => $row->dr_reference,
            'actor_name' => 'Logistics Integration',
            'source_module' => 'Logistics Webhook',
            'related_type' => 'webhook_event',
            'related_id' => (string) $row->external_event_id,
            'notes' => $row->reconciliation_details ?? $row->error_details ?? null,
            'metadata' => (array) $row,
        ];
        $this->record('legacy:webhook:'.$sourceHash.':received', $base + [
            'event_at' => $row->received_at,
            'event_type' => 'webhook_received',
            'new_status' => 'received',
        ]);

        if ($row->processing_status === 'processed' && $row->processed_at !== null) {
            $this->record('legacy:webhook:'.$sourceHash.':processed', $base + [
                'event_at' => $row->processed_at,
                'event_type' => 'webhook_processed',
                'new_status' => 'processed',
            ]);
        }

        if ($row->processing_status === 'failed') {
            $this->record('legacy:webhook:'.$sourceHash.':failed', $base + [
                'event_at' => $row->updated_at,
                'event_type' => 'webhook_failed',
                'new_status' => 'failed',
            ]);
        }

        if (($row->reconciliation_status ?? null) === 'accepted') {
            $milestone = match ($row->event_type) {
                'delivery.logistics_received' => 'logistics_received',
                'delivery.delivered' => 'logistics_delivered',
                default => null,
            };
            if ($milestone !== null) {
                $this->record('legacy:webhook:'.$sourceHash.':milestone', $base + [
                    'event_at' => $row->event_occurred_at,
                    'event_type' => $milestone,
                    'previous_status' => $milestone === 'logistics_delivered' ? 'logistics_received' : null,
                    'new_status' => $milestone === 'logistics_delivered' ? 'delivered' : 'logistics_received',
                ]);
            }
        }
    }

    public function recordWebhookProcessingAttempt(int $eventRowId): void
    {
        if (! Schema::hasTable('delivery_audit_events')) {
            return;
        }

        $row = DB::table('logistics_webhook_events')->where('id', $eventRowId)->first();
        if ($row === null || $row->processing_status !== 'processing') {
            return;
        }

        $sourceHash = hash('sha256', $row->integration_id.'|'.$row->external_event_id);
        $this->record('legacy:webhook:'.$sourceHash.':processing:'.$row->processing_attempts, [
            'event_at' => $row->updated_at,
            'dr_no' => $row->dr_reference,
            'event_type' => 'webhook_processing',
            'new_status' => 'processing',
            'integration_id' => $row->integration_id,
            'actor_name' => 'Logistics Integration',
            'source_module' => 'Logistics Webhook',
            'related_type' => 'webhook_event',
            'related_id' => (string) $row->external_event_id,
            'notes' => $row->error_details,
            'metadata' => (array) $row,
        ]);
    }

    /** @param array<int, int> $projectIds @param array<int, string> $integrationIds @return array{earliest: string|null, history_start: string|null} */
    public function coverage(array $projectIds, array $integrationIds): array
    {
        $earliest = DeliveryAuditEvent::query()->where(function ($query) use ($projectIds, $integrationIds): void {
            $query->whereIn('project_id', $projectIds)
                ->orWhere(function ($unlinkedInventory): void {
                    $unlinkedInventory->whereNull('project_id')
                        ->whereIn('source_module', ['Inventory', 'Billing Sync']);
                });
            if ($integrationIds !== []) {
                $query->orWhereIn('integration_id', $integrationIds);
            }
        })->min('event_at');

        return [
            'earliest' => $earliest,
            'history_start' => $earliest,
        ];
    }

    public function syncRecordedHistory(): void
    {
        if (Schema::hasTable('inventory_history')) {
            foreach (DB::table('inventory_history')->cursor() as $row) {
                $sourceId = $row->history_id ?? null;
                if ($sourceId === null) {
                    continue;
                }

                $delivery = $this->resolveInventoryDelivery($row);
                $deliveryRecord = is_object($delivery) ? $delivery : null;
                $metadata = (array) $row;
                if ($delivery === null && ! empty($row->dr_no)) {
                    $metadata['mapping_review'] = 'No unique exact delivery match found for this DR number.';
                } elseif ($delivery === false) {
                    $metadata['mapping_review'] = 'Multiple exact delivery records match this inventory transaction.';
                }

                $packageCode = null;
                if (! empty($row->package_id) && Schema::hasTable('package')) {
                    foreach (['core_package_code', 'package_code'] as $column) {
                        if (Schema::hasColumn('package', $column)) {
                            $packageCode = DB::table('package')->where('package_id', $row->package_id)->value($column);
                            break;
                        }
                    }
                }

                $this->record('legacy:inventory_history:'.$sourceId, [
                    'event_at' => $row->changed_at ?? $row->created_at,
                    'project_id' => $row->project_id ?? $deliveryRecord?->project_id ?? $this->projectIdForItem($row->item_id ?? null),
                    'lot_id' => $row->lot_id ?? $deliveryRecord?->lot_id,
                    'school_id' => $deliveryRecord?->school_id,
                    'school_name' => $deliveryRecord?->school_name,
                    'package_code' => $packageCode,
                    'dr_no' => $row->dr_no ?? null,
                    'event_type' => $row->change_type ?? 'inventory_change',
                    'quantity' => $row->quantity ?? (isset($row->old_qty, $row->new_qty) ? abs($row->new_qty - $row->old_qty) : null),
                    'warehouse_id' => $row->warehouse_id ?? null,
                    'warehouse_name' => $this->warehouseName($row->warehouse_id ?? null),
                    'performed_by' => $row->changed_by_user_id ?? null,
                    'actor_name' => $row->changed_by ?? 'System',
                    'source_module' => str_contains((string) ($row->remarks ?? ''), 'Sync Billing') ? 'Billing Sync' : 'Inventory',
                    'related_type' => 'inventory_history',
                    'related_id' => (string) $sourceId,
                    'notes' => $row->remarks ?? null,
                    'metadata' => $metadata,
                ]);
            }
        }

        if (Schema::hasTable('delivery_history') && Schema::hasTable('package_status') && Schema::hasTable('deliveries')) {
            $histories = DB::table('delivery_history as h')
                ->join('package_status as ps', 'ps.package_status_id', '=', 'h.package_status_id')
                ->join('deliveries as d', 'd.delivery_id', '=', 'ps.delivery_id')
                ->leftJoin('school as s', 's.school_id', '=', 'd.school_id')
                ->select('h.*', 'd.project_id', 'd.lot_id', 'd.school_id', 's.school_name', 'd.dr_no')
                ->when(Schema::hasTable('users'), fn ($query) => $query->leftJoin('users as u', 'u.user_id', '=', 'h.user_id')->addSelect('u.name as user_name'))
                ->cursor();
            foreach ($histories as $row) {
                $this->record('legacy:delivery_history:'.$row->id, [
                    'event_at' => $row->created_at, 'project_id' => $row->project_id,
                    'lot_id' => $row->lot_id, 'school_id' => $row->school_id, 'school_name' => $row->school_name,
                    'dr_no' => $row->dr_no, 'event_type' => 'delivery_status', 'previous_status' => $row->previous_status ?? null, 'new_status' => $row->status,
                    'performed_by' => $row->user_id,
                    'actor_name' => ($row->user_name ?? null)
                        ?? (str_contains((string) $row->remarks, 'Logistics') ? 'Logistics Integration' : (str_contains((string) $row->remarks, 'Sync Billing') ? 'Billing Sync' : 'System')),
                    'source_module' => 'Delivery',
                    'related_type' => 'delivery_history', 'related_id' => (string) $row->id,
                    'notes' => $row->remarks, 'metadata' => (array) $row,
                ]);
            }
        }

        if (Schema::hasTable('delivery_billing_histories') && Schema::hasTable('delivery_billing_records')) {
            $histories = DB::table('delivery_billing_histories as h')
                ->join('delivery_billing_records as b', 'b.id', '=', 'h.delivery_billing_record_id')
                ->join('deliveries as d', 'd.delivery_id', '=', 'b.delivery_id')
                ->leftJoin('school as s', 's.school_id', '=', 'd.school_id')
                ->select('h.*', 'b.delivery_id', 'b.billing_batch_id', 'd.project_id', 'd.lot_id', 'd.school_id', 'd.dr_no', 's.school_name')
                ->cursor();
            foreach ($histories as $row) {
                $this->record('legacy:billing_history:'.$row->id, [
                    'event_at' => $row->created_at, 'project_id' => $row->project_id, 'lot_id' => $row->lot_id,
                    'school_id' => $row->school_id, 'school_name' => $row->school_name, 'dr_no' => $row->dr_no,
                    'event_type' => 'billing_'.$row->new_status, 'previous_status' => $row->previous_status,
                    'new_status' => $row->new_status, 'performed_by' => $row->changed_by,
                    'actor_name' => $row->changed_by_name, 'source_module' => 'Billing',
                    'related_type' => $row->billing_batch_id === null ? 'delivery_billing_record' : 'billing_batch',
                    'related_id' => (string) ($row->billing_batch_id ?? $row->delivery_billing_record_id),
                    'notes' => $row->remarks, 'metadata' => (array) $row,
                ]);
            }
        }

        if (Schema::hasTable('logistics_webhook_events')) {
            foreach (DB::table('logistics_webhook_events')->cursor() as $row) {
                $base = [
                    'integration_id' => $row->integration_id,
                    'project_id' => $row->resolved_project_id ?? null, 'school_id' => $row->resolved_school_id ?? null,
                    'dr_no' => $row->dr_reference, 'new_status' => $row->processing_status,
                    'actor_name' => 'Logistics Integration', 'source_module' => 'Logistics Webhook',
                    'related_type' => 'webhook_event', 'related_id' => (string) $row->external_event_id,
                    'notes' => $row->reconciliation_details ?? $row->error_details ?? null, 'metadata' => (array) $row,
                ];
                $sourceHash = hash('sha256', $row->integration_id.'|'.$row->external_event_id);
                $this->record('legacy:webhook:'.$sourceHash.':received', $base + [
                    'event_at' => $row->received_at, 'event_type' => 'webhook_received',
                ]);
                if ($row->processed_at !== null) {
                    $this->record('legacy:webhook:'.$sourceHash.':processed', $base + [
                        'event_at' => $row->processed_at, 'event_type' => 'webhook_processed',
                    ]);
                }
                if (($row->reconciliation_status ?? null) === 'accepted') {
                    $milestone = match ($row->event_type) {
                        'delivery.logistics_received' => 'logistics_received',
                        'delivery.delivered' => 'logistics_delivered',
                        default => null,
                    };
                    if ($milestone !== null) {
                        $this->record('legacy:webhook:'.$sourceHash.':milestone', $base + [
                            'event_at' => $row->event_occurred_at,
                            'event_type' => $milestone,
                            'previous_status' => $milestone === 'logistics_delivered' ? 'logistics_received' : null,
                            'new_status' => $milestone === 'logistics_delivered' ? 'delivered' : 'logistics_received',
                        ]);
                    }
                }
            }
        }
    }

    private function resolveInventoryDelivery(object $history): object|false|null
    {
        if (! Schema::hasTable('deliveries')) {
            return null;
        }

        $query = DB::table('deliveries as d')->leftJoin('school as s', 's.school_id', '=', 'd.school_id');
        if (! empty($history->delivery_id)) {
            $query->where('d.delivery_id', $history->delivery_id);
        } elseif (! empty($history->dr_no)) {
            if (! empty($history->project_id)) {
                $query->where('d.project_id', $history->project_id);
            }
            if (! empty($history->lot_id)) {
                $query->where('d.lot_id', $history->lot_id);
            } elseif (! empty($history->package_id) && Schema::hasTable('package')) {
                $package = DB::table('package')->where('package_id', $history->package_id)->first(['lot_id', 'keystage_id']);
                if ($package?->lot_id !== null) {
                    $query->where('d.lot_id', $package->lot_id);
                } elseif ($package?->keystage_id !== null) {
                    $query->where('d.keystage_id', $package->keystage_id);
                }
            }
            if (Schema::hasColumn('inventory_history', 'school_id') && ! empty($history->school_id)) {
                $query->where('d.school_id', $history->school_id);
            }
            if (in_array(DB::connection()->getDriverName(), ['mysql', 'mariadb'], true)) {
                $query->whereRaw('BINARY d.dr_no = BINARY ?', [$history->dr_no]);
            } else {
                $query->whereRaw('d.dr_no = ? COLLATE BINARY', [$history->dr_no]);
            }
        } else {
            return null;
        }

        $deliveries = $query->limit(2)->get(['d.project_id', 'd.lot_id', 'd.school_id', 's.school_name']);

        return match ($deliveries->count()) {
            1 => $deliveries->first(),
            0 => null,
            default => false,
        };
    }

    private function packageCode(mixed $packageId): ?string
    {
        if (! $packageId || ! Schema::hasTable('package')) {
            return null;
        }

        foreach (['core_package_code', 'package_code'] as $column) {
            if (Schema::hasColumn('package', $column)) {
                $value = DB::table('package')->where('package_id', $packageId)->value($column);

                return $value === null ? null : (string) $value;
            }
        }

        return null;
    }

    private function projectIdForItem(mixed $itemId): ?int
    {
        if (! $itemId || ! Schema::hasTable('item') || ! Schema::hasColumn('item', 'project_id')) {
            return null;
        }

        $projectId = DB::table('item')->where('item_id', $itemId)->value('project_id');

        return $projectId === null ? null : (int) $projectId;
    }

    private function warehouseName(mixed $warehouseId): ?string
    {
        if (! $warehouseId || ! Schema::hasTable('warehouse') || ! Schema::hasColumn('warehouse', 'warehouse_name')) {
            return null;
        }

        $name = DB::table('warehouse')->where('warehouse_id', $warehouseId)->value('warehouse_name');

        return $name === null ? null : (string) $name;
    }
}
