<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class DuplicateDrCleanupService
{
    public function groups(int $companyId, array $filters, int $perPage = 20): LengthAwarePaginator
    {
        $query = $this->candidateQuery($companyId, $filters);

        $paginator = $query->paginate($perPage)->withQueryString();
        $groups = collect($paginator->items())->map(fn (object $group): object => $this->hydrateGroup($companyId, $group));

        return $paginator->setCollection($groups);
    }

    /** @return array<int, object> */
    public function allGroups(int $companyId, array $filters): array
    {
        return $this->candidateQuery($companyId, $filters)
            ->get()
            ->map(fn (object $group): object => $this->hydrateGroup($companyId, $group))
            ->all();
    }

    /** @return array{groups: int, records: int} */
    public function summary(int $companyId, array $filters): array
    {
        $groups = $this->candidateQuery($companyId, $filters)->get();

        return [
            'groups' => $groups->count(),
            'records' => (int) $groups->sum('record_count'),
        ];
    }

    /** @return array{allowed: bool, reason: ?string} */
    public function resolutionEligibility(int $companyId, object $group): array
    {
        $ids = collect($group->records)->pluck('delivery_id');
        $billing = Schema::hasTable('delivery_billing_records')
            && DB::table('delivery_billing_records')->whereIn('delivery_id', $ids)->exists();
        if ($billing) {
            return ['allowed' => false, 'reason' => 'Billing records already depend on one or more deliveries in this group.'];
        }

        if (Schema::hasTable('billing_sheet_syncs') && DB::table('billing_sheet_syncs')->whereIn('delivery_id', $ids)->exists()) {
            return ['allowed' => false, 'reason' => 'A billing sheet synchronization record depends on one or more deliveries in this group.'];
        }

        if (Schema::hasTable('bidding_school_assignments') && Schema::hasColumn('bidding_school_assignments', 'delivery_id')
            && DB::table('bidding_school_assignments')->whereIn('delivery_id', $ids)->exists()) {
            return ['allowed' => false, 'reason' => 'Bidding school assignments depend on one or more deliveries in this group.'];
        }

        if (Schema::hasTable('logistics_dr_tracking')) {
            $tracking = DB::table('logistics_dr_tracking')
                ->where('project_id', $group->project_id)
                ->where('school_id', (string) $group->school_id)
                ->whereRaw('LOWER(TRIM(dr_reference)) = ?', [mb_strtolower(trim((string) $group->dr_no))])
                ->exists();
            if ($tracking) {
                return ['allowed' => false, 'reason' => 'A logistics tracking record exists for this project, school, and DR.'];
            }
        }

        return ['allowed' => true, 'reason' => null];
    }

    /**
     * Mark the explicitly selected duplicate rows cancelled while preserving all linked rows.
     *
     * @param  array<int, int>  $affectedIds
     */
    public function resolve(int $companyId, User $actor, int $keepId, array $affectedIds): void
    {
        DB::transaction(function () use ($companyId, $actor, $keepId, $affectedIds): void {
            $deliveries = DB::table('deliveries as d')
                ->join('projects as p', 'p.project_id', '=', 'd.project_id')
                ->whereIn('d.delivery_id', [$keepId, ...$affectedIds])
                ->where(function (Builder $query) use ($companyId): void {
                    if (Schema::hasColumn('projects', 'company_id')) {
                        $query->whereNull('p.company_id')->orWhere('p.company_id', $companyId);
                    } else {
                        $query->whereRaw('1 = 1');
                    }
                })
                ->lockForUpdate()
                ->select('d.*')
                ->get();

            if ($deliveries->count() !== count($affectedIds) + 1 || ! $deliveries->contains('delivery_id', $keepId)) {
                throw ValidationException::withMessages(['records' => 'The selected delivery records are no longer in your company scope. Refresh the review and try again.']);
            }

            $first = $deliveries->first();
            $expected = $this->groupKey($first);
            foreach ($deliveries as $delivery) {
                if ($this->groupKey($delivery) !== $expected) {
                    throw ValidationException::withMessages(['records' => 'The selected rows no longer form one duplicate group. Refresh the review and try again.']);
                }
            }
            $packageSignatures = $this->packageSignatures($deliveries->pluck('delivery_id')->map(fn ($id): int => (int) $id)->all(), true);
            if ($packageSignatures->unique()->count() > 1) {
                throw ValidationException::withMessages(['records' => 'The selected rows no longer have matching linked package records. Refresh the review.']);
            }

            $completeGroupQuery = DB::table('deliveries as d')
                ->join('projects as p', 'p.project_id', '=', 'd.project_id')
                ->where('d.project_id', $first->project_id)
                ->whereRaw('LOWER(TRIM(d.dr_no)) = ?', [mb_strtolower(trim((string) $first->dr_no))])
                ->where('d.school_id', $first->school_id)
                ->where(function (Builder $query) use ($first): void {
                    $first->lot_id === null ? $query->whereNull('d.lot_id') : $query->where('d.lot_id', $first->lot_id);
                });
            if (Schema::hasColumn('deliveries', 'keystage_id')) {
                $first->keystage_id === null ? $completeGroupQuery->whereNull('d.keystage_id') : $completeGroupQuery->where('d.keystage_id', $first->keystage_id);
            }
            if (Schema::hasColumn('deliveries', 'delivery_date')) {
                $first->delivery_date === null ? $completeGroupQuery->whereNull('d.delivery_date') : $completeGroupQuery->where('d.delivery_date', $first->delivery_date);
            }
            if (Schema::hasColumn('projects', 'company_id')) {
                $completeGroupQuery->where(function (Builder $query) use ($companyId): void {
                    $query->whereNull('p.company_id')->orWhere('p.company_id', $companyId);
                });
            }
            $completeCandidateRows = $completeGroupQuery->lockForUpdate()->get(['d.delivery_id', 'd.status']);
            $completeCandidateIds = $completeCandidateRows->pluck('delivery_id')->map(fn ($id): int => (int) $id)->all();
            $completePackageSignatures = $this->packageSignatures($completeCandidateIds, true);
            $keepPackageSignature = $completePackageSignatures->get((string) $keepId, '[]');
            $completeIds = $completeCandidateRows
                ->filter(function (object $row) use ($completePackageSignatures, $keepPackageSignature, $first): bool {
                    $id = (int) $row->delivery_id;
                    $samePackages = $completePackageSignatures->get((string) $id, '[]') === $keepPackageSignature;
                    $sameStatus = $row->status === $first->status;

                    return $samePackages && $sameStatus;
                })
                ->pluck('delivery_id')
                ->map(fn ($id): int => (int) $id)
                ->sort()->values()->all();
            $requestedIds = array_values(array_unique([$keepId, ...array_map('intval', $affectedIds)]));
            sort($requestedIds);
            if ($completeIds !== $requestedIds || count($affectedIds) !== count(array_unique($affectedIds))) {
                throw ValidationException::withMessages(['records' => 'The submitted keep and affected records do not include this complete duplicate group. Refresh the review.']);
            }

            $group = (object) [
                'project_id' => $first->project_id,
                'school_id' => $first->school_id,
                'dr_no' => $first->dr_no,
                'records' => $deliveries->all(),
            ];
            $eligibility = $this->resolutionEligibility($companyId, $group);
            if (! $eligibility['allowed']) {
                throw ValidationException::withMessages(['records' => $eligibility['reason']]);
            }
            if (! Schema::hasTable('admin_activity_logs')) {
                throw ValidationException::withMessages(['records' => 'The audit trail is unavailable, so this group cannot be changed safely.']);
            }

            $affected = $deliveries->whereIn('delivery_id', $affectedIds);
            if ($affected->contains(fn (object $row): bool => mb_strtolower((string) ($row->status ?? '')) === 'cancelled')) {
                throw ValidationException::withMessages(['records' => 'At least one selected record is already cancelled. Refresh the review.']);
            }

            $previousStatuses = [];
            foreach ($affected as $delivery) {
                $previousStatuses[(string) $delivery->delivery_id] = $delivery->status;
                DB::table('deliveries')->where('delivery_id', $delivery->delivery_id)->update(['status' => 'cancelled']);
            }

            app(AdminAuditLogger::class)->record(
                $actor,
                'duplicate_dr_cleanup',
                'delivery_group',
                $first->project_id.':'.$first->dr_no,
                'Marked confirmed duplicate delivery records as cancelled; linked records were preserved.',
                $companyId,
                [
                    'action' => 'cancel_duplicate_deliveries',
                    'project_id' => (int) $first->project_id,
                    'dr_no' => (string) $first->dr_no,
                    'keep_delivery_id' => $keepId,
                    'affected_delivery_ids' => array_values($affectedIds),
                    'previous_statuses' => $previousStatuses,
                    'timestamp' => now()->toIso8601String(),
                ],
            );
        });
    }

    private function candidateQuery(int $companyId, array $filters): Builder
    {
        $query = DB::table('deliveries as d')
            ->join('projects as p', 'p.project_id', '=', 'd.project_id')
            ->leftJoin('school as s', 's.school_id', '=', 'd.school_id')
            ->leftJoin('lot as l', 'l.lot_id', '=', 'd.lot_id');

        if (Schema::hasColumn('projects', 'company_id')) {
            $query->where(function (Builder $scope) use ($companyId): void {
                $scope->whereNull('p.company_id')->orWhere('p.company_id', $companyId);
            });
        }

        $query->whereNotNull('d.dr_no')->whereRaw("TRIM(d.dr_no) <> ''")
            ->selectRaw('d.project_id, LOWER(TRIM(d.dr_no)) as normalized_dr, MIN(d.dr_no) as dr_no, COUNT(*) as record_count')
            ->selectRaw('MAX(p.project_name) as project_name, MAX(s.school_name) as school_name, MAX(l.lot_name) as lot_name')
            ->groupBy('d.project_id')
            ->groupByRaw('LOWER(TRIM(d.dr_no))')
            ->havingRaw('COUNT(*) > 1');

        if (! empty($filters['search'])) {
            $term = '%'.addcslashes(trim($filters['search']), '%_\\').'%';
            $normalizedTerm = '%'.addcslashes(mb_strtolower(trim($filters['search'])), '%_\\').'%';
            $query->havingRaw(
                'SUM(CASE WHEN LOWER(TRIM(d.dr_no)) LIKE ? OR p.project_name LIKE ? OR s.school_name LIKE ? THEN 1 ELSE 0 END) > 0',
                [$normalizedTerm, $term, $term],
            );
        }
        if (! empty($filters['project_id'])) {
            $query->where('d.project_id', (int) $filters['project_id']);
        }
        if (! empty($filters['status'])) {
            $query->havingRaw('SUM(CASE WHEN d.status = ? THEN 1 ELSE 0 END) > 0', [$filters['status']]);
        }

        return $query->orderBy('project_name')->orderBy('dr_no');
    }

    private function hydrateGroup(int $companyId, object $group): object
    {
        $recordsQuery = DB::table('deliveries as d')
            ->join('projects as p', 'p.project_id', '=', 'd.project_id')
            ->leftJoin('school as s', 's.school_id', '=', 'd.school_id')
            ->leftJoin('lot as l', 'l.lot_id', '=', 'd.lot_id')
            ->where('d.project_id', $group->project_id)
            ->whereRaw('LOWER(TRIM(d.dr_no)) = ?', [$group->normalized_dr]);
        if (Schema::hasColumn('projects', 'company_id')) {
            $recordsQuery->where(function (Builder $scope) use ($companyId): void {
                $scope->whereNull('p.company_id')->orWhere('p.company_id', $companyId);
            });
        }
        $group->records = $recordsQuery->select('d.*', 's.school_name', 'l.lot_name')->orderBy('d.delivery_id')->get();
        $ids = $group->records->pluck('delivery_id');
        $packageLabelColumn = Schema::hasColumn('package', 'package_num') ? 'pk.package_num' : 'pk.package_code';
        $group->packages = DB::table('package_status as ps')->leftJoin('package as pk', 'pk.package_id', '=', 'ps.package_id')
            ->whereIn('ps.delivery_id', $ids)->select('ps.delivery_id', 'ps.status', 'pk.package_id')->selectRaw($packageLabelColumn.' as package_num')->orderBy('ps.delivery_id')->get();
        $packageSignatures = $this->packageSignatures($ids->map(fn ($id): int => (int) $id)->all());
        $group->exactDuplicateSets = $group->records
            ->groupBy(fn (object $record): string => $this->groupKey($record).'|'.$packageSignatures->get((string) $record->delivery_id, '[]'))
            ->filter(fn ($records): bool => $records->count() > 1)
            ->map(function ($records) use ($companyId, $group): object {
                $duplicateSet = (object) [
                    'project_id' => $group->project_id,
                    'school_id' => $records->first()->school_id,
                    'dr_no' => $records->first()->dr_no,
                    'records' => $records,
                ];
                $eligibility = $this->resolutionEligibility($companyId, $duplicateSet);
                $duplicateSet->eligible = $eligibility['allowed'];
                $duplicateSet->exclusion = $eligibility['reason'];

                return $duplicateSet;
            })
            ->values();

        return $group;
    }

    private function groupKey(object $row): string
    {
        return implode('|', [
            (string) $row->school_id,
            (string) ($row->lot_id ?? ''),
            $row->keystage_id ?? '',
            $row->delivery_date ?? '',
            mb_strtolower(trim((string) ($row->status ?? ''))),
        ]);
    }

    /** @param array<int, int> $deliveryIds
     * @return Collection<string, string>
     */
    private function packageSignatures(array $deliveryIds, bool $lock = false): Collection
    {
        if ($deliveryIds === []) {
            return collect();
        }

        $query = DB::table('package_status')->whereIn('delivery_id', $deliveryIds)
            ->orderBy('delivery_id')->orderBy('package_id')->select('delivery_id', 'package_id', 'status');
        if ($lock) {
            $query->lockForUpdate();
        }

        return $query->get()->groupBy('delivery_id')->map(function (Collection $packages): string {
            $signature = $packages->map(fn (object $package): string => implode(':', [
                (string) $package->package_id,
                mb_strtolower(trim((string) $package->status)),
            ]))->sort()->values()->all();

            return json_encode($signature, JSON_THROW_ON_ERROR);
        })->mapWithKeys(fn (string $signature, int|string $deliveryId): array => [(string) $deliveryId => $signature]);
    }
}
