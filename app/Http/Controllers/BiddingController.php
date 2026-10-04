<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBiddingRequest;
use App\Http\Requests\UpdateBiddingRequest;
use App\Models\New\Item;
use App\Models\ProjectInformation;
use App\Services\BiddingService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;

class BiddingController extends Controller
{
    public function __construct(private BiddingService $biddingService) {}

    public function index(Request $request): View
    {
        return $this->listing($request, 'finance');
    }

    public function project_index(Request $request): View
    {
        return $this->listing($request, 'operation');
    }

    private function listing(Request $request, string $area): View
    {
        Gate::authorize('viewAny', ProjectInformation::class);
        $filters = $request->validate(['search' => ['nullable', 'string', 'max:100'], 'status' => ['nullable', 'string', 'max:30']]);
        $query = ProjectInformation::query();
        if (! empty($filters['search'])) {
            $search = '%'.$filters['search'].'%';
            $query->where(function ($query) use ($search): void {
                $query->where('project_id', 'like', $search)->orWhere('project_name', 'like', $search)
                    ->orWhere('procuring_entity', 'like', $search)
                    ->orWhereHas('lots', fn ($lots) => $lots->where('lot_no', 'like', $search));
            });
        }
        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        $projects = $query->with('lots')->latest()->orderByDesc('id')->paginate(20)->withQueryString();
        $biddingRoutePrefix = $area === 'operation' ? 'project.bidding' : 'bidding';

        return view($area.'.bidding.index', compact('projects', 'biddingRoutePrefix'));
    }

    public function create(): View
    {
        return $this->createView('finance', 'bidding');
    }

    public function project_create(): View
    {
        return $this->createView('operation', 'project.bidding');
    }

    private function createView(string $area, string $biddingRoutePrefix): View
    {
        Gate::authorize('create', ProjectInformation::class);
        $this->sanitizeOldInput();

        return view($area.'.bidding.create', [
            'catalogItems' => $this->catalogItems(),
            'biddingRoutePrefix' => $biddingRoutePrefix,
            'biddingLots' => [],
            'calculatedTotal' => '0.00',
        ]);
    }

    public function store(StoreBiddingRequest $request): RedirectResponse
    {
        $bidding = $this->biddingService->store($request->validated());

        return redirect()->route('bidding.show', $bidding)->with('success', 'Bidding document created successfully.');
    }

    public function project_store(StoreBiddingRequest $request): RedirectResponse
    {
        $bidding = $this->biddingService->store($request->validated());

        return redirect()->route('project.bidding.show', $bidding)->with('success', 'Bidding document created successfully.');
    }

    public function show(ProjectInformation $bidding): View
    {
        return $this->documentView($bidding, 'finance', 'bidding', 'show');
    }

    public function project_show(ProjectInformation $bidding): View
    {
        return $this->documentView($bidding, 'operation', 'project.bidding', 'show');
    }

    public function edit(ProjectInformation $bidding): View
    {
        return $this->documentView($bidding, 'finance', 'bidding', 'edit');
    }

    public function project_edit(ProjectInformation $bidding): View
    {
        return $this->documentView($bidding, 'operation', 'project.bidding', 'edit');
    }

    private function documentView(ProjectInformation $bidding, string $area, string $biddingRoutePrefix, string $page): View
    {
        Gate::authorize($page === 'edit' ? 'update' : 'view', $bidding);
        $this->sanitizeOldInput();
        $biddingLots = $this->biddingService->formLots($bidding);

        return view($area.'.bidding.'.$page, [
            'project' => $bidding,
            'biddingLots' => $biddingLots,
            'biddingRoutePrefix' => $biddingRoutePrefix,
            'catalogItems' => $page === 'edit' ? $this->catalogItems($bidding) : collect(),
            'calculatedTotal' => $this->biddingService->calculatedTotal($bidding),
        ]);
    }

    public function update(UpdateBiddingRequest $request, ProjectInformation $bidding): RedirectResponse
    {
        $this->biddingService->update($bidding, $request->validated());

        return redirect()->route('bidding.show', $bidding)->with('success', 'Bidding document updated successfully.');
    }

    public function project_update(UpdateBiddingRequest $request, ProjectInformation $bidding): RedirectResponse
    {
        $this->biddingService->update($bidding, $request->validated());

        return redirect()->route('project.bidding.show', $bidding)->with('success', 'Bidding document updated successfully.');
    }

    public function destroy(ProjectInformation $bidding): RedirectResponse
    {
        Gate::authorize('delete', $bidding);
        $this->biddingService->delete($bidding);

        return redirect()->route('bidding.index')->with('success', 'Bidding document deleted successfully.');
    }

    public function project_destroy(ProjectInformation $bidding): RedirectResponse
    {
        Gate::authorize('delete', $bidding);
        $this->biddingService->delete($bidding);

        return redirect()->route('project.bidding.index')->with('success', 'Bidding document deleted successfully.');
    }

    public function catalog(Request $request): JsonResponse
    {
        Gate::authorize('create', ProjectInformation::class);
        $data = $request->validate(['q' => ['nullable', 'string', 'max:100']]);
        $query = Item::query()->where('active', 1);
        if (! empty($data['q'])) {
            $search = '%'.$data['q'].'%';
            $query->where(fn ($query) => $query->where('item_name', 'like', $search)->orWhere('item_id', 'like', $search)->orWhere('description', 'like', $search));
        }
        $items = $query->select('id', 'item_name', 'description', 'unit', 'price')->orderBy('item_name')->orderBy('id')->limit(51)->get();

        return response()->json(['items' => $items->take(50)->values(), 'has_more' => $items->count() > 50]);
    }

    private function catalogItems(?ProjectInformation $bidding = null): Collection
    {
        $columns = ['id', 'item_name', 'description', 'unit', 'price', 'active'];
        $items = Item::query()->where('active', 1)->select($columns)->orderBy('item_name')->orderBy('id')->limit(100)->get();
        $selected = $bidding?->items()->whereNotNull('catalog_item_id')->pluck('catalog_item_id') ?? collect();
        $oldLots = session()->getOldInput('lots', []);
        if (is_array($oldLots)) {
            $selected = $selected->merge(collect($oldLots)->flatMap(function (mixed $lot): array {
                if (! is_array($lot)) {
                    return [];
                }
                $ids = array_column(is_array($lot['legacy_items'] ?? null) ? $lot['legacy_items'] : [], 'catalog_item_id');
                foreach ($lot['addresses'] ?? [] as $address) {
                    if (! is_array($address)) {
                        continue;
                    }
                    foreach ($address['keystages'] ?? [] as $stage) {
                        if (is_array($stage)) {
                            $ids = array_merge($ids, array_column(is_array($stage['items'] ?? null) ? $stage['items'] : [], 'catalog_item_id'));
                        }
                    }
                }

                return $ids;
            }));
        }

        $selected = $selected->filter(fn (mixed $id): bool => is_scalar($id) && ctype_digit((string) $id))->unique()->take(1000);

        return $items->merge(Item::query()->whereIn('id', $selected)->select($columns)->get())->unique('id')->values();
    }

    private function sanitizeOldInput(): void
    {
        $old = session()->getOldInput();
        if (is_array($old) && $old !== []) {
            session()->flashInput($this->sanitizeFormRow($old));
        }
    }

    /** @param array<string, mixed> $row @return array<string, mixed> */
    private function sanitizeFormRow(array $row): array
    {
        foreach ($row as $key => $value) {
            if (in_array($key, ['lots', 'addresses', 'keystages', 'items', 'legacy_items'], true)) {
                $row[$key] = is_array($value) ? array_map($this->sanitizeFormRow(...), array_filter($value, 'is_array')) : [];
            } elseif (is_array($value) || is_object($value)) {
                $row[$key] = null;
            }
        }

        return $row;
    }
}
