<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Services\PackageContentsService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;

class ProjectPackageController extends Controller
{
    public function show(Project $project, int $packageId, PackageContentsService $packageContents): View
    {
        $this->authorizeProjectAccess($project, 'view');
        $package = $this->findProjectPackage($project, $packageId);
        $items = $packageContents->rows($packageId);

        return view('projects.packages.show', [
            'project' => $project,
            'package' => $package,
            'items' => $items,
        ]);
    }

    public function edit(Project $project, int $packageId, PackageContentsService $packageContents): View
    {
        $this->authorizeProjectAccess($project, 'update');
        $package = $this->findProjectPackage($project, $packageId);

        return view('projects.packages.edit', [
            'project' => $project,
            'package' => $package,
            'items' => $packageContents->rows($packageId),
            'duplicateItemIds' => $packageContents->duplicateItemIds($packageId),
            'quantityChangeBlockReason' => $packageContents->quantityChangeBlockReason($packageId),
        ]);
    }

    public function update(Request $request, Project $project, int $packageId, PackageContentsService $packageContents): RedirectResponse
    {
        $this->authorizeProjectAccess($project, 'update');
        $package = $this->findProjectPackage($project, $packageId);
        $quantityChangeBlockReason = $packageContents->quantityChangeBlockReason($packageId);
        $validated = $request->validate([
            'items' => ['sometimes', 'array'],
            'items.*' => ['required', 'integer', 'min:0', 'max:2147483647'],
            'remarks' => [$quantityChangeBlockReason !== null ? 'required' : 'nullable', 'string', 'max:1000'],
        ]);

        $packageContents->updateQuantities($packageId, $validated['items'] ?? [], $validated['remarks'] ?? null, auth()->id());

        return to_route('projects.packages.show', [$project, $packageId])
            ->with('success', 'Package item quantities updated successfully.');
    }

    private function findProjectPackage(Project $project, int $packageId): object
    {
        $packageColumns = Schema::getColumnListing('package');
        $descriptionColumn = collect(['description', 'package_description'])
            ->first(fn (string $column): bool => in_array($column, $packageColumns, true));

        $query = DB::table('package as p')
            ->leftJoin('lot as l', 'l.lot_id', '=', 'p.lot_id')
            ->leftJoin('keystage as k', 'k.keystage_id', '=', 'p.keystage_id')
            ->leftJoin('lot as kl', 'kl.lot_id', '=', 'k.lot_id')
            ->where('p.package_id', $packageId)
            ->where(function ($query) use ($project): void {
                $query->where('l.project_id', $project->project_id)
                    ->orWhere('kl.project_id', $project->project_id);
            })
            ->select([
                'p.*',
                'l.lot_name',
                'l.project_id as package_lot_project_id',
                'k.keystage_num',
                'k.description as keystage_description',
                'k.lot_id as keystage_lot_id',
                'kl.project_id as keystage_project_id',
                'kl.lot_name as keystage_lot_name',
            ]);

        if ($descriptionColumn !== null) {
            $query->addSelect('p.'.$descriptionColumn.' as package_description');
        }

        $package = $query->firstOrFail();
        $lotMatchesProject = (int) ($package->package_lot_project_id ?? 0) === (int) $project->project_id;
        $stageMatchesProject = (int) ($package->keystage_project_id ?? 0) === (int) $project->project_id;
        $package->lot_label = $lotMatchesProject
            ? $package->lot_name
            : ($stageMatchesProject ? $package->keystage_lot_name : null);
        $stageMatchesLot = $package->lot_id === null || (int) $package->lot_id === (int) ($package->keystage_lot_id ?? 0);
        $package->keystage_label = $stageMatchesProject && $stageMatchesLot && filled($package->keystage_num)
            ? 'Key Stage '.$package->keystage_num
            : 'No Key Stage assigned';
        $package->code_label = $package->package_code ?? $package->code ?? $package->package_num ?? $package->package_id;
        $package->description_label = filled($package->package_description ?? null)
            ? $package->package_description
            : ($stageMatchesProject && $stageMatchesLot ? $package->keystage_description : null);

        return $package;
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
