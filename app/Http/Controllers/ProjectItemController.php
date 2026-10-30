<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ProjectItemController extends Controller
{
    public function show(Project $project, int $itemId): View
    {
        $this->authorizeProjectAccess($project, 'view');
        $item = $this->findProjectItem($project, $itemId);
        $details = collect((array) $item)
            ->reject(fn (mixed $value, string $field): bool => in_array($field, ['project_id', 'created_at', 'updated_at'], true))
            ->map(fn (mixed $value, string $field): array => [
                'label' => match ($field) {
                    'price' => 'Selling Price',
                    'supplier_price' => 'Supplier Price',
                    default => Str::headline($field),
                },
                'value' => in_array($field, ['price', 'supplier_price'], true) && $value !== null
                    ? "\u{20B1}".number_format((float) $value, 2)
                    : ($value === null || $value === '' ? 'Not provided' : (string) $value),
            ]);

        return view('projects.items.show', compact('project', 'item', 'details'));
    }

    public function edit(Project $project, int $itemId): View
    {
        $this->authorizeProjectAccess($project, 'update');
        $item = $this->findProjectItem($project, $itemId);
        $quantityPackages = $this->packageQuantityRows($project, $itemId);

        return view('projects.items.edit', [
            'project' => $project,
            'item' => $item,
            'fields' => $this->editableFields($item),
            'quantityPackages' => $quantityPackages,
        ]);
    }

    public function update(Request $request, Project $project, int $itemId): RedirectResponse
    {
        $this->authorizeProjectAccess($project, 'update');
        $item = $this->findProjectItem($project, $itemId);
        $fields = $this->editableFields($item);
        $rules = collect($fields)->mapWithKeys(fn (array $field): array => [$field['name'] => $field['rules']])->all();
        $rules['package_contents'] = ['sometimes', 'array'];
        $rules['package_contents.*'] = ['required', 'integer', 'min:0', 'max:2147483647'];
        $validated = $request->validate($rules);
        $packageContents = $validated['package_contents'] ?? [];
        unset($validated['package_contents']);

        try {
            DB::transaction(function () use ($project, $itemId, $validated, $packageContents): void {
                $item = DB::table('item')
                    ->where('project_id', $project->project_id)
                    ->where('item_id', $itemId)
                    ->lockForUpdate()
                    ->first();

                abort_if($item === null, 404);

                DB::table('item')
                    ->where('project_id', $project->project_id)
                    ->where('item_id', $itemId)
                    ->update($validated);

                foreach ($packageContents as $packageId => $quantity) {
                    if (! ctype_digit((string) $packageId)) {
                        throw ValidationException::withMessages(['package_contents' => 'A selected package is invalid.']);
                    }

                    $package = $this->projectPackageQuery($project, (int) $packageId)->lockForUpdate()->first();
                    abort_if($package === null, 404);

                    $content = DB::table('package_content')
                        ->where('package_id', $packageId)
                        ->where('item_id', $itemId)
                        ->lockForUpdate();
                    $contentCount = (clone $content)->count();
                    if ($contentCount !== 1) {
                        throw ValidationException::withMessages([
                            'package_contents.'.$packageId => 'This package has duplicate or missing quantity rows for the item. Resolve the package contents before editing this quantity.',
                        ]);
                    }

                    $this->ensurePackageQuantityIsEditable((int) $packageId);
                    $content->update(['qty' => $quantity]);
                }
            }, 3);
        } catch (QueryException $exception) {
            report($exception);

            return to_route('projects.items.edit', [$project, $itemId])
                ->withInput()
                ->with('error', 'The item could not be saved. Please try again.');
        }

        return to_route('projects.show', ['project' => $project->project_id, 'tab' => 'items'])
            ->with('success', 'Item updated successfully.');
    }

    /** @return array<int, array{name: string, label: string, type: string, rules: array<int, string>, value: mixed}> */
    private function editableFields(object $item): array
    {
        $itemColumns = Schema::getColumnListing('item');
        $definitions = [
            'item_name' => ['label' => 'Item name', 'type' => 'text', 'rules' => ['required', 'string', 'max:255']],
            'description' => ['label' => 'Description', 'type' => 'textarea', 'rules' => ['nullable', 'string', 'max:10000']],
            'item_description' => ['label' => 'Item description', 'type' => 'textarea', 'rules' => ['nullable', 'string', 'max:10000']],
            'item_type' => ['label' => 'Type', 'type' => 'text', 'rules' => ['nullable', 'string', 'max:255']],
            'type' => ['label' => 'Type', 'type' => 'text', 'rules' => ['nullable', 'string', 'max:255']],
            'unit' => ['label' => 'Unit', 'type' => 'text', 'rules' => ['nullable', 'string', 'max:255']],
            'price' => ['label' => 'Selling Price', 'type' => 'number', 'rules' => ['nullable', 'numeric', 'decimal:0,2', 'min:0', 'max:9999999999999.99']],
            'supplier_price' => ['label' => 'Supplier price', 'type' => 'number', 'rules' => ['nullable', 'numeric', 'decimal:0,2', 'min:0', 'max:9999999999999.99']],
        ];

        return collect($definitions)
            ->filter(fn (array $definition, string $field): bool => in_array($field, $itemColumns, true))
            ->map(fn (array $definition, string $field): array => [
                'name' => $field,
                ...$definition,
                'value' => in_array($field, ['price', 'supplier_price'], true) && $item->{$field} !== null
                    ? number_format((float) $item->{$field}, 2, '.', '')
                    : ($item->{$field} ?? ''),
            ])
            ->values()
            ->all();
    }

    private function findProjectItem(Project $project, int $itemId): object
    {
        return DB::table('item')
            ->where('project_id', $project->project_id)
            ->where('item_id', $itemId)
            ->firstOrFail();
    }

    private function packageQuantityRows(Project $project, int $itemId): Collection
    {
        return DB::table('package_content as pc')
            ->join('package as p', 'p.package_id', '=', 'pc.package_id')
            ->leftJoin('lot as l', 'l.lot_id', '=', 'p.lot_id')
            ->leftJoin('keystage as k', 'k.keystage_id', '=', 'p.keystage_id')
            ->leftJoin('lot as kl', 'kl.lot_id', '=', 'k.lot_id')
            ->where('pc.item_id', $itemId)
            ->where(function ($query) use ($project): void {
                $query->where('l.project_id', $project->project_id)
                    ->orWhere('kl.project_id', $project->project_id);
            })
            ->select('p.package_id', 'p.package_num', 'p.lot_id', 'k.lot_id as keystage_lot_id', 'pc.qty', 'l.lot_name', 'k.keystage_num', 'kl.project_id as keystage_project_id')
            ->orderBy('p.package_num')
            ->orderBy('p.package_id')
            ->get();
    }

    private function projectPackageQuery(Project $project, int $packageId): Builder
    {
        return DB::table('package as p')
            ->leftJoin('lot as l', 'l.lot_id', '=', 'p.lot_id')
            ->leftJoin('keystage as k', 'k.keystage_id', '=', 'p.keystage_id')
            ->leftJoin('lot as kl', 'kl.lot_id', '=', 'k.lot_id')
            ->where('p.package_id', $packageId)
            ->where(function ($query) use ($project): void {
                $query->where('l.project_id', $project->project_id)
                    ->orWhere('kl.project_id', $project->project_id);
            });
    }

    private function ensurePackageQuantityIsEditable(int $packageId): void
    {
        $package = DB::table('package')->where('package_id', $packageId)->first(['lot_id', 'keystage_id']);
        if ($package !== null && DB::table('deliveries')->where(function ($query) use ($package): void {
            if ($package->keystage_id !== null) {
                $query->where('keystage_id', $package->keystage_id);
            }
            if ($package->lot_id !== null) {
                $query->orWhere(function ($lotDeliveries) use ($package): void {
                    $lotDeliveries->whereNull('keystage_id')->where('lot_id', $package->lot_id);
                });
            }
        })->exists()) {
            throw ValidationException::withMessages([
                'package_contents.'.$packageId => 'This package is assigned to a delivery. Its quantity cannot be changed because that would alter delivery, billing, or inventory requirements.',
            ]);
        }

        if (Schema::hasTable('package_status') && DB::table('package_status')->where('package_id', $packageId)->exists()) {
            throw ValidationException::withMessages([
                'package_contents.'.$packageId => 'This package already has delivery status records. Its quantity cannot be changed because that would alter recorded delivery and billing requirements.',
            ]);
        }

        if (Schema::hasTable('inventory_history') && Schema::hasColumn('inventory_history', 'package_id') && DB::table('inventory_history')->where('package_id', $packageId)->exists()) {
            throw ValidationException::withMessages([
                'package_contents.'.$packageId => 'This package has inventory history. Its quantity cannot be changed because that would make the recorded stock movements inconsistent.',
            ]);
        }
    }

    private function authorizeProjectAccess(Project $project, string $ability): void
    {
        if (! Schema::hasTable('project_information')) {
            return;
        }

        $bidding = $project->bidding()->first();
        if ($bidding !== null) {
            Gate::authorize($ability, $bidding);
        }
    }
}
