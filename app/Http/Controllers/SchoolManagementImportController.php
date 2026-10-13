<?php

namespace App\Http\Controllers;

use App\Services\SchoolImportService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class SchoolManagementImportController extends Controller
{
    public function __construct(private SchoolImportService $imports) {}

    public function index(Request $request): View
    {
        return view('schools.import', [
            'preview' => null,
            'importResult' => $this->imports->result($request),
            'listQuery' => SchoolController::listQuery($request),
        ]);
    }

    public function preview(Request $request): View|RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'max:5120', 'mimes:csv,txt,xlsx', 'extensions:csv,xlsx'],
        ]);

        try {
            $preview = $this->imports->preview($request->file('file'), $request);
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            report($exception);

            return redirect()->route('schools.import', SchoolController::listQuery($request))
                ->with('error', 'The import preview could not be prepared. Check the file and database connection, then try again.');
        }

        return view('schools.import', [
            'preview' => $preview,
            'importResult' => null,
            'listQuery' => SchoolController::listQuery($request),
        ]);
    }

    public function confirm(Request $request): RedirectResponse
    {
        $data = $request->validate(['token' => ['required', 'uuid']]);

        try {
            $result = $this->imports->confirm($data['token'], $request);
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            report($exception);

            return redirect()->route('schools.import', SchoolController::listQuery($request))
                ->with('error', 'The import could not be completed. Review the school list before uploading again.');
        }

        return redirect()->route('schools.import', SchoolController::listQuery($request))
            ->with('import_result_token', $result['token'])
            ->with($result['rolled_back'] ? 'error' : 'success', $result['rolled_back']
                ? 'No schools were saved because a database error interrupted this import. Review the results and try again.'
                : $result['summary']['imported'].' school(s) imported. Review the row results below.');
    }

    public function template(): StreamedResponse
    {
        return response()->streamDownload(function (): void {
            $stream = fopen('php://output', 'wb');
            fputcsv($stream, SchoolImportService::HEADERS, ',', '"', '');
            fclose($stream);
        }, 'school-import-template.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
