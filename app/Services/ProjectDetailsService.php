<?php

namespace App\Services;

use App\Models\Project;
use Illuminate\Database\Query\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ProjectDetailsService
{
    /** @var array<string, list<string>> */
    private array $columns = [];

    public function schools(Project $project): Builder
    {
        return DB::table('school as s')->where(function (Builder $query) use ($project): void {
            $query->where('s.project_id', $project->project_id)
                ->orWhereIn('s.school_id', DB::table('deliveries')->where('project_id', $project->project_id)->select('school_id'));
        });
    }

    public function lots(Project $project): Builder
    {
        return DB::table('lot as l')->where('l.project_id', $project->project_id);
    }

    public function keystages(Project $project): Builder
    {
        return DB::table('keystage as k')->join('lot as l', 'l.lot_id', '=', 'k.lot_id')
            ->where('l.project_id', $project->project_id);
    }

    public function items(Project $project): Builder
    {
        return DB::table('item')->where('project_id', $project->project_id);
    }

    public function packages(Project $project): Builder
    {
        return DB::table('package as p')
            ->leftJoin('lot as l', 'l.lot_id', '=', 'p.lot_id')
            ->leftJoin('keystage as k', 'k.keystage_id', '=', 'p.keystage_id')
            ->leftJoin('lot as keystage_lot', 'keystage_lot.lot_id', '=', 'k.lot_id')
            ->where(function (Builder $query) use ($project): void {
                $query->where('l.project_id', $project->project_id)->orWhere('keystage_lot.project_id', $project->project_id);
            });
    }

    /** @return array<string, int> */
    public function summary(Project $project): array
    {
        $schools = $this->schools($project)->selectRaw("COUNT(*) as total, COUNT(DISTINCT NULLIF(region, '')) as regions, COUNT(DISTINCT NULLIF(division, '')) as divisions, COUNT(DISTINCT NULLIF(municipality, '')) as municipalities")->first();

        return [
            'schoolCount' => (int) $schools->total,
            'regionCount' => (int) $schools->regions,
            'divisionCount' => (int) $schools->divisions,
            'municipalityCount' => (int) $schools->municipalities,
            'lotCount' => $this->lots($project)->count(),
            'keystageCount' => $this->keystages($project)->count(),
            'itemCount' => $this->items($project)->count(),
            'packageCount' => $this->packages($project)->count(),
        ];
    }

    public function lotRecords(Project $project): Builder
    {
        return $this->lots($project)->select('l.*')
            ->selectSub(function (Builder $query): void {
                $query->from('package as p')->leftJoin('keystage as k', 'k.keystage_id', '=', 'p.keystage_id')
                    ->where(function (Builder $query): void {
                        $query->whereColumn('p.lot_id', 'l.lot_id')->orWhereColumn('k.lot_id', 'l.lot_id');
                    })->selectRaw('count(*)');
            }, 'packages_count')
            ->selectSub(DB::table('keystage as k')->whereColumn('k.lot_id', 'l.lot_id')->selectRaw('count(*)'), 'keystages_count')
            ->orderBy('l.lot_name')->orderBy('l.lot_id');
    }

    public function keystageRecords(Project $project): Builder
    {
        return $this->keystages($project)->select('k.*', 'l.lot_name')->orderBy('l.lot_name')->orderBy('k.keystage_num')->orderBy('k.keystage_id');
    }

    public function attachKeystages(Collection $lots, Collection $keystages): void
    {
        $groups = $keystages->groupBy('lot_id');
        $lots->each(function (object $lot) use ($groups): void {
            $lot->keystages = $groups->get($lot->lot_id, collect());
        });
    }

    /** @param array<string, mixed> $filters */
    public function records(Project $project, string $section, array $filters): LengthAwarePaginator
    {
        $query = match ($section) {
            'schools' => $this->schools($project)->orderBy('s.school_name')->orderBy('s.school_id'),
            'items' => $this->items($project)->orderBy('item_name')->orderBy('item_id'),
            'packages' => $this->packages($project)->select('p.*', 'l.lot_name', 'k.keystage_num', 'k.description as keystage_description')->orderBy('p.package_num')->orderBy('p.package_id'),
            'lots' => $this->lotRecords($project),
            'keystage' => $this->keystageRecords($project),
        };

        $columns = match ($section) {
            'schools' => ['s.school_id', 's.school_name', 's.address', 's.municipality', 's.division', 's.region'],
            'items' => $this->searchColumns('item', ['item_id', 'item_name', 'description', 'item_description', 'item_type', 'type']),
            'packages' => $this->searchColumns('package', ['package_id', 'package_num', 'package_code', 'code', 'package_type', 'type'], 'p.'),
            'lots' => ['l.lot_name', 'l.contract_no'],
            'keystage' => ['k.keystage_num', 'k.description', 'l.lot_name'],
        };
        if (! empty($filters['search'])) {
            $query->where(function (Builder $query) use ($columns, $filters, $section): void {
                foreach ($columns as $column) {
                    $query->orWhere($column, 'like', '%'.$filters['search'].'%');
                }
                if ($section === 'packages') {
                    $expression = DB::connection()->getDriverName() === 'sqlite' ? "('Package ' || p.package_num)" : "CONCAT('Package ', p.package_num)";
                    $query->orWhereRaw($expression.' LIKE ?', ['%'.$filters['search'].'%']);
                }
            });
        }
        if ($section === 'schools') {
            foreach (['region', 'division', 'municipality'] as $field) {
                if (! empty($filters[$field])) {
                    $query->where('s.'.$field, $filters[$field]);
                }
            }
        }
        if (in_array($section, ['items', 'packages'], true)) {
            $field = $section === 'items' ? 'item_type' : 'package_type';
            if (! empty($filters[$field])) {
                $column = $this->typeColumn($section === 'items' ? 'item' : 'package');
                $column ? $query->where(($section === 'packages' ? 'p.' : '').$column, $filters[$field]) : $query->whereRaw('1 = 0');
            }
        }
        if (! empty($filters['lot'])) {
            if ($section === 'packages') {
                $query->where(function (Builder $query) use ($filters): void {
                    $query->where('p.lot_id', $filters['lot'])->orWhere('k.lot_id', $filters['lot']);
                });
            } elseif ($section === 'keystage') {
                $query->where('k.lot_id', $filters['lot']);
            }
        }
        if ($section === 'packages' && ! empty($filters['keystage'])) {
            $query->where('p.keystage_id', $filters['keystage']);
        }

        $records = $query->paginate((int) ($filters['per_page'] ?? 25), ['*'], 'page', (int) ($filters['page'] ?? 1));
        if ($section === 'packages') {
            $records->getCollection()->each(function (object $package): void {
                $package->package_name = 'Package '.$package->package_num;
            });
        }
        if ($section === 'lots') {
            $keystages = $this->keystages($project)->count() <= 100
                ? $this->keystageRecords($project)->whereIn('k.lot_id', $records->pluck('lot_id'))->limit(100)->get() : collect();
            $this->attachKeystages($records->getCollection(), $keystages);
        }

        return $records;
    }

    /** @param array<string, mixed> $filters
     * @return array<string, mixed>
     */
    public function options(Project $project, string $section, array $filters): array
    {
        if ($section === 'schools') {
            $level = $filters['level'] ?? 'region';
            $query = $this->schools($project);
            if ($level !== 'region' && ! empty($filters['region'])) {
                $query->where('s.region', $filters['region']);
            }
            if ($level === 'municipality' && ! empty($filters['division'])) {
                $query->where('s.division', $filters['division']);
            }

            return ['options' => $query->whereNotNull('s.'.$level)->where('s.'.$level, '<>', '')->distinct()->orderBy('s.'.$level)->pluck('s.'.$level)];
        }
        if ($section === 'keystage' || ($section === 'packages' && ($filters['level'] ?? '') === 'lot')) {
            $options = $this->lots($project)->orderBy('l.lot_name')->orderBy('l.lot_id')
                ->simplePaginate(100, ['l.lot_id as value', 'l.lot_name as label'], 'page', (int) ($filters['page'] ?? 1));

            return ['options' => $options->items(), 'next_page' => $options->hasMorePages() ? $options->currentPage() + 1 : null];
        }
        if ($section === 'packages' && ($filters['level'] ?? '') === 'keystage') {
            $query = $this->keystageRecords($project);
            if (! empty($filters['lot'])) {
                $query->where('k.lot_id', $filters['lot']);
            }

            $options = $query->simplePaginate(100, ['*'], 'page', (int) ($filters['page'] ?? 1));

            return ['options' => $options->getCollection()->map(fn (object $record): array => ['value' => $record->keystage_id, 'label' => $record->lot_name.' / Keystage '.$record->keystage_num]), 'next_page' => $options->hasMorePages() ? $options->currentPage() + 1 : null];
        }
        $table = $section === 'items' ? 'item' : 'package';
        $column = $this->typeColumn($table);
        if (! $column) {
            return ['options' => []];
        }
        $query = $section === 'items' ? $this->items($project) : $this->packages($project);
        $column = ($section === 'packages' ? 'p.' : '').$column;

        return ['options' => $query->whereNotNull($column)->where($column, '<>', '')->distinct()->orderBy($column)->pluck($column)];
    }

    private function typeColumn(string $table): ?string
    {
        $columns = $this->columns[$table] ??= Schema::getColumnListing($table);
        foreach ([$table.'_type', 'type'] as $column) {
            if (in_array($column, $columns, true)) {
                return $column;
            }
        }

        return null;
    }

    /** @param list<string> $candidates
     * @return list<string>
     */
    private function searchColumns(string $table, array $candidates, string $prefix = ''): array
    {
        return array_map(fn (string $column): string => $prefix.$column, array_values(array_intersect($candidates, $this->columns[$table] ??= Schema::getColumnListing($table))));
    }
}
