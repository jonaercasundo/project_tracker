<?php

namespace App\Http\Controllers;

use App\Models\Keystage;
use App\Models\Lot;
use App\Models\Project;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class ProjectLotController extends Controller
{
    public function show(Project $project, int $lotId): View
    {
        $this->authorizeProjectAccess($project, 'view');
        $lot = $this->findProjectLot($project, $lotId);
        $keystages = Keystage::query()
            ->where('lot_id', $lot->lot_id)
            ->orderBy('keystage_num')
            ->orderBy('keystage_id')
            ->get();

        return view('projects.lots.show', compact('project', 'lot', 'keystages'));
    }

    public function edit(Project $project, int $lotId): View
    {
        $this->authorizeProjectAccess($project, 'update');
        $lot = $this->findProjectLot($project, $lotId);
        $keystages = Keystage::query()
            ->with('lot:lot_id,lot_name')
            ->whereHas('lot', fn (EloquentBuilder $query): EloquentBuilder => $query->where('project_id', $project->project_id))
            ->orderBy('keystage_num')
            ->orderBy('keystage_id')
            ->get();
        $assignedKeystageIds = Keystage::query()->where('lot_id', $lot->lot_id)->pluck('keystage_id')->map(fn ($id): int => (int) $id)->all();

        return view('projects.lots.edit', compact('project', 'lot', 'keystages', 'assignedKeystageIds'));
    }

    public function update(Request $request, Project $project, int $lotId): RedirectResponse
    {
        $this->authorizeProjectAccess($project, 'update');
        $this->findProjectLot($project, $lotId);

        $validated = $request->validate([
            'lot_name' => ['required', 'string', 'max:255'],
            'contract_no' => ['nullable', 'string', 'max:255'],
            'contract_price' => ['required', 'numeric', 'decimal:0,2', 'min:0', 'max:9999999999999.99'],
            'keystage_ids' => ['nullable', 'array'],
            'keystage_ids.*' => ['integer', 'distinct'],
        ]);
        $selectedKeystageIds = array_map('intval', $validated['keystage_ids'] ?? []);

        try {
            DB::transaction(function () use ($project, $lotId, $validated, $selectedKeystageIds): void {
                $lot = Lot::query()
                    ->where('project_id', $project->project_id)
                    ->whereKey($lotId)
                    ->lockForUpdate()
                    ->firstOrFail();

                $availableKeystageIds = Keystage::query()
                    ->whereIn('keystage_id', $selectedKeystageIds)
                    ->whereHas('lot', fn (EloquentBuilder $query): EloquentBuilder => $query->where('project_id', $project->project_id))
                    ->pluck('keystage_id')
                    ->map(fn ($id): int => (int) $id);

                if ($availableKeystageIds->count() !== count($selectedKeystageIds)) {
                    throw ValidationException::withMessages([
                        'keystage_ids' => 'Choose keystages assigned to this project.',
                    ]);
                }

                $lot->fill([
                    'lot_name' => $validated['lot_name'],
                    'contract_no' => $validated['contract_no'] ?? null,
                    'contract_price' => $validated['contract_price'],
                ])->save();

                $keystagesToUnassign = Keystage::query()->where('lot_id', $lotId);
                if ($selectedKeystageIds !== []) {
                    $keystagesToUnassign->whereNotIn('keystage_id', $selectedKeystageIds);
                }
                $keystagesToUnassign->update(['lot_id' => null]);

                if ($selectedKeystageIds !== []) {
                    Keystage::query()->whereIn('keystage_id', $selectedKeystageIds)->update(['lot_id' => $lotId]);
                }
            }, 3);
        } catch (QueryException $exception) {
            report($exception);

            return to_route('projects.lots.edit', [$project, $lotId])
                ->withInput()
                ->with('error', 'The lot could not be saved. Please try again.');
        }

        return to_route('projects.show', ['project' => $project->project_id, 'tab' => 'lots'])
            ->with('success', 'Lot updated successfully.');
    }

    private function findProjectLot(Project $project, int $lotId): Lot
    {
        return Lot::query()
            ->where('project_id', $project->project_id)
            ->whereKey($lotId)
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
