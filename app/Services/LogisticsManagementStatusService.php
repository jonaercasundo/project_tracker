<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class LogisticsManagementStatusService
{
    /** @return list<array<string, mixed>> */
    public function partnerStatuses(): array
    {
        $integrationProjects = DB::table('logistics_integration_projects as assigned_project')
            ->join('logistics_integrations as integration', 'integration.id', '=', 'assigned_project.integration_id')
            ->join('projects', 'projects.project_id', '=', 'assigned_project.project_id')
            ->where('integration.status', 'active')
            ->where(function ($connection): void {
                $connection->where('integration.authentication_method', 'manual')
                    ->orWhere(function ($api): void {
                        $api->where('integration.authentication_method', 'hmac_sha256')
                            ->whereExists(function ($credentials): void {
                                $credentials->selectRaw('1')
                                    ->from('logistics_integration_credentials as credential')
                                    ->whereColumn('credential.integration_id', 'integration.id')
                                    ->where('credential.status', 'active')
                                    ->where(fn ($expires) => $expires->whereNull('credential.expires_at')
                                        ->orWhere('credential.expires_at', '>', now()));
                            });
                    });
            })
            ->orderBy('integration.name')
            ->orderBy('projects.project_name')
            ->get([
                'integration.id as integration_key', 'integration.integration_id', 'integration.name as partner_name',
                'integration.status as integration_status', 'integration.authentication_method',
                'assigned_project.project_id', 'projects.project_name',
            ]);

        if ($integrationProjects->isEmpty()) {
            return [];
        }

        $integrationKeys = $integrationProjects->pluck('integration_key')->unique()->all();
        $activeCredentialIntegrationKeys = DB::table('logistics_integration_credentials')
            ->whereIn('integration_id', $integrationKeys)
            ->where('status', 'active')
            ->where(fn ($query) => $query->whereNull('expires_at')->orWhere('expires_at', '>', now()))
            ->distinct()
            ->pluck('integration_id')
            ->map(fn (mixed $id): int => (int) $id)
            ->all();
        $schoolScopes = DB::table('logistics_integration_schools')
            ->whereIn('integration_id', $integrationKeys)
            ->get(['integration_id', 'project_id', 'school_id'])
            ->groupBy('integration_id');
        $regionScopes = DB::table('logistics_integration_regions')
            ->whereIn('integration_id', $integrationKeys)
            ->get(['integration_id', 'project_id', 'region'])
            ->groupBy('integration_id');
        $projectIds = $integrationProjects->pluck('project_id')->unique()->map(fn (mixed $id): int => (int) $id)->all();
        $trackingStatuses = [];
        if (Schema::hasTable('logistics_dr_tracking')) {
            $integrationIds = $integrationProjects->pluck('integration_id')->unique()->all();
            $trackingRows = DB::table('logistics_dr_tracking')
                ->whereIn('integration_id', $integrationIds)
                ->whereIn('project_id', $projectIds)
                ->get(['integration_id', 'project_id', 'school_id', 'dr_reference', 'logistics_status']);

            foreach ($trackingRows as $tracking) {
                $trackingKey = $tracking->integration_id.':'.(int) $tracking->project_id.':'.base64_encode((string) $tracking->school_id).':'.base64_encode((string) $tracking->dr_reference);
                $trackingStatuses[$trackingKey] = strtolower(trim((string) $tracking->logistics_status));
            }
        }
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
            ->leftJoin('school', 'school.school_id', '=', 'delivery.school_id')
            ->leftJoinSub($billingStates, 'billing_state', 'billing_state.delivery_id', '=', 'delivery.delivery_id')
            ->whereIn('assigned_project.integration_id', $integrationKeys)
            ->whereIn('assigned_project.project_id', $projectIds)
            ->whereNotNull('delivery.dr_no')
            ->whereRaw("TRIM(delivery.dr_no) <> ''")
            ->get([
                'integration.id as integration_key', 'integration.integration_id as integration_code',
                'integration.authentication_method', 'assigned_project.project_id', 'delivery.school_id',
                'school.region as delivery_region',
                'delivery.dr_no', 'delivery.status as source_delivery_status', 'billing_state.active_record_count',
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
                $isAllowed = $delivery->delivery_region !== null && $allowedRegions->contains(fn (object $scope): bool => (int) $scope->project_id === (int) $delivery->project_id
                    && (string) $scope->region === (string) $delivery->delivery_region);
            } else {
                $isAllowed = true;
            }

            if (! $isAllowed) {
                continue;
            }

            $region = trim((string) $delivery->delivery_region) !== '' ? trim((string) $delivery->delivery_region) : 'Region not set';
            $projectId = (int) $delivery->project_id;
            $groupKey = (string) $integrationKey;
            $documentKey = $projectId.':'.base64_encode((string) $delivery->school_id).':'.base64_encode((string) $delivery->dr_no);
            $groups[$groupKey] ??= [
                'integration_key' => $integrationKey,
                'project_ids' => [],
                'regions' => [],
                'documents' => [],
            ];
            $groups[$groupKey]['project_ids'][$projectId] = true;
            $groups[$groupKey]['regions'][$region] = true;
            $groups[$groupKey]['documents'][$documentKey] ??= ['delivery_statuses' => [], 'billing_statuses' => []];
            $groups[$groupKey]['documents'][$documentKey]['school_key'] = $projectId.':'.base64_encode((string) $delivery->school_id);
            $billingStatus = (int) ($delivery->active_record_count ?? 0) === 0
                ? 'no_document'
                : ((int) $delivery->has_billed === 1
                    ? 'billed'
                    : ((int) $delivery->has_for_billing === 1
                        ? 'for_billing'
                        : ((int) $delivery->has_on_hold === 1 ? 'returned' : 'pending_billing')));
            $billingConfirmsDelivery = in_array($billingStatus, ['for_billing', 'billed', 'returned'], true);

            if ($delivery->authentication_method === 'manual') {
                $deliveryStatus = in_array(strtolower(trim((string) $delivery->source_delivery_status)), ['delivered', 'accepted'], true)
                    ? 'delivered'
                    : 'pending';
            } else {
                $trackingKey = $delivery->integration_code.':'.$projectId.':'.base64_encode((string) $delivery->school_id).':'.base64_encode((string) $delivery->dr_no);
                $deliveryStatus = $trackingStatuses[$trackingKey] ?? null;
                if (! in_array($deliveryStatus, ['logistics_received', 'delivered'], true)) {
                    if ($billingConfirmsDelivery) {
                        $deliveryStatus = 'delivered';
                    } else {
                        $groups[$groupKey]['documents'][$documentKey]['delivery_statuses']['pending'] = true;

                        continue;
                    }
                }
            }

            if ($billingConfirmsDelivery) {
                $deliveryStatus = 'delivered';
            }

            $groups[$groupKey]['documents'][$documentKey]['delivery_statuses'][$deliveryStatus] = true;
            $groups[$groupKey]['documents'][$documentKey]['billing_statuses'][$billingStatus] = true;
        }

        $report = [];
        foreach ($groups as $group) {
            $deliveryCounts = [];
            $billingCounts = [];
            $schools = [];
            foreach ($group['documents'] as $document) {
                $deliveryStatuses = array_keys($document['delivery_statuses']);
                $deliveryState = in_array('delivered', $deliveryStatuses, true)
                    ? 'delivered'
                    : (in_array('logistics_received', $deliveryStatuses, true)
                        ? 'logistics_received'
                        : (in_array('pending', $deliveryStatuses, true) ? 'pending' : null));
                $billingStatuses = array_keys($document['billing_statuses']);
                $billingState = in_array('returned', $billingStatuses, true)
                    ? 'returned'
                    : (in_array('for_billing', $billingStatuses, true) ? 'for_billing' : null);
                $schoolKey = $document['school_key'];
                $schools[$schoolKey] ??= ['delivery_statuses' => [], 'billing_statuses' => []];
                if ($deliveryState !== null) {
                    $schools[$schoolKey]['delivery_statuses'][$deliveryState] = true;
                }
                if ($billingState !== null) {
                    $schools[$schoolKey]['billing_statuses'][$billingState] = true;
                }
            }

            foreach ($schools as $school) {
                $schoolDeliveryStatuses = array_keys($school['delivery_statuses']);
                $deliveryState = in_array('delivered', $schoolDeliveryStatuses, true)
                    ? 'delivered'
                    : (in_array('logistics_received', $schoolDeliveryStatuses, true)
                        ? 'logistics_received'
                        : (in_array('pending', $schoolDeliveryStatuses, true) ? 'pending' : null));
                $schoolBillingStatuses = array_keys($school['billing_statuses']);
                $billingState = in_array('returned', $schoolBillingStatuses, true)
                    ? 'returned'
                    : (in_array('for_billing', $schoolBillingStatuses, true) ? 'for_billing' : null);
                if ($deliveryState !== null) {
                    $deliveryCounts[$deliveryState] = ($deliveryCounts[$deliveryState] ?? 0) + 1;
                }
                if ($billingState !== null) {
                    $billingCounts[$billingState] = ($billingCounts[$billingState] ?? 0) + 1;
                }
            }

            $integration = $integrationProjects->first(fn (object $row): bool => (int) $row->integration_key === $group['integration_key']);
            $projectNames = $integrationProjects
                ->where('integration_key', $group['integration_key'])
                ->whereIn('project_id', array_keys($group['project_ids']))
                ->pluck('project_name')
                ->unique()
                ->values()
                ->all();
            $report[] = [
                'integration_id' => $integration->integration_id,
                'partner_name' => $integration->partner_name,
                'api_status' => $integration->authentication_method === 'manual'
                    ? 'No API'
                    : (in_array($group['integration_key'], $activeCredentialIntegrationKeys, true) ? 'API Active' : 'API Inactive'),
                'project_name' => implode(', ', $projectNames),
                'region' => implode(', ', array_keys($group['regions'])),
                'total_schools' => count($schools),
                'total_drs' => count($group['documents']),
                'delivery_statuses' => $deliveryCounts,
                'billing_statuses' => $billingCounts,
            ];
        }

        return $report;
    }
}
