<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class LogisticsDrResolver
{
    /**
     * Resolve a DR to exactly one project and school receipt scope.
     *
     * @return array{status: string, project_id: int|null, school_id: string|null, delivery_count: int, reason: string|null}
     */
    public function resolve(string $integrationId, string $drReference): array
    {
        $deliveries = $this->deliveriesForDr($drReference);

        if ($deliveries->isEmpty()) {
            return $this->unresolved('unknown', 'No MMC delivery rows use this exact DR reference.');
        }

        $mappingQuery = DB::table('logistics_webhook_dr_mappings')
            ->where('integration_id', $integrationId);

        if (DB::connection()->getDriverName() === 'mysql') {
            $mappingQuery->whereRaw('BINARY dr_reference = BINARY ?', [$drReference]);
        } else {
            $mappingQuery->whereRaw('dr_reference = ? COLLATE BINARY', [$drReference]);
        }

        $mapping = $mappingQuery->get();

        if ($mapping->count() > 1) {
            return $this->unresolved('ambiguous', 'More than one approved DR mapping exists for this integration.');
        }

        if ($mapping->count() === 1) {
            $approvedScope = $mapping->first();
            $mappedDeliveries = $deliveries->filter(fn (object $delivery): bool => (int) $delivery->project_id === (int) $approvedScope->project_id
                && (string) $delivery->school_id === (string) $approvedScope->school_id
            );

            return $this->scopeResult($mappedDeliveries, 'approved_mapping');
        }

        $scope = config('logistics-webhooks.integration_scopes.'.$integrationId, []);
        $projectIds = array_map('intval', is_array($scope['project_ids'] ?? null) ? $scope['project_ids'] : []);
        $schoolIds = array_map('strval', is_array($scope['school_ids'] ?? null) ? $scope['school_ids'] : []);

        if ($projectIds === []) {
            return $this->unresolved('out_of_scope', 'No project scope or approved DR mapping is configured for this integration.');
        }

        $scopedDeliveries = $deliveries->filter(function (object $delivery) use ($projectIds, $schoolIds): bool {
            return in_array((int) $delivery->project_id, $projectIds, true)
                && ($schoolIds === [] || in_array((string) $delivery->school_id, $schoolIds, true));
        });

        if ($scopedDeliveries->isEmpty()) {
            return $this->unresolved('out_of_scope', 'No matching DR rows are within this integration scope.');
        }

        return $this->scopeResult($scopedDeliveries, 'integration_scope');
    }

    /**
     * Lock all rows in a resolved receipt scope to serialize event sequencing.
     *
     * @return array<int, int>
     */
    public function lockReceiptRows(string $drReference, int $projectId, string $schoolId): array
    {
        return $this->exactDrQuery($drReference)
            ->where('project_id', $projectId)
            ->where('school_id', $schoolId)
            ->orderBy('delivery_id')
            ->lockForUpdate()
            ->pluck('delivery_id')
            ->map(fn ($deliveryId): int => (int) $deliveryId)
            ->all();
    }

    private function deliveriesForDr(string $drReference)
    {
        return $this->exactDrQuery($drReference)
            ->select(['delivery_id', 'project_id', 'school_id'])
            ->orderBy('project_id')
            ->orderBy('school_id')
            ->orderBy('delivery_id')
            ->get();
    }

    private function exactDrQuery(string $drReference)
    {
        $query = DB::table('deliveries');

        if (DB::connection()->getDriverName() === 'mysql') {
            return $query->whereRaw('BINARY dr_no = BINARY ?', [$drReference]);
        }

        return $query->whereRaw('dr_no = ? COLLATE BINARY', [$drReference]);
    }

    /**
     * @param  Collection<int, object>  $deliveries
     * @return array{status: string, project_id: int|null, school_id: string|null, delivery_count: int, reason: string|null}
     */
    private function scopeResult($deliveries, string $source): array
    {
        $scopes = $deliveries->unique(fn (object $delivery): string => $delivery->project_id.'|'.$delivery->school_id);

        if ($scopes->count() !== 1) {
            return $this->unresolved(
                $scopes->isEmpty() ? 'unknown' : 'ambiguous',
                $scopes->isEmpty()
                    ? 'The configured DR scope does not match an MMC receipt scope.'
                    : 'The DR reference identifies multiple project and school receipt scopes.'
            );
        }

        $scope = $scopes->sole();

        return [
            'status' => 'resolved',
            'project_id' => (int) $scope->project_id,
            'school_id' => (string) $scope->school_id,
            'delivery_count' => $deliveries->count(),
            'reason' => $source,
        ];
    }

    /** @return array{status: string, project_id: null, school_id: null, delivery_count: int, reason: string} */
    private function unresolved(string $status, string $reason): array
    {
        return [
            'status' => $status,
            'project_id' => null,
            'school_id' => null,
            'delivery_count' => 0,
            'reason' => $reason,
        ];
    }
}
