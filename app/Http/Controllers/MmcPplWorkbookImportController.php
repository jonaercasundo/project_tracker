<?php

namespace App\Http\Controllers;

use App\Http\Requests\MmcPplWorkbookUploadRequest;
use App\Models\MmcPplImportBatch;
use App\Models\MmcPplImportRow;
use App\Models\Project;
use App\Models\ProjectInformation;
use App\Models\ProjectLot;
use App\Services\MmcPplWorkbookImportService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MmcPplWorkbookImportController extends Controller
{
    public function index(Request $request): View
    {
        $companyId = (int) $request->user()->currentCompany()->company_id;

        return view('finance.ppl-import.index', [
            'batches' => MmcPplImportBatch::query()
                ->where('company_id', $companyId)
                ->withCount(['rows as imported_rows_count' => fn ($query) => $query->where('status', 'imported')])
                ->latest()
                ->paginate(20),
        ]);
    }

    public function store(MmcPplWorkbookUploadRequest $request, MmcPplWorkbookImportService $service): RedirectResponse
    {
        $result = $service->stage(
            $request->file('file'),
            $request->user(),
            (int) $request->user()->currentCompany()->company_id,
        );

        return redirect()->route('finance.ppl-import.show', $result['batch'])
            ->with('status', $result['duplicate'] ? 'This exact workbook was already previewed. Opened its existing batch.' : 'Workbook preview created. Review every warning and blocked row before confirming.');
    }

    public function show(Request $request, MmcPplImportBatch $batch): View
    {
        $this->authorizeBatch($request, $batch);

        $projects = collect();
        if (Schema::hasTable('projects') && Schema::hasColumn('projects', 'project_id') && Schema::hasColumn('projects', 'project_name')) {
            $columns = ['project_id', 'project_name'];
            foreach (['project_code', 'project_information_id'] as $column) {
                if (Schema::hasColumn('projects', $column)) {
                    $columns[] = $column;
                }
            }
            $projects = Project::query()->orderBy('project_name')->get($columns);
        }

        $biddings = collect();
        if (Schema::hasTable('project_information') && Schema::hasColumn('project_information', 'id') && Schema::hasColumn('project_information', 'project_name')) {
            $columns = ['id', 'project_name'];
            foreach (['project_id_no', 'lot_no'] as $column) {
                if (Schema::hasColumn('project_information', $column)) {
                    $columns[] = $column;
                }
            }
            $biddings = ProjectInformation::query()->orderBy('project_name')->get($columns);
        }

        $lots = Schema::hasTable('lots') && Schema::hasColumn('lots', 'id') && Schema::hasColumn('lots', 'project_id') && Schema::hasColumn('lots', 'lot_no')
            ? ProjectLot::query()->with('project:id,project_name')->orderBy('lot_no')->get(['id', 'project_id', 'lot_no'])
            : collect();

        return view('finance.ppl-import.show', [
            'batch' => $batch->load(['rows' => fn ($query) => $query->orderBy('source_row')]),
            'mappingProjects' => $projects,
            'mappingBiddings' => $biddings,
            'mappingLots' => $lots,
        ]);
    }

    public function updateMapping(Request $request, MmcPplImportBatch $batch, MmcPplImportRow $row, MmcPplWorkbookImportService $service): RedirectResponse
    {
        $this->authorizeBatch($request, $batch);
        $validated = $request->validate([
            'project_id' => ['nullable', 'integer', 'min:1'],
            'project_information_id' => ['nullable', 'integer', 'min:1'],
            'lot_id' => ['nullable', 'integer', 'min:1'],
        ]);

        $service->mapRow(
            $batch,
            $row,
            $request->user(),
            (int) $request->user()->currentCompany()->company_id,
            isset($validated['project_id']) ? (int) $validated['project_id'] : null,
            isset($validated['project_information_id']) ? (int) $validated['project_information_id'] : null,
            isset($validated['lot_id']) ? (int) $validated['lot_id'] : null,
        );

        return redirect()->route('finance.ppl-import.show', $batch)->with('status', 'Source links saved for this preview row. Financial inputs and historical PPL data were not changed.');
    }

    public function confirm(Request $request, MmcPplImportBatch $batch, MmcPplWorkbookImportService $service): RedirectResponse
    {
        $this->authorizeBatch($request, $batch);
        $validated = $request->validate(['acknowledge_warnings' => ['nullable', 'accepted']]);
        $result = $service->confirm(
            $batch,
            $request->user(),
            (int) $request->user()->currentCompany()->company_id,
            isset($validated['acknowledge_warnings']),
        );

        return redirect()->route('finance.ppl-import.show', $result['batch'])
            ->with('status', "Import complete: {$result['imported']} plan(s) created, {$result['skipped']} duplicate(s) skipped, {$result['blocked']} row(s) blocked.");
    }

    public function cancel(Request $request, MmcPplImportBatch $batch, MmcPplWorkbookImportService $service): RedirectResponse
    {
        $this->authorizeBatch($request, $batch);
        $service->cancel($batch, (int) $request->user()->currentCompany()->company_id);

        return redirect()->route('finance.ppl-import.index')->with('status', 'Import preview cancelled. No financial plans were created.');
    }

    public function download(Request $request, MmcPplImportBatch $batch): StreamedResponse
    {
        $this->authorizeBatch($request, $batch);
        abort_unless(Storage::disk('local')->exists($batch->storage_path), 404);

        return Storage::disk('local')->download($batch->storage_path, $batch->original_file_name);
    }

    private function authorizeBatch(Request $request, MmcPplImportBatch $batch): void
    {
        abort_unless((int) $batch->company_id === (int) $request->user()->currentCompany()->company_id, 404);
    }
}
