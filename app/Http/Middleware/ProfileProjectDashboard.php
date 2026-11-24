<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class ProfileProjectDashboard
{
    private int $queries = 0;

    private float $databaseMs = 0;

    private int $rows = 0;

    private array $sections = [];

    private array $context = [];

    private array $slowest = [];

    public function handle(Request $request, Closure $next): Response
    {
        if (! config('app.dashboard_profile') || ! $request->isMethod('GET') || ! $request->routeIs('projects.dashboard') || ! $request->user()) {
            return $next($request);
        }

        $this->context = ['correlation_id' => (string) Str::uuid(), 'route' => 'projects.dashboard'];
        $request->attributes->set(self::class, $this);
        $started = hrtime(true);
        Log::info('dashboard_profile.started', $this->context);
        DB::listen(function (QueryExecuted $query) use ($request): void {
            if ($request->attributes->get(self::class) !== $this) {
                return;
            }

            $this->queries++;
            $this->databaseMs += $query->time;
            if ($query->time >= 5 && preg_match('/^\s*select\b/i', $query->sql)) {
                $pattern = <<<'REGEX'
~'(?:''|\\.|[^'\\])*'|"(?:\"\"|\\.|[^"\\])*"|/\*.*?\*/|--[^\r\n]*|\#[^\r\n]*|\b(?:0x[0-9a-f]+|[0-9]+(?:\.[0-9]+)?)\b~is
REGEX;
                $sql = preg_replace($pattern, '?', $query->sql);
                if ($sql !== null) {
                    $sql = preg_replace('/\?(?:\s*,\s*\?)++/', '?…', $sql) ?? $sql;
                }
                Log::info('dashboard_profile.query', $this->context + [
                    'section' => array_key_last($this->sections),
                    'query_number' => $this->queries,
                    'duration_ms' => $query->time,
                    'sql' => substr(preg_replace('/\s+/', ' ', $sql ?? '') ?? '', 0, 16000),
                ]);
                if ($query->time >= 100 && in_array($query->connection->getDriverName(), ['mysql', 'mariadb'], true)) {
                    $this->slowest[] = ['query' => $query, 'number' => $this->queries];
                    usort($this->slowest, fn (array $left, array $right): int => $right['query']->time <=> $left['query']->time);
                    $this->slowest = array_slice($this->slowest, 0, 5);
                }
            }
        });

        try {
            return $next($request);
        } finally {
            foreach (array_reverse(array_keys($this->sections)) as $section) {
                $this->finish($section);
            }
            $request->attributes->remove(self::class);
            $request->attributes->set(self::class.'.plans', [$this->context, $this->slowest]);
            Log::info('dashboard_profile.request', $this->context + [
                'duration_ms' => round(defined('LARAVEL_START') ? (microtime(true) - LARAVEL_START) * 1000 : (hrtime(true) - $started) / 1e6, 2),
                'query_count' => $this->queries,
                'database_ms' => round($this->databaseMs, 2),
                'tracked_rows_loaded' => $this->rows,
                'peak_memory_bytes' => memory_get_peak_usage(true),
            ]);
        }
    }

    public function terminate(Request $request, Response $response): void
    {
        [$context, $slowest] = $request->attributes->get(self::class.'.plans', [[], []]);
        $request->attributes->remove(self::class.'.plans');
        foreach ($slowest as $entry) {
            $query = $entry['query'];
            try {
                $plan = $query->connection->select('EXPLAIN '.$query->sql, $query->bindings);
                $steps = array_map(static fn (object $step): array => array_intersect_key((array) $step, array_flip([
                    'id', 'select_type', 'table', 'type', 'possible_keys', 'key', 'key_len', 'rows', 'Extra',
                ])), $plan);
                Log::info('dashboard_profile.explain', $context + ['query_number' => $entry['number'], 'plan' => $steps]);
            } catch (Throwable) {
                Log::info('dashboard_profile.explain_failed', $context + ['query_number' => $entry['number']]);
            }
        }
    }

    public static function current(): ?self
    {
        return request()->attributes->get(self::class);
    }

    public function start(string $section): void
    {
        $this->sections[$section] = [hrtime(true), $this->queries, $this->databaseMs, $this->rows];
    }

    public function loaded(int $rows): void
    {
        $this->rows += $rows;
    }

    public function finish(string $section, array $extra = []): void
    {
        [$started, $queries, $databaseMs, $rows] = $this->sections[$section];
        unset($this->sections[$section]);
        Log::info('dashboard_profile.section', $this->context + [
            'section' => $section,
            'duration_ms' => round((hrtime(true) - $started) / 1e6, 2),
            'query_count' => $this->queries - $queries,
            'database_ms' => round($this->databaseMs - $databaseMs, 2),
            'tracked_rows_loaded' => $this->rows - $rows,
            'peak_memory_bytes' => memory_get_peak_usage(true),
        ] + $extra);
    }
}
