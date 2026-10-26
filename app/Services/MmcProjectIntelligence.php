<?php

namespace App\Services;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

class MmcProjectIntelligence
{
    public function __construct(private ProjectDeliveryProgressService $deliveryProgress) {}

    /** @return array<string, mixed> */
    public function resolve(string $reference, int|string|null $companyId = null): array
    {
        $reference = trim($reference);
        if ($reference === '' || mb_strlen($reference) > 120) {
            return ['status' => 'not_found', 'reference' => $reference, 'candidates' => []];
        }

        $query = DB::table('projects as p')->select(['p.project_id', 'p.project_code', 'p.ref_no', 'p.project_name']);
        if (Schema::hasColumn('projects', 'company_id')) {
            $query->addSelect('p.company_id');
            if ($companyId !== null) {
                $query->where('p.company_id', $companyId);
            }
        }

        $biddingColumns = [];
        if (Schema::hasTable('project_information')) {
            $hasExplicitForeignKey = Schema::hasColumn('projects', 'project_information_id');
            $usesExplicitLink = $hasExplicitForeignKey && Schema::hasColumn('project_information', 'id');
            $usesLegacyLink = ! $hasExplicitForeignKey && Schema::hasColumn('project_information', 'project_id');
            if ($usesExplicitLink || $usesLegacyLink) {
                $query->leftJoin('project_information as pi', $usesExplicitLink ? 'pi.id' : 'pi.project_id', '=', $usesExplicitLink ? 'p.project_information_id' : 'p.project_id');
                if (Schema::hasColumn('project_information', 'id')) {
                    $query->addSelect('pi.id as bidding_id');
                }
                foreach (['project_code', 'reference_no', 'project_name'] as $column) {
                    if (Schema::hasColumn('project_information', $column)) {
                        $query->addSelect('pi.'.$column.' as bidding_'.$column);
                        $biddingColumns[] = 'bidding_'.$column;
                    }
                }
                if ($companyId !== null && Schema::hasColumn('project_information', 'company_id')) {
                    $query->where(function (Builder $builder) use ($companyId): void {
                        $builder->whereNull('pi.company_id')->orWhere('pi.company_id', $companyId);
                    });
                }
            }
        }

        $records = $query->limit(5000)->get();
        $needle = self::normalizeReference($reference);
        $matches = [];
        foreach ($records as $record) {
            $values = [];
            foreach (['project_code', 'ref_no', 'project_name', ...$biddingColumns] as $column) {
                if (! empty($record->{$column})) {
                    $values[$column] = (string) $record->{$column};
                }
            }

            $score = 0;
            $method = null;
            foreach ($values as $column => $value) {
                $normalizedValue = self::normalizeReference($value);
                $candidateScore = $normalizedValue === $needle ? ($column === 'project_code' ? 100 : ($column === 'bidding_project_code' ? 95 : 90)) : 0;
                if ($candidateScore === 0 && preg_match('/\b'.preg_quote($reference, '/').'\b/iu', $value)) {
                    $candidateScore = in_array($column, ['project_name', 'bidding_project_name'], true) ? 84 : 80;
                }
                if ($candidateScore === 0 && in_array($column, ['project_name', 'bidding_project_name'], true)
                    && self::containsProjectAlias($value, $needle)) {
                    $candidateScore = 84;
                }
                if ($candidateScore > $score) {
                    $score = $candidateScore;
                    $method = str_starts_with($column, 'bidding_') ? 'linked_bidding_'.$column : $column;
                }
            }

            if ($score === 0 && preg_match('/^\s*[\(\[]?\s*([\pL]+[\s-]*\d+)\s*[\)\]]?/u', (string) $record->project_name, $alias)
                && self::normalizeReference($alias[1]) === $needle) {
                $score = 84;
                $method = 'project_name_alias';
            }

            if ($score >= 75) {
                $matches[] = ['record' => $record, 'score' => $score, 'method' => $method];
            }
        }

        usort($matches, fn (array $left, array $right): int => $right['score'] <=> $left['score']);
        if ($matches === []) {
            return ['status' => 'not_found', 'reference' => $reference, 'candidates' => []];
        }

        $bestScore = $matches[0]['score'];
        $topMatches = array_values(array_filter($matches, fn (array $match): bool => $match['score'] === $bestScore));
        $candidateView = fn (array $match): array => [
            'project_id' => (int) $match['record']->project_id,
            'project_code' => $match['record']->project_code,
            'project_name' => $match['record']->project_name,
            'bidding_id' => $match['record']->bidding_id ?? null,
            'bidding_title' => $match['record']->bidding_project_name ?? null,
            'company_id' => $match['record']->company_id ?? null,
            'match_method' => $match['method'],
            'confidence' => $match['score'] / 100,
        ];

        if (count($topMatches) > 1) {
            return ['status' => 'ambiguous', 'reference' => $reference, 'candidates' => array_map($candidateView, array_slice($topMatches, 0, 5))];
        }

        return ['status' => 'found', 'reference' => $reference, 'project' => $candidateView($topMatches[0])];
    }

    /** @return array<string, mixed> */
    public function deliverySummary(string $reference, int|string|null $companyId = null, array $filters = []): array
    {
        $resolved = $this->resolve($reference, $companyId);
        if (($resolved['status'] ?? null) !== 'found') {
            return $resolved;
        }

        $project = $resolved['project'];
        $allowedFilters = array_intersect_key($filters, array_flip(['year', 'date_from', 'date_to']));
        try {
            $metrics = $this->deliveryProgress->deliveryMetrics($allowedFilters + ['project_id' => $project['project_id']]);
        } catch (Throwable) {
            return [
                'status' => 'data_unavailable',
                'project' => $project,
                'message' => 'Delivery records could not be retrieved right now. Please try again later.',
            ];
        }
        if ($metrics === null) {
            $metrics = (object) array_fill_keys([
                'total_deliveries_count', 'completed_deliveries_count', 'delivery_rows_count', 'pending_deliveries_count',
                'released_deliveries_count', 'delivered_deliveries_count', 'accepted_deliveries_count', 'warehouse_deliveries_count',
                'mixed_deliveries_count', 'cancelled_deliveries_count', 'for_approval_deliveries_count',
            ], 0);
        }

        $schoolQuery = DB::table('deliveries')->where('project_id', $project['project_id'])
            ->whereIn('status', ['delivered', 'accepted'])->whereNotNull('school_id');
        $linkedRowsQuery = DB::table('deliveries')->where('project_id', $project['project_id']);
        if (isset($allowedFilters['year'])) {
            $schoolQuery->whereYear('delivery_date', $allowedFilters['year']);
            $linkedRowsQuery->whereYear('delivery_date', $allowedFilters['year']);
        }
        if (isset($allowedFilters['date_from'])) {
            $schoolQuery->whereDate('delivery_date', '>=', $allowedFilters['date_from']);
            $linkedRowsQuery->whereDate('delivery_date', '>=', $allowedFilters['date_from']);
        }
        if (isset($allowedFilters['date_to'])) {
            $schoolQuery->whereDate('delivery_date', '<=', $allowedFilters['date_to']);
            $linkedRowsQuery->whereDate('delivery_date', '<=', $allowedFilters['date_to']);
        }
        $linkedDeliveryRowsCount = $linkedRowsQuery->count();

        return [
            'status' => 'found',
            'project' => $project,
            'period' => $allowedFilters === [] ? 'all time' : $allowedFilters,
            'counting_unit' => 'distinct valid delivery receipt (project + DR number)',
            'delivery_rows_count' => (int) $metrics->delivery_rows_count,
            'total_receipts_count' => (int) $metrics->total_deliveries_count,
            'delivered_receipts_count' => (int) $metrics->completed_deliveries_count,
            'unique_schools_with_delivered_or_accepted_rows' => (int) $schoolQuery->distinct()->count('school_id'),
            'linked_delivery_records_count' => $linkedDeliveryRowsCount,
            'pending_receipts_count' => (int) $metrics->pending_deliveries_count,
            'released_receipts_count' => (int) $metrics->released_deliveries_count,
            'in_progress_receipts_count' => (int) ($metrics->mixed_deliveries_count + $metrics->released_deliveries_count),
            'other_receipt_status_counts' => [
                'delivered' => (int) $metrics->delivered_deliveries_count,
                'accepted' => (int) $metrics->accepted_deliveries_count,
                'warehouse' => (int) $metrics->warehouse_deliveries_count,
                'mixed' => (int) $metrics->mixed_deliveries_count,
                'cancelled' => (int) $metrics->cancelled_deliveries_count,
                'for_approval' => (int) $metrics->for_approval_deliveries_count,
            ],
            'completion_percentage' => (int) $metrics->total_deliveries_count > 0 ? round((int) $metrics->completed_deliveries_count / (int) $metrics->total_deliveries_count * 100, 2) : null,
            'has_delivery_records' => $linkedDeliveryRowsCount > 0,
            'definition' => 'Delivered means the receipt has all expected packages delivered or accepted; if no package definitions exist, all receipt rows must be delivered or accepted. Blank DR numbers and invalid/cancelled receipts are excluded. Delivery rows are all linked delivery records, while valid receipt rows exclude blank DRs and invalid receipts. Unique schools counts schools with at least one delivery row whose row status is delivered or accepted.',
        ];
    }

    public static function normalizeReference(string $reference): string
    {
        return mb_strtolower((string) preg_replace('/[^\pL\pN]+/u', '', trim($reference)));
    }

    private static function containsProjectAlias(string $value, string $needle): bool
    {
        preg_match_all('/[\pL]+(?:[\s-]*\d+)?/u', $value, $aliases);

        foreach ($aliases[0] as $alias) {
            if (self::normalizeReference($alias) === $needle) {
                return true;
            }
        }

        return false;
    }
}
