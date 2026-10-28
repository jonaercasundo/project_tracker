<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Illuminate\Contracts\View\View;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

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

        return view('projects.items.edit', [
            'project' => $project,
            'item' => $item,
            'fields' => $this->editableFields($item),
        ]);
    }

    public function update(Request $request, Project $project, int $itemId): RedirectResponse
    {
        $this->authorizeProjectAccess($project, 'update');
        $item = $this->findProjectItem($project, $itemId);
        $fields = $this->editableFields($item);
        $rules = collect($fields)->mapWithKeys(fn (array $field): array => [$field['name'] => $field['rules']])->all();
        $validated = $request->validate($rules);

        try {
            DB::transaction(function () use ($project, $itemId, $validated): void {
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
