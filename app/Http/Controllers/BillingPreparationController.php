<?php

namespace App\Http\Controllers;

use App\Exceptions\BillingBatchEligibilityException;
use App\Exceptions\ExpiredBillingQrCodeException;
use App\Exceptions\InvalidBillingQrCodeException;
use App\Models\User;
use App\Services\BillingBatchService;
use App\Services\DeliveryBillingEligibilityService;
use App\Services\DeliveryBillingQrCodeService;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class BillingPreparationController extends Controller
{
    public function __construct(
        private DeliveryBillingEligibilityService $eligibility,
        private DeliveryBillingQrCodeService $qrCodes,
        private BillingBatchService $batches
    ) {}

    public function index(Request $request): View|RedirectResponse
    {
        $filters = $this->validatedFilters($request);
        if (isset($filters['year']) && ! DB::table('deliveries')->whereYear('delivery_date', $filters['year'])->exists()) {
            $query = $request->query();
            unset($query['year'], $query['project_id'], $query['page']);

            return redirect()->route('billing.prepare', $query);
        }
        if (isset($filters['project_id']) && (! isset($filters['year']) || ! $this->projectMatchesYear($filters['project_id'], $filters['year']))) {
            $query = $request->query();
            unset($query['project_id'], $query['page']);

            return redirect()->route('billing.prepare', $query);
        }

        $years = DB::table('deliveries')
            ->selectRaw('DISTINCT SUBSTR(delivery_date, 1, 4) as year')
            ->orderByDesc('year')
            ->pluck('year');
        $projects = isset($filters['year'])
            ? DB::table('projects as p')
                ->whereExists(function (Builder $deliveries) use ($filters): void {
                    $deliveries->selectRaw('1')
                        ->from('deliveries as d')
                        ->whereColumn('d.project_id', 'p.project_id')
                        ->whereYear('d.delivery_date', $filters['year']);
                })
                ->orderBy('p.project_name')
                ->get(['p.project_id', 'p.project_name', 'p.project_code', 'p.ref_no'])
            : collect();
        $lots = DB::table('lot')
            ->when(isset($filters['project_id']), fn (Builder $query) => $query->where('project_id', $filters['project_id']))
            ->orderBy('lot_name')
            ->get(['lot_id', 'project_id', 'lot_name']);
        $divisions = DB::table('school')->whereNotNull('division')->distinct()->orderBy('division')->pluck('division');
        $schools = DB::table('school')->orderBy('school_name')->get(['school_id', 'school_name']);

        return view('billing.prepare.index', compact('filters', 'years', 'projects', 'lots', 'divisions', 'schools'));
    }

    public function scanLookup(Request $request): JsonResponse
    {
        $validated = $request->validate(['q' => ['required', 'string', 'max:2048']]);

        try {
            $target = $this->qrCodes->resolveTarget($validated['q']);
        } catch (ExpiredBillingQrCodeException|InvalidBillingQrCodeException $exception) {
            return response()->json(['message' => $exception->getMessage()], $exception instanceof ExpiredBillingQrCodeException ? 410 : 422);
        }

        if ($target === null) {
            return response()->json(['message' => 'This QR code does not identify a delivery.'], 422);
        }

        $query = $this->eligibility->query();
        if (isset($target['delivery_id'])) {
            $query->where('d.delivery_id', $target['delivery_id']);
        } else {
            $query->where('d.dr_no', $target['dr_no']);
        }

        $rows = $query->orderBy('d.delivery_id')->limit($this->maxBatchSize() + 1)->get();
        if ($rows->count() > $this->maxBatchSize()) {
            return response()->json(['message' => 'This DR number matches more records than one review can hold. Search by DR number, project, or school to narrow the results.'], 422);
        }
        if ($rows->isEmpty()) {
            return response()->json(['message' => 'No delivery record matches this QR code.'], 404);
        }

        return response()->json([
            'records' => $rows->map(fn (object $row): array => $this->eligibility->present($row))->values(),
            'message' => $rows->count() > 1 ? 'Multiple delivery rows share this DR number. Select the correct school and project.' : 'Delivery record found.',
        ]);
    }

    public function search(Request $request): JsonResponse
    {
        $filters = $this->validatedFilters($request);
        if (isset($filters['project_id']) && (! isset($filters['year']) || ! $this->projectMatchesYear($filters['project_id'], $filters['year']))) {
            return response()->json(['message' => 'Choose a Year before searching within a Project.'], 422);
        }
        if (empty($filters['q'])) {
            return response()->json(['message' => 'Enter a DR number, school, project, or lot to search.'], 422);
        }

        $perPage = min(max($request->integer('per_page', 25), 10), 50);
        $records = $this->eligibility->query($filters)
            ->orderByDesc('d.delivery_date')
            ->orderByDesc('d.delivery_id')
            ->paginate($perPage)
            ->withQueryString();

        return response()->json([
            'records' => collect($records->items())->map(fn (object $row): array => $this->eligibility->present($row))->values(),
            'pagination' => ['current_page' => $records->currentPage(), 'last_page' => $records->lastPage(), 'total' => $records->total()],
        ]);
    }

    public function range(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'project_id' => ['required', 'integer', 'exists:projects,project_id'],
            'dr_from' => ['required', 'regex:/^[0-9]{1,18}$/'],
            'dr_to' => ['required', 'regex:/^[0-9]{1,18}$/'],
            'year' => ['nullable', 'digits:4'],
            'lot_id' => ['nullable', 'integer', 'exists:lot,lot_id'],
            'division' => ['nullable', 'string', 'max:255', 'exists:school,division'],
            'school_id' => ['nullable', 'string', 'max:255', 'exists:school,school_id'],
            'date_from' => ['nullable', 'date_format:Y-m-d'],
            'date_to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:date_from'],
            'q' => ['nullable', 'string', 'max:100'],
        ]);

        if ($this->compareNumericStrings($validated['dr_from'], $validated['dr_to']) > 0) {
            return response()->json(['message' => 'DR Number From must be less than or equal to DR Number To.'], 422);
        }
        if (! isset($validated['year']) || ! $this->projectMatchesYear((int) $validated['project_id'], (int) $validated['year'])) {
            return response()->json(['message' => 'Choose a project associated with the selected year.'], 422);
        }

        $filters = array_filter([
            'project_id' => (int) $validated['project_id'],
            'year' => isset($validated['year']) ? (int) $validated['year'] : null,
            'lot_id' => isset($validated['lot_id']) ? (int) $validated['lot_id'] : null,
            'division' => $validated['division'] ?? null,
            'school_id' => $validated['school_id'] ?? null,
            'date_from' => $validated['date_from'] ?? null,
            'date_to' => $validated['date_to'] ?? null,
            'q' => $validated['q'] ?? null,
        ], static fn (mixed $value): bool => $value !== null && $value !== '');
        $query = $this->rangeQuery($filters, $validated['dr_from'], $validated['dr_to']);

        return $this->boundedPreview($query, 'range');
    }

    public function allDelivered(Request $request): JsonResponse
    {
        $filters = $this->validatedFilters($request);
        if (! $this->hasScope($filters)) {
            return response()->json(['message' => 'Choose at least one delivery scope filter before selecting all eligible deliveries.'], 422);
        }
        if (isset($filters['project_id']) && (! isset($filters['year']) || ! $this->projectMatchesYear($filters['project_id'], $filters['year']))) {
            return response()->json(['message' => 'Choose a project associated with the selected year.'], 422);
        }

        return $this->boundedPreview($this->eligibility->query($filters)->orderBy('d.delivery_id'), 'all');
    }

    public function confirm(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'mode' => ['required', 'in:scan,search,range,all,mixed'],
            'delivery_ids' => ['required_unless:mode,all', 'array', 'min:1', 'max:'.$this->maxBatchSize()],
            'delivery_ids.*' => ['required', 'integer', 'distinct:strict', 'exists:deliveries,delivery_id'],
            'idempotency_key' => ['required', 'uuid'],
            'excluded_delivery_ids' => ['sometimes', 'array', 'max:'.$this->maxBatchSize()],
            'excluded_delivery_ids.*' => ['integer', 'distinct:strict', 'exists:deliveries,delivery_id'],
            'expected_delivery_ids' => ['required_if:mode,all', 'array', 'min:1', 'max:'.$this->maxBatchSize()],
            'expected_delivery_ids.*' => ['integer', 'distinct:strict', 'exists:deliveries,delivery_id'],
        ]);
        $actor = $request->user();
        abort_unless($actor instanceof User, 401);
        $filters = null;
        $selectionFilters = null;
        $deliveryIds = array_map('intval', $validated['delivery_ids'] ?? []);

        if ($validated['mode'] === 'range') {
            $selectionFilters = $request->validate([
                'selection_filters' => ['required', 'array'],
                'selection_filters.project_id' => ['required', 'integer', 'exists:projects,project_id'],
                'selection_filters.year' => ['required', 'digits:4'],
                'selection_filters.dr_from' => ['required', 'regex:/^[0-9]{1,18}$/'],
                'selection_filters.dr_to' => ['required', 'regex:/^[0-9]{1,18}$/'],
                'selection_filters.lot_id' => ['nullable', 'integer', 'exists:lot,lot_id'],
                'selection_filters.division' => ['nullable', 'string', 'max:255', 'exists:school,division'],
                'selection_filters.school_id' => ['nullable', 'string', 'max:255', 'exists:school,school_id'],
                'selection_filters.date_from' => ['nullable', 'date_format:Y-m-d'],
                'selection_filters.date_to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:selection_filters.date_from'],
                'selection_filters.q' => ['nullable', 'string', 'max:100'],
            ])['selection_filters'];
            if ($this->compareNumericStrings($selectionFilters['dr_from'], $selectionFilters['dr_to']) > 0) {
                return response()->json(['message' => 'The saved DR range is invalid. Preview the range again before confirming.'], 422);
            }

            $rangeFilters = [
                'project_id' => (int) $selectionFilters['project_id'],
                'year' => (int) $selectionFilters['year'],
            ];
            foreach (['lot_id', 'division', 'school_id', 'date_from', 'date_to', 'q'] as $optionalFilter) {
                if (isset($selectionFilters[$optionalFilter]) && $selectionFilters[$optionalFilter] !== '') {
                    $rangeFilters[$optionalFilter] = $selectionFilters[$optionalFilter];
                }
            }
            $rangeDeliveryIds = $this->rangeQuery($rangeFilters, $selectionFilters['dr_from'], $selectionFilters['dr_to'])
                ->pluck('d.delivery_id')->map(fn (mixed $id): int => (int) $id)->all();
            if (! $this->projectMatchesYear($rangeFilters['project_id'], $rangeFilters['year']) || array_diff($deliveryIds, $rangeDeliveryIds) !== []) {
                return response()->json(['message' => 'The selected deliveries do not match the saved DR range. Preview the range again before confirming.'], 422);
            }
        }

        if ($validated['mode'] === 'all') {
            $filters = $this->validatedFilters($request);
            if (! $this->hasScope($filters)) {
                return response()->json(['message' => 'A scoped filter is required for Select All.'], 422);
            }
            if (isset($filters['project_id']) && (! isset($filters['year']) || ! $this->projectMatchesYear($filters['project_id'], $filters['year']))) {
                return response()->json(['message' => 'Choose a project associated with the selected year.'], 422);
            }

            $excludedIds = array_map('intval', $validated['excluded_delivery_ids'] ?? []);
            $eligibleIds = $this->eligibility->query($filters)
                ->whereIn('d.status', ['delivered', 'accepted'])
                ->where(function (Builder $query): void {
                    $query->whereNull('billing.status')->orWhere('billing.status', 'pending_billing');
                })
                ->orderBy('d.delivery_id')
                ->pluck('d.delivery_id')
                ->map(fn (mixed $id): int => (int) $id)
                ->reject(fn (int $id): bool => in_array($id, $excludedIds, true))
                ->values()
                ->all();
            $expectedIds = array_values(array_unique(array_map('intval', $validated['expected_delivery_ids'])));
            sort($eligibleIds);
            sort($expectedIds);

            if ($eligibleIds !== $expectedIds) {
                return response()->json(['message' => 'The eligible delivery list changed after preview. Refresh the preview before confirming.'], 409);
            }
            if (count($eligibleIds) > $this->maxBatchSize()) {
                return response()->json(['message' => 'The eligible selection exceeds the configured batch limit. Narrow the filters and preview again.'], 422);
            }
            $deliveryIds = $eligibleIds;
            $selectionFilters = $filters;
        }

        try {
            $result = $this->batches->confirm($deliveryIds, $validated['mode'], $validated['idempotency_key'], $actor, $selectionFilters);
        } catch (BillingBatchEligibilityException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
                'rejected' => $exception->rejectedRecords,
            ], $exception->httpStatus);
        }

        return response()->json([
            'message' => $result['created'] ? 'Deliveries confirmed for Billing.' : 'This selection was already confirmed.',
            'batch' => [
                'id' => $result['batch']->getKey(),
                'batch_ref' => $result['batch']->batch_ref,
                'status' => $result['batch']->status,
                'delivery_count' => count($deliveryIds),
                'url' => route('billing.batches.show', $result['batch']),
            ],
        ], $result['created'] ? 201 : 200);
    }

    /** @return array<string, mixed> */
    private function validatedFilters(Request $request): array
    {
        $validated = $request->validate([
            'year' => ['nullable', 'digits:4'],
            'project_id' => ['nullable', 'integer', 'exists:projects,project_id'],
            'lot_id' => ['nullable', 'integer', 'exists:lot,lot_id'],
            'division' => ['nullable', 'string', 'max:255', 'exists:school,division'],
            'school_id' => ['nullable', 'string', 'max:255', 'exists:school,school_id'],
            'date_from' => ['nullable', 'date_format:Y-m-d'],
            'date_to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:date_from'],
            'q' => ['nullable', 'string', 'max:100'],
        ]);

        return array_filter($validated, static fn (mixed $value): bool => $value !== null && $value !== '');
    }

    /** @param array<string, mixed> $filters */
    private function hasScope(array $filters): bool
    {
        foreach (['year', 'project_id', 'lot_id', 'division', 'school_id', 'date_from', 'date_to'] as $filter) {
            if (isset($filters[$filter])) {
                return true;
            }
        }

        return false;
    }

    private function projectMatchesYear(int $projectId, ?int $year): bool
    {
        $query = DB::table('deliveries')->where('project_id', $projectId);
        if ($year !== null) {
            $query->whereYear('delivery_date', $year);
        }

        return $query->exists();
    }

    /** @param array<string, mixed> $filters */
    private function rangeQuery(array $filters, string $from, string $to): Builder
    {
        $query = $this->eligibility->query($filters);
        $driver = DB::connection()->getDriverName();
        if ($driver === 'mysql') {
            $expression = "COALESCE(NULLIF(TRIM(LEADING '0' FROM d.dr_no), ''), '0')";
            $query->whereRaw("d.dr_no REGEXP '^[0-9]{1,18}$'");
            $lengthFunction = 'CHAR_LENGTH';
        } else {
            $expression = "COALESCE(NULLIF(LTRIM(d.dr_no, '0'), ''), '0')";
            $query->whereRaw("d.dr_no <> '' AND d.dr_no NOT GLOB '*[^0-9]*' AND LENGTH(d.dr_no) <= 18");
            $lengthFunction = 'LENGTH';
        }

        $from = $this->normalizeNumericString($from);
        $to = $this->normalizeNumericString($to);
        $query->where(function (Builder $lowerBound) use ($expression, $lengthFunction, $from): void {
            $length = strlen($from);
            $lowerBound->whereRaw("{$lengthFunction}({$expression}) > ?", [$length])
                ->orWhere(function (Builder $sameLength) use ($expression, $lengthFunction, $length, $from): void {
                    $sameLength->whereRaw("{$lengthFunction}({$expression}) = ?", [$length])
                        ->whereRaw("{$expression} >= ?", [$from]);
                });
        })->where(function (Builder $upperBound) use ($expression, $lengthFunction, $to): void {
            $length = strlen($to);
            $upperBound->whereRaw("{$lengthFunction}({$expression}) < ?", [$length])
                ->orWhere(function (Builder $sameLength) use ($expression, $lengthFunction, $length, $to): void {
                    $sameLength->whereRaw("{$lengthFunction}({$expression}) = ?", [$length])
                        ->whereRaw("{$expression} <= ?", [$to]);
                });
        })->orderByRaw("{$lengthFunction}({$expression})")
            ->orderByRaw($expression)
            ->orderBy('d.delivery_id');

        return $query;
    }

    private function compareNumericStrings(string $first, string $second): int
    {
        $first = $this->normalizeNumericString($first);
        $second = $this->normalizeNumericString($second);

        return strlen($first) <=> strlen($second) ?: strcmp($first, $second);
    }

    private function normalizeNumericString(string $value): string
    {
        return ltrim($value, '0') ?: '0';
    }

    private function boundedPreview(Builder $query, string $mode): JsonResponse
    {
        $total = (clone $query)->count();
        if ($total > $this->maxBatchSize()) {
            return response()->json([
                'message' => 'The preview exceeds the configured batch limit of '.$this->maxBatchSize().' deliveries. Narrow the scope or use Search and select across pages.',
                'total' => $total,
                'max_batch_size' => $this->maxBatchSize(),
            ], 422);
        }

        $records = $query->get()->map(fn (object $row): array => $this->eligibility->present($row))->values();
        $eligibleCount = $records->where('eligible', true)->count();

        return response()->json([
            'mode' => $mode,
            'records' => $records,
            'summary' => ['total' => $records->count(), 'eligible' => $eligibleCount, 'excluded' => $records->count() - $eligibleCount],
        ]);
    }

    private function maxBatchSize(): int
    {
        return (int) config('billing.max_batch_size', 500);
    }
}
