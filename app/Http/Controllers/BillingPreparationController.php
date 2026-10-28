<?php

namespace App\Http\Controllers;

use App\Exceptions\BillingBatchEligibilityException;
use App\Exceptions\BillingTransitionException;
use App\Exceptions\ExpiredBillingQrCodeException;
use App\Exceptions\InvalidBillingQrCodeException;
use App\Models\BillingBatch;
use App\Models\DeliveryBillingRecord;
use App\Models\User;
use App\Services\BillingBatchService;
use App\Services\BillingSheetSyncService;
use App\Services\DeliveryBillingEligibilityService;
use App\Services\DeliveryBillingQrCodeService;
use App\Services\DeliveryBillingService;
use App\Services\GoogleSheetsApiClient;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class BillingPreparationController extends Controller
{
    public function __construct(
        private DeliveryBillingEligibilityService $eligibility,
        private DeliveryBillingQrCodeService $qrCodes,
        private BillingBatchService $batches,
        private DeliveryBillingService $billing,
        private BillingSheetSyncService $sheetSync,
        private GoogleSheetsApiClient $sheets
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

        $groups = $this->groupsForRows($rows);
        if ($groups->sum('delivery_count') > $this->maxBatchSize()) {
            return response()->json(['message' => 'This DR group contains more delivery records than one review can hold. Search by DR number, project, or school to narrow the results.'], 422);
        }

        return response()->json([
            'records' => $groups,
            'message' => $groups->count() > 1
                ? 'This DR number is associated with multiple project groups. Review the conflict before confirming.'
                : ($groups->first()['delivery_count'] > 1 ? 'Matching deliveries are grouped under this DR number.' : 'Delivery record found.'),
        ]);
    }

    public function search(Request $request): JsonResponse
    {
        $filters = $this->validatedFilters($request);
        $drSearch = trim((string) ($filters['dr_search'] ?? ''));
        $schoolIdSearch = trim((string) ($filters['school_id_search'] ?? ''));
        $drSearchValues = $this->splitSearchValues($drSearch);
        $schoolIdSearchValues = $this->splitSearchValues($schoolIdSearch);
        if ($drSearchValues !== [] && $schoolIdSearchValues !== []) {
            return response()->json(['message' => 'Search by DR number or School ID, one field at a time.'], 422);
        }
        unset($filters['dr_search'], $filters['school_id_search']);
        if ($drSearchValues !== []) {
            unset($filters['q']);
            $filters['dr_search_values'] = $drSearchValues;
        } elseif ($schoolIdSearchValues !== []) {
            unset($filters['q']);
            $filters['school_id_search_values'] = $schoolIdSearchValues;
        }
        if (isset($filters['project_id']) && (! isset($filters['year']) || ! $this->projectMatchesYear($filters['project_id'], $filters['year']))) {
            return response()->json(['message' => 'Choose a Year before searching within a Project.'], 422);
        }
        if ($drSearchValues === [] && $schoolIdSearchValues === [] && empty($filters['q'])) {
            return response()->json(['message' => 'Enter a DR number or School ID to search.'], 422);
        }

        $perPage = min(max($request->integer('per_page', 25), 10), 50);
        $records = $this->groupedQuery($this->eligibility->query($filters))
            ->paginate($perPage)
            ->withQueryString();
        $groups = $this->groupsForRows($records->getCollection());

        return response()->json([
            'records' => $groups,
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
            'confirmation_date' => ['sometimes', 'date_format:Y-m-d'],
            'excluded_delivery_ids' => ['sometimes', 'array', 'max:'.$this->maxBatchSize()],
            'excluded_delivery_ids.*' => ['integer', 'distinct:strict', 'exists:deliveries,delivery_id'],
            'expected_delivery_ids' => ['required_if:mode,all', 'array', 'min:1', 'max:'.$this->maxBatchSize()],
            'expected_delivery_ids.*' => ['integer', 'distinct:strict', 'exists:deliveries,delivery_id'],
        ]);
        $confirmationDate = $validated['confirmation_date'] ?? now()->toDateString();
        $actor = $request->user();
        abort_unless($actor instanceof User, 401);
        $filters = null;
        $selectionFilters = null;
        $deliveryIds = array_map('intval', $validated['delivery_ids'] ?? []);
        $groupKeys = null;
        $groupCount = 0;
        $excludedRecords = collect();

        // A retry must be replayed before current eligibility is recalculated: the
        // original records are already For Billing by the time a client retries.
        $priorBatch = BillingBatch::query()->where('idempotency_key', $validated['idempotency_key'])->first();
        if ($priorBatch) {
            $selectionFilters = $validated['mode'] === 'all'
                ? $this->validatedFilters($request)
                : ($validated['mode'] === 'range' ? $request->input('selection_filters') : null);
            $submittedIds = $validated['mode'] === 'all'
                ? array_map('intval', $validated['expected_delivery_ids'] ?? [])
                : $deliveryIds;
            $retryIds = $priorBatch->deliveryBillingRecords()
                ->orderBy('delivery_id')
                ->pluck('delivery_id')
                ->map(fn (mixed $id): int => (int) $id)
                ->all();
            if ($validated['mode'] !== 'all' && $submittedIds !== []) {
                $retryRows = $this->eligibility->query()->whereIn('d.delivery_id', $submittedIds)->get();
                $groupKeys = $this->groupsForRows($retryRows)->pluck('group_key')->all();
            }
            $groupCount = $this->eligibility->presentGroups($this->eligibility->query()->whereIn('d.delivery_id', $retryIds)->get())->count();
            try {
                $result = $this->batches->confirm($retryIds, $validated['mode'], $validated['idempotency_key'], $actor, $confirmationDate, $selectionFilters, $groupKeys);
            } catch (BillingBatchEligibilityException $exception) {
                return response()->json(['message' => $exception->getMessage(), 'rejected' => $exception->rejectedRecords], $exception->httpStatus);
            }
            $sheetSync = $this->sheetSync->syncBatch((int) $result['batch']->getKey(), $this->sheets);

            return response()->json([
                'message' => 'This selection was already confirmed.',
                'sheet_sync' => $sheetSync,
                'updated_records' => $this->updatedRecordDetails($retryIds),
                'rejected_records' => [],
                'batch' => [
                    'id' => $result['batch']->getKey(),
                    'batch_ref' => $result['batch']->batch_ref,
                    'status' => $result['batch']->status,
                    'confirmation_date' => $result['batch']->confirmation_date?->toDateString(),
                    'delivery_count' => count($retryIds),
                    'group_count' => $groupCount,
                    'url' => route('billing.batches.show', $result['batch']),
                ],
            ]);
        }

        $alreadySubmittedIds = $validated['mode'] === 'all'
            ? array_map('intval', $validated['expected_delivery_ids'] ?? [])
            : $deliveryIds;
        if ($alreadySubmittedIds !== []) {
            $alreadySubmittedRecords = DB::table('delivery_billing_records')
                ->whereIn('delivery_id', $alreadySubmittedIds)
                ->orderBy('delivery_id')
                ->get(['delivery_id', 'billing_batch_id', 'status']);
            $existingBatchIds = $alreadySubmittedRecords->pluck('billing_batch_id')->filter()->unique();
            $allAlreadyConfirmedTogether = $alreadySubmittedRecords->count() === count($alreadySubmittedIds)
                && $alreadySubmittedRecords->every(fn (object $record): bool => $record->status === DeliveryBillingRecord::FOR_BILLING)
                && $existingBatchIds->count() === 1;

            if ($allAlreadyConfirmedTogether) {
                $existingBatch = BillingBatch::query()->find((int) $existingBatchIds->first());
                $sameSelectAllRequest = $validated['mode'] !== 'all';
                if ($existingBatch && $validated['mode'] === 'all') {
                    $submittedFilters = $this->validatedFilters($request);
                    $batchDeliveryIds = $existingBatch->deliveryBillingRecords()
                        ->orderBy('delivery_id')
                        ->pluck('delivery_id')
                        ->map(fn (mixed $id): int => (int) $id)
                        ->all();
                    $submittedDeliveryIds = $alreadySubmittedIds;
                    sort($batchDeliveryIds);
                    sort($submittedDeliveryIds);
                    $sameSelectAllRequest = $existingBatch->selection_method === 'all'
                        && $existingBatch->selection_filters == $submittedFilters
                        && $batchDeliveryIds === $submittedDeliveryIds;
                }

                if ($existingBatch && $sameSelectAllRequest) {
                    $retryRows = $this->eligibility->query()->whereIn('d.delivery_id', $alreadySubmittedIds)->get();
                    $groupCount = $this->groupsForRows($retryRows)->count();
                    $sheetSync = $this->sheetSync->syncBatch((int) $existingBatch->getKey(), $this->sheets);

                    return response()->json([
                        'message' => 'This delivery selection is already confirmed for Billing.',
                        'sheet_sync' => $sheetSync,
                        'updated_records' => $this->updatedRecordDetails($alreadySubmittedIds),
                        'rejected_records' => [],
                        'batch' => [
                            'id' => $existingBatch->getKey(),
                            'batch_ref' => $existingBatch->batch_ref,
                            'status' => $existingBatch->status,
                            'confirmation_date' => $existingBatch->confirmation_date?->toDateString(),
                            'delivery_count' => count($alreadySubmittedIds),
                            'group_count' => $groupCount,
                            'url' => route('billing.batches.show', $existingBatch),
                        ],
                    ]);
                }
            }
        }

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
            $allGroups = $this->eligibility->presentGroups($this->eligibility->query($filters)->orderBy('d.delivery_id')->get());
            $expectedIds = array_values(array_unique(array_map('intval', $validated['expected_delivery_ids'])));
            $eligibleIds = $allGroups->flatMap(fn (array $group): array => $group['eligible_delivery_ids'])
                ->reject(fn (int $id): bool => in_array($id, $excludedIds, true))->unique()->sort()->values()->all();
            sort($expectedIds);

            if ($eligibleIds !== $expectedIds) {
                return response()->json(['message' => 'The eligible delivery list changed after preview. Refresh the preview before confirming.'], 409);
            }
            if (count($eligibleIds) > $this->maxBatchSize()) {
                return response()->json(['message' => 'The eligible selection exceeds the configured batch limit. Narrow the filters and preview again.'], 422);
            }
            $deliveryIds = $eligibleIds;
            $selectionFilters = $filters;
            $groupCount = $allGroups->where('eligible', true)->count();
        } else {
            // A selected DR represents its complete project-scoped group. Re-read the group
            // server-side so callers cannot submit only a subset of its deliveries.
            $selectedRows = $this->eligibility->query()->whereIn('d.delivery_id', $deliveryIds)->get();
            $groups = $this->groupsForRows($selectedRows);
            if ($groups->contains(fn (array $group): bool => $group['conflict'])) {
                return response()->json(['message' => 'A selected DR is ambiguous across projects and requires review.', 'rejected' => $groups->where('conflict')->flatMap(fn (array $group): array => $group['excluded_records'])->values()], 409);
            }
            $groupKeys = $groups->pluck('group_key')->all();
            $groupCount = $groups->count();
            $deliveryIds = $groups->flatMap(fn (array $group): array => $group['eligible_delivery_ids'])->unique()->values()->all();
            $excludedRecords = $groups->flatMap(fn (array $group): array => $group['excluded_records'])->values();
            if ($deliveryIds === []) {
                return response()->json(['message' => 'No records in the selected DR groups are eligible for billing.', 'rejected' => $excludedRecords], 409);
            }
        }

        try {
            $result = $this->batches->confirm($deliveryIds, $validated['mode'], $validated['idempotency_key'], $actor, $confirmationDate, $selectionFilters, $groupKeys ?? null);
        } catch (BillingBatchEligibilityException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
                'rejected' => $exception->rejectedRecords,
            ], $exception->httpStatus);
        }
        $sheetSync = $this->sheetSync->syncBatch((int) $result['batch']->getKey(), $this->sheets);

        return response()->json([
            'message' => $result['created'] ? 'Deliveries confirmed for Billing.' : 'This selection was already confirmed.',
            'sheet_sync' => $sheetSync,
            'updated_records' => $this->updatedRecordDetails($deliveryIds),
            'rejected_records' => $excludedRecords ?? collect(),
            'batch' => [
                'id' => $result['batch']->getKey(),
                'batch_ref' => $result['batch']->batch_ref,
                'status' => $result['batch']->status,
                'confirmation_date' => $result['batch']->confirmation_date?->toDateString(),
                'delivery_count' => count($deliveryIds),
                'group_count' => $groupCount,
                'url' => route('billing.batches.show', $result['batch']),
            ],
        ], $result['created'] ? 201 : 200);
    }

    public function undo(Request $request, int $delivery): JsonResponse
    {
        $actor = $request->user();
        abort_unless($actor instanceof User, 401);

        try {
            $billingRecord = $this->billing->undoForBilling($delivery, $actor);
        } catch (BillingTransitionException $exception) {
            return response()->json(['message' => $exception->getMessage()], $exception->httpStatus);
        }

        $deliveryRow = $this->eligibility->query()
            ->where('d.delivery_id', $delivery)
            ->firstOrFail();
        $group = $this->groupsForRows(collect([$deliveryRow]))->first();

        return response()->json([
            'message' => 'For Billing confirmation was undone. Billing status returned to Pending Billing.',
            'billing_status' => $billingRecord->status,
            'group' => $group,
        ]);
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
            'dr_search' => ['nullable', 'string', 'max:1000'],
            'school_id_search' => ['nullable', 'string', 'max:1000'],
        ]);

        return array_filter($validated, static fn (mixed $value): bool => $value !== null && $value !== '');
    }

    /** @return array<int, string> */
    private function splitSearchValues(string $value): array
    {
        $values = preg_split('/[\s,]+/u', $value, -1, PREG_SPLIT_NO_EMPTY) ?: [];

        return array_values(array_unique($values));
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

        $groups = $this->groupsForRows($query->get());
        $deliveryCount = $groups->sum('delivery_count');
        if ($deliveryCount > $this->maxBatchSize()) {
            return response()->json([
                'message' => 'The matching DR groups contain more delivery records than the configured batch limit of '.$this->maxBatchSize().'. Narrow the scope and preview again.',
                'total' => $groups->count(),
                'max_batch_size' => $this->maxBatchSize(),
            ], 422);
        }
        $eligibleCount = $groups->where('eligible', true)->count();

        return response()->json([
            'mode' => $mode,
            'records' => $groups,
            'summary' => [
                'total' => $groups->count(),
                'eligible' => $eligibleCount,
                'excluded' => $groups->count() - $eligibleCount,
                'delivery_records' => $deliveryCount,
            ],
        ]);
    }

    private function groupedQuery(Builder $query): Builder
    {
        return $query
            ->whereNotNull('d.dr_no')
            ->whereRaw("TRIM(d.dr_no) <> ''")
            ->select(['d.dr_no', 'd.project_id', 'p.project_name'])
            ->selectRaw('MAX(d.delivery_date) as latest_delivery_date')
            ->selectRaw('COUNT(DISTINCT d.delivery_id) as delivery_count')
            ->groupBy('d.dr_no', 'd.project_id', 'p.project_name')
            ->orderByDesc('latest_delivery_date')
            ->orderBy('d.dr_no')
            ->orderBy('d.project_id');
    }

    /** @param Collection<int, object> $matchedRows
     * @return Collection<int, array<string, mixed>>
     */
    private function groupsForRows(Collection $matchedRows): Collection
    {
        if ($matchedRows->isEmpty()) {
            return collect();
        }

        $keys = $matchedRows->map(function (object $row): array {
            return [
                'project_id' => (int) $row->project_id,
                'dr_no' => (string) $row->dr_no,
                'delivery_id' => isset($row->delivery_id) ? (int) $row->delivery_id : null,
            ];
        });
        $query = $this->eligibility->query()->where(function (Builder $groupQuery) use ($keys): void {
            foreach ($keys as $key) {
                $groupQuery->orWhere(function (Builder $group) use ($key): void {
                    $group->where('d.project_id', $key['project_id']);
                    if ($key['dr_no'] === '' && $key['delivery_id'] !== null) {
                        $group->where('d.delivery_id', $key['delivery_id']);
                    } else {
                        $group->where('d.dr_no', $key['dr_no']);
                    }
                });
            }
        });
        $groups = $this->eligibility->presentGroups($query->orderBy('d.delivery_id')->get())->keyBy('group_key');
        $orderedKeys = $keys->map(fn (array $key): string => DeliveryBillingEligibilityService::groupKey($key['project_id'], $key['dr_no'], $key['delivery_id']))
            ->unique()
            ->values();

        return $orderedKeys->map(fn (string $key): ?array => $groups->get($key))->filter()->values();
    }

    private function maxBatchSize(): int
    {
        return (int) config('billing.max_batch_size', 500);
    }

    /** @param array<int, int> $deliveryIds
     * @return Collection<int, array<string, mixed>>
     */
    private function updatedRecordDetails(array $deliveryIds): Collection
    {
        if ($deliveryIds === []) {
            return collect();
        }

        return DB::table('deliveries as d')
            ->join('projects as p', 'p.project_id', '=', 'd.project_id')
            ->leftJoin('lot as l', 'l.lot_id', '=', 'd.lot_id')
            ->leftJoin('school as s', 's.school_id', '=', 'd.school_id')
            ->leftJoin('delivery_billing_records as billing', 'billing.delivery_id', '=', 'd.delivery_id')
            ->whereIn('d.delivery_id', $deliveryIds)
            ->orderBy('d.delivery_id')
            ->get([
                'd.delivery_id', 'd.dr_no', 'd.status as delivery_status',
                'p.project_name', 'l.lot_name', 's.school_name', 'billing.status as billing_status',
            ])
            ->map(fn (object $record): array => (array) $record);
    }
}
