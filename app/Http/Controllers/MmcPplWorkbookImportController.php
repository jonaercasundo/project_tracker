<?php

namespace App\Http\Controllers;

use App\Http\Requests\MmcPplWorkbookUploadRequest;
use App\Models\MmcPplImportBatch;
use App\Services\MmcPplWorkbookImportService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

        return view('finance.ppl-import.show', [
            'batch' => $batch->load(['rows' => fn ($query) => $query->orderBy('source_row')]),
        ]);
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
