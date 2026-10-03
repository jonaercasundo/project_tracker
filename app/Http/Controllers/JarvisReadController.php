<?php

namespace App\Http\Controllers;

use App\Http\Requests\JarvisReadRequest;
use App\Http\Resources\JarvisRecordResource;
use App\Services\JarvisReadService;
use App\Services\ProjectDeliveryProgressService;
use Closure;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;

class JarvisReadController extends Controller
{
    public function __construct(private JarvisReadService $operations) {}

    public function dashboard(JarvisReadRequest $request): JsonResponse
    {
        return $this->success($request, $this->operations->dashboard($request->validated()));
    }

    public function projects(JarvisReadRequest $request): JsonResponse
    {
        return $this->paginate($request, $this->operations->projectRecords($request->validated())->orderBy('p.project_id'));
    }

    public function deliveryProgress(JarvisReadRequest $request, ProjectDeliveryProgressService $progress): JsonResponse
    {
        $filters = array_filter($request->validated(), fn (mixed $value): bool => $value !== null && $value !== '');

        return $this->success($request, $progress->report($filters), ['definitions' => $progress->definitions()]);
    }

    public function project(JarvisReadRequest $request, int $id): JsonResponse
    {
        $project = $this->operations->projectRecords(['project_id' => $id])->first();
        abort_unless($project, 404, 'Project not found.');

        return $this->success($request, [
            'project' => (new JarvisRecordResource($project))->resolve($request),
            'operations' => $this->operations->dashboard(['project_id' => $id]),
        ]);
    }

    public function deliveries(JarvisReadRequest $request): JsonResponse
    {
        $filters = $request->validated();

        return $this->paginate(
            $request,
            $this->operations->deliveries($filters)->select('d.delivery_id')->orderBy('d.delivery_id'),
            fn (Collection $deliveries): Collection => $deliveries->isEmpty()
                ? $deliveries
                : $this->operations->deliveryRecords($filters, $deliveries->pluck('delivery_id')->all())->orderBy('d.delivery_id')->get(),
        );
    }

    public function inventory(JarvisReadRequest $request): JsonResponse
    {
        return $this->paginate($request, $this->operations->inventoryRecords($request->validated())->orderBy('inv.inventory_id'));
    }

    public function warehouses(JarvisReadRequest $request): JsonResponse
    {
        return $this->paginate($request, $this->operations->warehouses($request->validated())->orderBy('w.warehouse_id'));
    }

    public function lots(JarvisReadRequest $request): JsonResponse
    {
        return $this->paginate($request, $this->operations->lots($request->validated())->orderBy('l.lot_id'));
    }

    public function packages(JarvisReadRequest $request): JsonResponse
    {
        return $this->paginate($request, $this->operations->packages($request->validated())->orderBy('d.delivery_id')->orderBy('pk.package_id')->orderBy('ps.package_status_id'));
    }

    public function masterlist(JarvisReadRequest $request): JsonResponse
    {
        $filters = $request->validated();
        $query = $this->operations->masterlist($filters);
        $query->orderBy(($filters['source'] ?? 'catalog') === 'catalog' ? 'i.id' : 'i.item_id');

        return $this->paginate($request, $query);
    }

    /** @param Closure(Collection): Collection|null $records */
    private function paginate(JarvisReadRequest $request, Builder $query, ?Closure $records = null): JsonResponse
    {
        $filters = $request->validated();
        $page = $query->paginate((int) ($filters['per_page'] ?? 25), ['*'], 'page', (int) ($filters['page'] ?? 1));

        $collection = $records ? $records($page->getCollection()) : $page->getCollection();

        return $this->success($request, JarvisRecordResource::collection($collection)->resolve($request), [
            'pagination' => [
                'current_page' => $page->currentPage(),
                'per_page' => $page->perPage(),
                'total' => $page->total(),
                'last_page' => $page->lastPage(),
                'has_more' => $page->hasMorePages(),
            ],
        ]);
    }

    /** @param mixed $data @param array<string, mixed> $extraMeta */
    private function success(JarvisReadRequest $request, mixed $data, array $extraMeta = []): JsonResponse
    {
        $company = $request->attributes->get('jarvis_company');
        $meta = [
            'company' => ['company_id' => $company->company_id, 'code' => $company->code],
            'filters' => (object) $request->validated(),
            'supported_filters' => $request->supportedFilters(),
            'generated_at' => now()->toIso8601String(),
            'timezone' => config('app.timezone'),
        ];
        $endpoint = $request->route()->getActionMethod();
        $meta['definitions'] = match ($endpoint) {
            'dashboard', 'project' => [
                'year' => 'Project creation year; all metrics are restricted to the selected projects.',
                'schools_with_delivered_or_accepted_rows' => 'Distinct school IDs with at least one delivered or accepted delivery row; does not mean all allocations for the school are complete.',
                'schools_with_delivered_or_accepted_packages' => 'Distinct school IDs with at least one delivered or accepted package allocation. Package receipt can be recorded independently of delivery row status; does not mean full school fulfillment.',
                'pending_deliveries' => 'Non-cancelled delivery rows with pending delivery status or an expected pending package allocation.',
                'package_allocations' => 'Expected package definitions matched to delivery rows, including missing status as pending.',
                'recorded_qty' => 'Sum of current inventory.qty, without reservation deductions; may combine different item units.',
                'approved_qty' => 'Recorded quantity with inventory_status Approved.',
            ],
            'deliveries', 'packages' => [
                'pagination_unit' => $endpoint === 'deliveries' ? 'Delivery row, not grouped delivery receipt.' : 'Package allocation per delivery, not unique package definition.',
                'package_status' => 'Expected packages match delivery keystage when set, otherwise delivery lot; missing/null status means pending.',
                'billing' => 'Counts of recorded billing groups matching the exact DR number; zero groups means unknown, not unbilled. Multiple groups can have different statuses.',
                'warehouse_id' => 'Warehouse linked through the delivery logistics location.',
                'date_field' => 'Date range/year applies to the specified delivery field; defaults to delivery_date. It is not the package release date.',
                'released_at' => 'Unavailable: warehouse release does not reliably persist a release timestamp.',
            ],
            'inventory', 'warehouses' => [
                'recorded_qty' => 'Current inventory.qty; no reservation deduction. Aggregate quantities may combine different units.',
                'available_only' => 'Positive recorded stock, following the warehouse availability check; approval remains a separate filter.',
            ],
            'masterlist' => [
                'source' => $request->query('source', 'catalog'),
                'catalog' => 'Current items masterlist. catalog_project_id references project_information.project_id and catalog_lot_id references lots.id; these are separate from operations IDs.',
                'operations' => 'Legacy item table used by inventory and package contents. lot_id filters package membership, including a package lot inherited from keystage.',
            ],
            'lots' => ['source' => 'Operational lot table; use masterlist source=operations with lot_id for its items.'],
            default => ['pending_deliveries' => 'Non-cancelled delivery rows with pending delivery status or an expected pending package allocation.'],
        };

        return response()->json(['success' => true, 'data' => $data, 'meta' => array_merge($meta, $extraMeta)])
            ->header('Cache-Control', 'private, no-store');
    }
}
