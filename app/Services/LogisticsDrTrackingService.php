<?php

namespace App\Services;

use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class LogisticsDrTrackingService
{
    /**
     * Synchronize one already resolved and sequence-validated event within its caller's transaction.
     *
     * @return array{status: string, details: string}
     */
    public function synchronize(object $event, int $projectId, string $schoolId): array
    {
        $scope = [
            'integration_id' => $event->integration_id,
            'project_id' => $projectId,
            'school_scope_hash' => hash('sha256', $schoolId),
            'dr_reference_hash' => hash('sha256', $event->dr_reference),
        ];

        if ($event->event_type === 'delivery.logistics_received') {
            $inserted = DB::table('logistics_dr_tracking')->insertOrIgnore([
                ...$scope,
                'school_id' => $schoolId,
                'dr_reference' => $event->dr_reference,
                'logistics_status' => 'logistics_received',
                'latest_event_id' => $event->external_event_id,
                'latest_webhook_event_row_id' => $event->id,
                'event_occurred_at' => $event->event_occurred_at,
                'latest_sequence' => $event->event_sequence,
                'last_synchronized_at' => now(),
                'reconciliation_status' => 'synchronized',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            if ($inserted === 1) {
                return ['status' => 'accepted', 'details' => 'Logistics received status synchronized for the verified DR scope.'];
            }
        }

        $tracking = DB::table('logistics_dr_tracking')
            ->where($scope)
            ->lockForUpdate()
            ->first();

        if ($tracking === null) {
            return [
                'status' => 'out_of_order',
                'details' => 'Logistics must report logistics_received before delivered for this DR scope.',
            ];
        }

        if ((int) $event->event_sequence <= (int) $tracking->latest_sequence
            || $event->event_occurred_at <= $tracking->event_occurred_at
        ) {
            return [
                'status' => (int) $event->event_sequence === (int) $tracking->latest_sequence ? 'duplicate_sequence' : 'stale',
                'details' => 'The tracking projection already contains a newer or equal event.',
            ];
        }

        if ($tracking->logistics_status === 'delivered') {
            return [
                'status' => 'out_of_order',
                'details' => 'A delivered Logistics status cannot regress to logistics_received.',
            ];
        }

        if ($event->event_type !== 'delivery.delivered' || $tracking->logistics_status !== 'logistics_received') {
            return [
                'status' => 'out_of_order',
                'details' => 'Only logistics_received may progress to delivered.',
            ];
        }

        DB::table('logistics_dr_tracking')
            ->where('id', $tracking->id)
            ->update([
                'logistics_status' => 'delivered',
                'latest_event_id' => $event->external_event_id,
                'latest_webhook_event_row_id' => $event->id,
                'event_occurred_at' => $event->event_occurred_at,
                'latest_sequence' => $event->event_sequence,
                'last_synchronized_at' => now(),
                'reconciliation_status' => 'synchronized',
                'updated_at' => now(),
            ]);

        return ['status' => 'accepted', 'details' => 'Logistics status synchronized for the verified DR scope.'];
    }

    /**
     * Read Logistics milestones alongside an MMC receiving summary for dashboard use.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function dashboardRows(?int $projectId = null, ?string $schoolId = null, ?Collection $trackingRows = null): Collection
    {
        $trackingRows ??= $this->trackingQuery([])
            ->when($projectId !== null, fn ($query) => $query->where('tracking.project_id', $projectId))
            ->when($schoolId !== null, fn ($query) => $query->where('tracking.school_id', $schoolId))
            ->orderBy('tracking.project_id')
            ->orderBy('tracking.school_id')
            ->orderBy('tracking.dr_reference')
            ->get();

        return $this->dashboardRowsFor($trackingRows);
    }

    /**
     * @param  Collection<int, object>  $trackingRows
     * @return Collection<int, array<string, mixed>>
     */
    private function dashboardRowsFor(Collection $trackingRows): Collection
    {
        $receivingSummaries = $this->receivingSummaries($trackingRows);

        return $trackingRows->map(function (object $tracking) use ($receivingSummaries): array {
            $scopeKey = $this->scopeKey((int) $tracking->project_id, (string) $tracking->school_id, (string) $tracking->dr_reference);
            $receiving = $receivingSummaries[$scopeKey] ?? ['status' => 'pending', 'received' => 0, 'expected' => 0];

            return [
                'dr_reference' => $tracking->dr_reference,
                'integration_id' => $tracking->integration_id,
                'project_id' => (int) $tracking->project_id,
                'project_name' => $tracking->project_name,
                'school_id' => $tracking->school_id,
                'school_name' => $tracking->school_name,
                'logistics_status' => $tracking->logistics_status,
                'last_logistics_update' => $tracking->event_occurred_at,
                'mmc_receiving_status' => $receiving['status'],
                'mmc_received_package_count' => $receiving['received'],
                'mmc_expected_package_count' => $receiving['expected'],
                'reconciliation_status' => $tracking->reconciliation_status,
            ];
        });
    }

    /**
     * Return a bounded dashboard page and scope-level summary counts.
     *
     * @param  array<string, mixed>  $filters
     * @return array{rows: \Illuminate\Contracts\Pagination\LengthAwarePaginator, summary: array{logistics_received: int, logistics_delivered: int, pending_updates: int, reconciliation_required: int}}
     */
    public function dashboardPage(array $filters = []): array
    {
        $perPage = (int) ($filters['per_page'] ?? 25);
        $page = (int) ($filters['page'] ?? 1);
        $query = $this->trackingQuery($filters)
            ->orderByDesc('tracking.event_occurred_at')
            ->orderBy('tracking.project_id')
            ->orderBy('tracking.school_id')
            ->orderBy('tracking.dr_reference');

        if (Schema::hasTable('logistics_dr_tracking')) {
            $rows = $query->paginate($perPage, ['tracking.*', 'project.project_name', 'school.school_name'], 'logistics_page', $page);
            $rows->setCollection($this->dashboardRows(trackingRows: $rows->getCollection()));
        } else {
            $rows = new LengthAwarePaginator(collect(), 0, $perPage, $page);
        }

        return [
            'rows' => $rows,
            'summary' => $this->dashboardSummary($filters),
        ];
    }

    /**
     * Return summary counts without retrieving or paginating the tracking rows.
     *
     * @param  array<string, mixed>  $filters
     * @return array{logistics_received: int, logistics_delivered: int, pending_updates: int, reconciliation_required: int}
     */
    public function dashboardSummary(array $filters = []): array
    {
        $summary = ['logistics_received' => 0, 'logistics_delivered' => 0, 'pending_updates' => 0, 'reconciliation_required' => 0];
        $hasTrackingRows = Schema::hasTable('logistics_dr_tracking')
            && DB::table('logistics_dr_tracking')->exists();

        if ($hasTrackingRows) {
            $statusScopes = $this->trackingQuery($filters, withDisplayNames: false)
                ->select('tracking.project_id');
            if (in_array(DB::connection()->getDriverName(), ['mysql', 'mariadb'], true)) {
                $statusScopes->selectRaw('BINARY tracking.school_id as school_id, BINARY tracking.dr_reference as dr_reference');
            } else {
                $statusScopes->addSelect('tracking.school_id', 'tracking.dr_reference');
            }
            $statusScopes
                ->selectRaw("MAX(CASE WHEN tracking.logistics_status = 'logistics_received' THEN 1 ELSE 0 END) as has_received, MAX(CASE WHEN tracking.logistics_status = 'delivered' THEN 1 ELSE 0 END) as has_delivered")
                ->groupBy('tracking.project_id');
            if (in_array(DB::connection()->getDriverName(), ['mysql', 'mariadb'], true)) {
                $statusScopes->groupByRaw('BINARY tracking.school_id, BINARY tracking.dr_reference');
            } else {
                $statusScopes->groupBy('tracking.school_id', 'tracking.dr_reference');
            }
            $statusCounts = DB::query()->fromSub($statusScopes, 'tracking_scopes')->selectRaw(
                'SUM(CASE WHEN has_delivered = 0 THEN has_received ELSE 0 END) as logistics_received, SUM(has_delivered) as logistics_delivered'
            )->first();
            $summary['logistics_received'] = (int) ($statusCounts->logistics_received ?? 0);
            $summary['logistics_delivered'] = (int) ($statusCounts->logistics_delivered ?? 0);
        }

        $pendingQuery = DB::table('deliveries as delivery')
            ->whereNotNull('delivery.dr_no')
            ->where('delivery.dr_no', '<>', '');

        if (isset($filters['school']) && $filters['school'] !== '') {
            $pendingQuery->leftJoin('school as school', function (JoinClause $join): void {
                $this->whereColumnExact($join, 'school.school_id', 'delivery.school_id');
            });
        }

        if (isset($filters['integration_id']) && $filters['integration_id'] !== '') {
            $pendingQuery->whereExists(function (Builder $integrationQuery) use ($filters): void {
                $integrationQuery->selectRaw('1')
                    ->from('logistics_integrations as integration')
                    ->join('logistics_integration_projects as integration_project', 'integration_project.integration_id', '=', 'integration.id')
                    ->where('integration.integration_id', $filters['integration_id'])
                    ->whereColumn('integration_project.project_id', 'delivery.project_id')
                    ->where(function (Builder $schoolScopeQuery): void {
                        $schoolScopeQuery->whereNotExists(function (Builder $unrestrictedScope): void {
                            $unrestrictedScope->selectRaw('1')
                                ->from('logistics_integration_schools as configured_school')
                                ->whereColumn('configured_school.integration_id', 'integration.id')
                                ->whereColumn('configured_school.project_id', 'delivery.project_id');
                        })->orWhereExists(function (Builder $authorizedSchool): void {
                            $authorizedSchool->selectRaw('1')
                                ->from('logistics_integration_schools as configured_school')
                                ->whereColumn('configured_school.integration_id', 'integration.id')
                                ->whereColumn('configured_school.project_id', 'delivery.project_id');
                            $this->whereColumnExact($authorizedSchool, 'configured_school.school_id', 'delivery.school_id');
                        });
                    });
            });
        }

        $this->applyPendingFilters($pendingQuery, $filters);

        if ($hasTrackingRows) {
            $pendingQuery->whereNotExists(function (Builder $trackingQuery) use ($filters): void {
                $trackingQuery->selectRaw('1')
                    ->from('logistics_dr_tracking as tracking')
                    ->whereColumn('tracking.project_id', 'delivery.project_id');
                if (isset($filters['integration_id']) && $filters['integration_id'] !== '') {
                    $trackingQuery->where('tracking.integration_id', (string) $filters['integration_id']);
                }

                if (in_array(DB::connection()->getDriverName(), ['mysql', 'mariadb'], true)) {
                    $trackingQuery->whereRaw('tracking.school_scope_hash = SHA2(CAST(delivery.school_id AS BINARY), 256)')
                        ->whereRaw('tracking.dr_reference_hash = SHA2(CAST(delivery.dr_no AS BINARY), 256)')
                        ->whereRaw('BINARY tracking.school_id = BINARY delivery.school_id')
                        ->whereRaw('BINARY tracking.dr_reference = BINARY delivery.dr_no');
                } else {
                    $this->whereColumnExact($trackingQuery, 'tracking.school_id', 'delivery.school_id');
                    $this->whereColumnExact($trackingQuery, 'tracking.dr_reference', 'delivery.dr_no');
                }
            });
        }

        $pendingScopes = $pendingQuery->select(['delivery.project_id', 'delivery.school_id', 'delivery.dr_no']);
        $this->groupExactScope($pendingScopes, 'delivery');
        $summary['pending_updates'] = (int) DB::query()->fromSub($pendingScopes, 'pending_scopes')->count();
        $summary['total_scoped_drs'] = $summary['pending_updates'] + $summary['logistics_received'] + $summary['logistics_delivered'];
        $summary['delivery_performance_percentage'] = $summary['total_scoped_drs'] === 0
            ? 0
            : round(($summary['logistics_delivered'] / $summary['total_scoped_drs']) * 100, 1);

        $summary['partner_performance'] = $hasTrackingRows
            ? $this->trackingQuery($filters, withDisplayNames: false)
                ->select('tracking.integration_id')
                ->selectRaw('COUNT(*) as total_scopes, COUNT(CASE WHEN tracking.logistics_status = \'delivered\' THEN 1 END) as delivered_scopes')
                ->groupBy('tracking.integration_id')
                ->get()
                ->map(fn (object $partner): array => [
                    'integration_id' => $partner->integration_id,
                    'total_scopes' => (int) $partner->total_scopes,
                    'delivered_scopes' => (int) $partner->delivered_scopes,
                    'performance_percentage' => (int) $partner->total_scopes === 0 ? 0 : round(((int) $partner->delivered_scopes / (int) $partner->total_scopes) * 100, 1),
                ])->all()
            : [];

        if (Schema::hasTable('logistics_webhook_events')) {
            $reconciliationQuery = DB::table('logistics_webhook_events')
                ->where('processing_status', 'processed')
                ->whereIn('reconciliation_status', ['ambiguous', 'unknown', 'out_of_scope', 'out_of_order', 'stale', 'duplicate_sequence'])
                ->when(isset($filters['project_id']) && $filters['project_id'] !== '', fn ($query) => $query->where('resolved_project_id', (int) $filters['project_id']))
                ->when(isset($filters['integration_id']) && $filters['integration_id'] !== '', fn ($query) => $query->where('integration_id', (string) $filters['integration_id']))
                ->when(isset($filters['school']) && $filters['school'] !== '', fn ($query) => $query->where('resolved_school_id', 'like', '%'.$filters['school'].'%'))
                ->when(isset($filters['dr_reference']) && $filters['dr_reference'] !== '', fn ($query) => $this->whereExact($query, 'dr_reference', (string) $filters['dr_reference']))
                ->when(isset($filters['lot_id']) && $filters['lot_id'] !== '', function ($query) use ($filters): void {
                    $query->whereExists(function (Builder $deliveryQuery) use ($filters): void {
                        $deliveryQuery->selectRaw('1')
                            ->from('deliveries as reconciliation_delivery')
                            ->whereColumn('reconciliation_delivery.project_id', 'logistics_webhook_events.resolved_project_id')
                            ->where('reconciliation_delivery.lot_id', (int) $filters['lot_id']);
                        $this->whereColumnExact($deliveryQuery, 'reconciliation_delivery.school_id', 'logistics_webhook_events.resolved_school_id');
                        $this->whereColumnExact($deliveryQuery, 'reconciliation_delivery.dr_no', 'logistics_webhook_events.dr_reference');
                    });
                })
                ->when(isset($filters['date_from']) && $filters['date_from'] !== '', fn ($query) => $query->where('event_occurred_at', '>=', $this->utcDateBoundary($filters['date_from'])))
                ->when(isset($filters['date_to']) && $filters['date_to'] !== '', fn ($query) => $query->where('event_occurred_at', '<', $this->utcDateBoundary($filters['date_to'], true)));

            if (isset($filters['status']) && $filters['status'] !== '') {
                $reconciliationQuery->where('event_type', $filters['status'] === 'delivered' ? 'delivery.delivered' : 'delivery.logistics_received');
            }

            $reconciliationScopes = $reconciliationQuery->select(['integration_id', 'resolved_project_id', 'resolved_school_id', 'dr_reference']);
            $this->groupExactReconciliationScope($reconciliationScopes);
            $summary['reconciliation_required'] = (int) DB::query()->fromSub($reconciliationScopes, 'reconciliation_scopes')->count();
        }

        return $summary;
    }

    /** @param array<string, mixed> $filters */
    private function trackingQuery(array $filters, bool $withDisplayNames = true): Builder
    {
        $query = DB::table('logistics_dr_tracking as tracking')->select('tracking.*');

        if ($withDisplayNames) {
            $query->leftJoin('projects as project', 'project.project_id', '=', 'tracking.project_id')
                ->addSelect('project.project_name');
        } else {
            $query->selectRaw('NULL as project_name');
        }

        if ($withDisplayNames || (isset($filters['school']) && $filters['school'] !== '')) {
            $query->leftJoin('school as school', function (JoinClause $join): void {
                $this->whereColumnExact($join, 'school.school_id', 'tracking.school_id');
            })->addSelect('school.school_name');
        } else {
            $query->selectRaw('NULL as school_name');
        }

        if (isset($filters['project_id']) && $filters['project_id'] !== '') {
            $query->where('tracking.project_id', (int) $filters['project_id']);
        }
        if (isset($filters['integration_id']) && $filters['integration_id'] !== '') {
            $query->where('tracking.integration_id', $filters['integration_id']);
        }
        if (isset($filters['lot_id']) && $filters['lot_id'] !== '') {
            $query->whereExists(function (Builder $deliveryQuery) use ($filters): void {
                $deliveryQuery->selectRaw('1')
                    ->from('deliveries as scoped_delivery')
                    ->whereColumn('scoped_delivery.project_id', 'tracking.project_id')
                    ->where('scoped_delivery.lot_id', (int) $filters['lot_id']);
                $this->whereColumnExact($deliveryQuery, 'scoped_delivery.school_id', 'tracking.school_id');
                $this->whereColumnExact($deliveryQuery, 'scoped_delivery.dr_no', 'tracking.dr_reference');
            });
        }
        if (isset($filters['dr_reference']) && $filters['dr_reference'] !== '') {
            $this->whereExact($query, 'tracking.dr_reference', (string) $filters['dr_reference']);
        }
        if (isset($filters['school']) && $filters['school'] !== '') {
            $query->where(function ($schoolQuery) use ($filters): void {
                $schoolQuery->where('tracking.school_id', 'like', '%'.$filters['school'].'%')
                    ->orWhere('school.school_name', 'like', '%'.$filters['school'].'%');
            });
        }
        if (isset($filters['status']) && $filters['status'] !== '') {
            $query->where('tracking.logistics_status', $filters['status']);
        }
        if (isset($filters['date_from']) && $filters['date_from'] !== '') {
            $query->where('tracking.event_occurred_at', '>=', $this->utcDateBoundary($filters['date_from']));
        }
        if (isset($filters['date_to']) && $filters['date_to'] !== '') {
            $query->where('tracking.event_occurred_at', '<', $this->utcDateBoundary($filters['date_to'], true));
        }

        return $query;
    }

    private function applyPendingFilters(Builder $query, array $filters): void
    {
        if (isset($filters['project_id']) && $filters['project_id'] !== '') {
            $query->where('delivery.project_id', (int) $filters['project_id']);
        }
        if (isset($filters['lot_id']) && $filters['lot_id'] !== '') {
            $query->where('delivery.lot_id', (int) $filters['lot_id']);
        }
        if (isset($filters['dr_reference']) && $filters['dr_reference'] !== '') {
            $this->whereExact($query, 'delivery.dr_no', (string) $filters['dr_reference']);
        }
        if (isset($filters['school']) && $filters['school'] !== '') {
            $query->where(function ($schoolQuery) use ($filters): void {
                $schoolQuery->where('delivery.school_id', 'like', '%'.$filters['school'].'%')
                    ->orWhere('school.school_name', 'like', '%'.$filters['school'].'%');
            });
        }
        if (isset($filters['date_from']) && $filters['date_from'] !== '') {
            $query->where('delivery.delivery_date', '>=', $filters['date_from']);
        }
        if (isset($filters['date_to']) && $filters['date_to'] !== '') {
            $query->where('delivery.delivery_date', '<', date('Y-m-d', strtotime($filters['date_to'].' +1 day')));
        }
    }

    private function whereExact(Builder $query, string $column, string $value): void
    {
        $query->where($column, $value);
        if (in_array(DB::connection()->getDriverName(), ['mysql', 'mariadb'], true)) {
            $query->whereRaw('BINARY '.$column.' = BINARY ?', [$value]);
        } else {
            $query->whereRaw($column.' = ? COLLATE BINARY', [$value]);
        }
    }

    private function utcDateBoundary(string $date, bool $nextDay = false): string
    {
        $boundary = Carbon::createFromFormat('!Y-m-d', $date, config('app.timezone'));
        if ($nextDay) {
            $boundary->addDay();
        }

        return $boundary->startOfDay()->utc()->format('Y-m-d H:i:s');
    }

    private function whereColumnExact(Builder $query, string $left, string $right): void
    {
        if (in_array(DB::connection()->getDriverName(), ['mysql', 'mariadb'], true)) {
            $query->whereRaw('BINARY '.$left.' = BINARY '.$right);
        } else {
            $query->whereRaw($left.' = '.$right.' COLLATE BINARY');
        }
    }

    private function groupExactScope(Builder $query, string $alias): void
    {
        if (in_array(DB::connection()->getDriverName(), ['mysql', 'mariadb'], true)) {
            $query->select([])->selectRaw('BINARY '.$alias.'.school_id as school_scope, BINARY '.$alias.'.dr_no as dr_scope');
            $query->groupByRaw($alias.'.project_id, BINARY '.$alias.'.school_id, BINARY '.$alias.'.dr_no');
        } else {
            $query->select([$alias.'.project_id', $alias.'.school_id', $alias.'.dr_no']);
            $query->groupBy($alias.'.project_id', $alias.'.school_id', $alias.'.dr_no');
        }
    }

    private function groupExactReconciliationScope(Builder $query): void
    {
        if (in_array(DB::connection()->getDriverName(), ['mysql', 'mariadb'], true)) {
            $query->select([])->selectRaw('BINARY integration_id as integration_scope, resolved_project_id, BINARY resolved_school_id as school_scope, BINARY dr_reference as dr_scope');
            $query->groupByRaw('BINARY integration_id, resolved_project_id, BINARY resolved_school_id, BINARY dr_reference');
        } else {
            $query->select(['integration_id', 'resolved_project_id', 'resolved_school_id', 'dr_reference']);
            $query->groupBy('integration_id', 'resolved_project_id', 'resolved_school_id', 'dr_reference');
        }
    }

    /**
     * @param  Collection<int, object>  $trackingRows
     * @return array<string, array{status: string, received: int, expected: int}>
     */
    private function receivingSummaries(Collection $trackingRows): array
    {
        $summaries = [];
        $scopeKeys = [];
        $deliveryRowsByScope = [];
        foreach ($trackingRows as $tracking) {
            $scopeKey = $this->scopeKey((int) $tracking->project_id, (string) $tracking->school_id, (string) $tracking->dr_reference);
            $scopeKeys[$scopeKey] = true;
            $summaries[$scopeKey] = ['status' => 'pending', 'received' => 0, 'expected' => 0];
        }

        foreach ($trackingRows->chunk(200) as $trackingChunk) {
            $deliveries = DB::table('deliveries')
                ->whereIn('project_id', $trackingChunk->pluck('project_id')->unique()->all())
                ->whereIn('dr_no', $trackingChunk->pluck('dr_reference')->unique()->all())
                ->get(['delivery_id', 'project_id', 'school_id', 'dr_no', 'status', 'keystage_id', 'lot_id'])
                ->filter(fn (object $delivery): bool => isset($scopeKeys[$this->scopeKey((int) $delivery->project_id, (string) $delivery->school_id, (string) $delivery->dr_no)]));

            $deliveriesByScope = $deliveries->groupBy(fn (object $delivery): string => $this->scopeKey((int) $delivery->project_id, (string) $delivery->school_id, (string) $delivery->dr_no));
            foreach ($deliveriesByScope as $scopeKey => $scopeDeliveries) {
                $deliveryRowsByScope[$scopeKey] = collect($deliveryRowsByScope[$scopeKey] ?? [])->concat($scopeDeliveries);
            }
            $deliveryIds = $deliveries->pluck('delivery_id')->all();
            $packageKeys = $deliveries->map(function (object $delivery): ?array {
                $column = $delivery->keystage_id !== null ? 'keystage_id' : 'lot_id';
                $value = $delivery->{$column};

                return $value === null ? null : [$column, (int) $value];
            })->filter()->unique(fn (array $key): string => $key[0].':'.$key[1]);

            $packages = collect();
            if ($packageKeys->isNotEmpty()) {
                $packages = DB::table('package')
                    ->where(function ($query) use ($packageKeys): void {
                        foreach (['keystage_id', 'lot_id'] as $column) {
                            $values = $packageKeys->where(0, $column)->pluck(1)->unique()->all();
                            if ($values !== []) {
                                $query->orWhereIn($column, $values);
                            }
                        }
                    })
                    ->get(['package_id', 'keystage_id', 'lot_id']);
            }

            $packageIdsByAllocation = [];
            foreach ($packages as $package) {
                foreach (['keystage_id', 'lot_id'] as $column) {
                    if ($package->{$column} !== null) {
                        $packageIdsByAllocation[$column.':'.(int) $package->{$column}][] = (int) $package->package_id;
                    }
                }
            }

            $latestStatuses = collect();
            if ($deliveryIds !== []) {
                $latestStatuses = DB::table('package_status as current_status')
                    ->joinSub(
                        DB::table('package_status as history')
                            ->select('delivery_id', 'package_id')
                            ->selectRaw('MAX(package_status_id) as package_status_id')
                            ->whereIn('delivery_id', $deliveryIds)
                            ->groupBy('delivery_id', 'package_id'),
                        'latest_status',
                        'latest_status.package_status_id',
                        '=',
                        'current_status.package_status_id'
                    )
                    ->get(['current_status.delivery_id', 'current_status.package_id', 'current_status.status'])
                    ->keyBy(fn (object $status): string => $status->delivery_id.':'.$status->package_id);
            }

            foreach ($deliveriesByScope as $scopeKey => $scopeDeliveries) {
                foreach ($scopeDeliveries as $delivery) {
                    $allocationKey = $delivery->keystage_id !== null ? 'keystage_id' : 'lot_id';
                    $allocationValue = $delivery->{$allocationKey};
                    $packageIds = $allocationValue === null ? [] : ($packageIdsByAllocation[$allocationKey.':'.(int) $allocationValue] ?? []);

                    if ($packageIds !== []) {
                        $summaries[$scopeKey]['expected'] += count($packageIds);
                        foreach ($packageIds as $packageId) {
                            $status = $latestStatuses->get($delivery->delivery_id.':'.$packageId);
                            if ($status !== null && in_array($status->status, ['delivered', 'accepted'], true)) {
                                $summaries[$scopeKey]['received']++;
                            }
                        }
                    }
                }
            }
        }

        foreach ($summaries as $scopeKey => &$summary) {
            if ($summary['expected'] > 0) {
                $summary['status'] = $summary['received'] === $summary['expected']
                    ? 'complete'
                    : ($summary['received'] > 0 ? 'partially_received' : 'pending');

                continue;
            }

            $scopeDeliveries = $deliveryRowsByScope[$scopeKey] ?? collect();
            $eligible = $scopeDeliveries->filter(fn (object $delivery): bool => ! in_array(strtolower(trim((string) $delivery->status)), ['cancelled', 'canceled', 'void', 'voided', 'deleted', 'rejected'], true));
            $completed = $eligible->filter(fn (object $delivery): bool => in_array($delivery->status, ['delivered', 'accepted'], true))->count();
            $summary['status'] = $eligible->isNotEmpty() && $completed === $eligible->count()
                ? 'complete'
                : ($completed > 0 ? 'partially_received' : 'pending');
        }
        unset($summary);

        return $summaries;
    }

    private function scopeKey(int $projectId, string $schoolId, string $drReference): string
    {
        return $projectId.':'.hash('sha256', $schoolId).':'.hash('sha256', $drReference);
    }
}
