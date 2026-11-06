<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class LogisticsManagementStatusService
{
    /** @return list<array<string, mixed>> */
    public function partnerStatuses(): array
    {
        $integrationProjects = DB::table('logistics_integration_projects as assigned_project')
            ->join('logistics_integrations as integration', 'integration.id', '=', 'assigned_project.integration_id')
            ->join('projects', 'projects.project_id', '=', 'assigned_project.project_id')
            ->orderBy('integration.name')
            ->orderBy('projects.project_name')
            ->get([
                'integration.id as integration_key', 'integration.integration_id', 'integration.name as partner_name',
                'integration.status as integration_status', 'assigned_project.project_id', 'projects.project_name',
            ]);

        if ($integrationProjects->isEmpty()) {
            return [];
        }

        $integrationKeys = $integrationProjects->pluck('integration_key')->unique()->all();
        $schoolScopes = DB::table('logistics_integration_schools')
            ->whereIn('integration_id', $integrationKeys)
            ->get(['integration_id', 'project_id', 'school_id'])
            ->groupBy('integration_id');
        $regionScopes = DB::table('logistics_integration_regions')
            ->whereIn('integration_id', $integrationKeys)
            ->get(['integration_id', 'project_id', 'region'])
            ->groupBy('integration_id');
        $projectIds = $integrationProjects->pluck('project_id')->unique()->map(fn (mixed $id): int => (int) $id)->all();
        $billingStates = DB::table('delivery_billing_records')
            ->select('delivery_id')
            ->selectRaw('SUM(CASE WHEN status <> ? THEN 1 ELSE 0 END) as active_record_count', ['revoked'])
            ->selectRaw('MAX(CASE WHEN status = ? THEN 1 ELSE 0 END) as has_billed', ['billed'])
            ->selectRaw('MAX(CASE WHEN status = ? THEN 1 ELSE 0 END) as has_for_billing', ['for_billing'])
            ->selectRaw('MAX(CASE WHEN status = ? THEN 1 ELSE 0 END) as has_on_hold', ['on_hold'])
            ->groupBy('delivery_id');
        $deliveryRows = DB::table('logistics_integration_projects as assigned_project')
            ->join('logistics_integrations as integration', 'integration.id', '=', 'assigned_project.integration_id')
            ->join('deliveries as delivery', 'delivery.project_id', '=', 'assigned_project.project_id')
            ->leftJoin('school', function ($join): void {
                $join->on('school.project_id', '=', 'delivery.project_id')
                    ->on('school.school_id', '=', 'delivery.school_id');
            })
            ->leftJoinSub($billingStates, 'billing_state', 'billing_state.delivery_id', '=', 'delivery.delivery_id')
            ->whereIn('assigned_project.project_id', $projectIds)
            ->whereNotNull('delivery.dr_no')
            ->whereRaw("TRIM(delivery.dr_no) <> ''")
            ->get([
                'integration.id as integration_key', 'assigned_project.project_id', 'delivery.school_id', 'school.region',
                'delivery.dr_no', 'delivery.status as delivery_status', 'billing_state.active_record_count',
                'billing_state.has_billed', 'billing_state.has_for_billing', 'billing_state.has_on_hold',
            ]);

        $groups = [];
        foreach ($deliveryRows as $delivery) {
            $integrationKey = (int) $delivery->integration_key;
            $allowedSchools = $schoolScopes->get($integrationKey, collect());
            $allowedRegions = $regionScopes->get($integrationKey, collect());
            if ($allowedSchools->isNotEmpty()) {
                $isAllowed = $allowedSchools->contains(fn (object $scope): bool => (int) $scope->project_id === (int) $delivery->project_id
                    && (string) $scope->school_id === (string) $delivery->school_id);
            } elseif ($allowedRegions->isNotEmpty()) {
                $isAllowed = $delivery->region !== null && $allowedRegions->contains(fn (object $scope): bool => (int) $scope->project_id === (int) $delivery->project_id
                    && (string) $scope->region === (string) $delivery->region);
            } else {
                $isAllowed = true;
            }

            if (! $isAllowed) {
                continue;
            }

            $region = trim((string) $delivery->region) !== '' ? trim((string) $delivery->region) : 'Region not set';
            $groupKey = $integrationKey.':'.(int) $delivery->project_id.':'.base64_encode($region);
            $documentKey = base64_encode((string) $delivery->school_id).':'.base64_encode((string) $delivery->dr_no);
            $groups[$groupKey] ??= [
                'integration_key' => $integrationKey,
                'project_id' => (int) $delivery->project_id,
                'region' => $region,
                'documents' => [],
            ];
            $groups[$groupKey]['documents'][$documentKey] ??= ['delivery_statuses' => [], 'billing_statuses' => []];

            $deliveryStatus = strtolower(trim((string) $delivery->delivery_status));
            $deliveryStatus = $deliveryStatus !== '' ? $deliveryStatus : 'pending';
            $groups[$groupKey]['documents'][$documentKey]['delivery_statuses'][$deliveryStatus] = true;

            $billingStatus = (int) ($delivery->active_record_count ?? 0) === 0
                ? 'no_document'
                : ((int) $delivery->has_billed === 1
                    ? 'billed'
                    : ((int) $delivery->has_for_billing === 1
                        ? 'for_billing'
                        : ((int) $delivery->has_on_hold === 1 ? 'on_hold' : 'pending_billing')));
            $groups[$groupKey]['documents'][$documentKey]['billing_statuses'][$billingStatus] = true;
        }

        $report = [];
        foreach ($groups as $group) {
            $deliveryCounts = [];
            $billingCounts = [];
            foreach ($group['documents'] as $document) {
                $deliveryStatuses = array_keys($document['delivery_statuses']);
                $deliveryState = count($deliveryStatuses) === 1 ? $deliveryStatuses[0] : 'mixed';
                $billingStatuses = array_keys($document['billing_statuses']);
                $billingState = count($billingStatuses) === 1 ? $billingStatuses[0] : 'mixed';
                $deliveryCounts[$deliveryState] = ($deliveryCounts[$deliveryState] ?? 0) + 1;
                $billingCounts[$billingState] = ($billingCounts[$billingState] ?? 0) + 1;
            }

            $integration = $integrationProjects->first(fn (object $row): bool => (int) $row->integration_key === $group['integration_key']);
            $project = $integrationProjects->first(fn (object $row): bool => (int) $row->integration_key === $group['integration_key']
                && (int) $row->project_id === $group['project_id']);
            $report[] = [
                'integration_id' => $integration->integration_id,
                'partner_name' => $integration->partner_name,
                'integration_status' => $integration->integration_status,
                'project_id' => $group['project_id'],
                'project_name' => $project->project_name,
                'region' => $group['region'],
                'total_drs' => count($group['documents']),
                'delivery_statuses' => $deliveryCounts,
                'billing_statuses' => $billingCounts,
            ];
        }

        return $report;
    }
}
