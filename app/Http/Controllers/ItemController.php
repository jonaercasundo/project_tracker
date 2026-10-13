<?php

namespace App\Http\Controllers;

use App\Http\Requests\SaveCatalogItemRequest;
use App\Models\CatalogItem;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\QueryException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ItemController extends Controller
{
    private const SORTABLE_COLUMNS = [
        'item_id', 'code_prefix', 'item_name', 'unit', 'price', 'supplier_price', 'active',
    ];

    public function index(Request $request): View
    {
        $filters = self::listQuery($request);
        $query = CatalogItem::query()->whereNull('project_id');

        if (isset($filters['search'])) {
            $query->where(function (Builder $query) use ($filters): void {
                foreach (['item_id', 'code_prefix', 'item_name', 'description', 'unit'] as $column) {
                    $query->orWhere($column, 'like', '%'.$filters['search'].'%');
                }
            });
        }

        if (isset($filters['active'])) {
            $query->where('active', $filters['active']);
        }

        $sort = $filters['sort'] ?? 'item_name';
        $direction = $filters['direction'] ?? 'asc';
        $items = $query
            ->orderBy($sort, $direction)
            ->orderBy('id')
            ->paginate(25)
            ->appends($filters);

        return view('items.index', compact('items', 'filters'));
    }

    public function create(Request $request): View
    {
        return view('items.create', [
            'item' => new CatalogItem,
            'listQuery' => self::listQuery($request),
        ]);
    }

    public function store(SaveCatalogItemRequest $request): RedirectResponse
    {
        $listQuery = self::listQuery($request);

        try {
            CatalogItem::query()->create($request->validated());
        } catch (UniqueConstraintViolationException $exception) {
            throw ValidationException::withMessages([
                'code_prefix' => 'The generated item ID could not be reserved. Please try again.',
            ]);
        } catch (QueryException $exception) {
            report($exception);

            return back()->withInput()->with('error', 'The item could not be saved. Please try again.');
        }

        return to_route('catalog-items.index', $listQuery)->with('success', 'Item added successfully.');
    }

    public function show(Request $request, int $item): View
    {
        return view('items.show', [
            'item' => $this->findStandaloneItem($item),
            'listQuery' => self::listQuery($request),
        ]);
    }

    public function edit(Request $request, int $item): View
    {
        return view('items.edit', [
            'item' => $this->findStandaloneItem($item),
            'listQuery' => self::listQuery($request),
        ]);
    }

    public function update(SaveCatalogItemRequest $request, int $item): RedirectResponse
    {
        $catalogItem = $this->findStandaloneItem($item);
        $listQuery = self::listQuery($request);

        try {
            $catalogItem->update($request->validated());
        } catch (QueryException $exception) {
            report($exception);

            return back()->withInput()->with('error', 'The item could not be updated. Please try again.');
        }

        return to_route('catalog-items.index', $listQuery)->with('success', 'Item updated successfully.');
    }

    /** @return array<string, string|int> */
    public static function listQuery(Request $request): array
    {
        $data = Validator::make($request->query(), [
            'search' => ['nullable', 'string', 'max:255'],
            'active' => ['nullable', Rule::in(['0', '1'])],
            'sort' => ['nullable', Rule::in(self::SORTABLE_COLUMNS)],
            'direction' => ['nullable', Rule::in(['asc', 'desc'])],
            'page' => ['nullable', 'integer', 'min:1', 'max:1000000'],
        ])->validate();

        return array_filter($data, fn (mixed $value): bool => $value !== null && $value !== '');
    }

    private function findStandaloneItem(int $id): CatalogItem
    {
        return CatalogItem::query()->whereNull('project_id')->whereKey($id)->firstOrFail();
    }
}
