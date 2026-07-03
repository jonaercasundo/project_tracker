<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Symfony\Component\Process\Process;
use Illuminate\Support\Facades\Storage;
use App\Models\School;
use Illuminate\Support\Facades\DB;

class SchoolImportController extends Controller
{
    public function index()
    {
        return view('operation.allocation_list.import');
    }

public function preview(Request $request)
{
    $request->validate([
        'url'  => 'required_without:pdf_file|nullable|url',
        'pdf_file' => 'required_without:url|nullable|file|mimes:pdf|max:51200',
    ]);

    $python = base_path('venv\\Scripts\\python.exe');
    $script = base_path('python\\extract_schools.py');

    $tempPath = null;

    try {

        if ($request->hasFile('pdf_file')) {
            $path = $request->file('pdf_file')->store('temp_pdf_imports');
            $tempPath = storage_path('app/' . $path);
            $target = $tempPath;
        } else {
            $target = $request->input('url');
        }

        $process = new Process([$python, $script, $target]);
        $process->setTimeout(600);
        $process->run();

        if (!$process->isSuccessful()) {
            return response()->json([
                'error' => $process->getErrorOutput() ?: 'Python failed'
            ], 500);
        }

        $output = json_decode($process->getOutput(), true);

        if (!$output || $output['status'] !== 'success') {
            return response()->json([
                'error' => 'Invalid Python output'
            ], 500);
        }

        // ✔️ RETURN ARRAY ONLY (FOR forEach)
        return response()->json($output['schools']);

    } finally {
        if ($tempPath && file_exists($tempPath)) {
            unlink($tempPath);
        }
    }
}
public function import(Request $request)
{
    $request->validate([
        'schools' => 'required|array'
    ]);

    DB::beginTransaction();

    try {

        $insert = [];

        foreach ($request->schools as $row) {
            $insert[] = [
                'school_id'     => $row['School ID'] ?? null,
                'school_name'   => $row['School Name'] ?? null,
                'municipality'  => $row['Municipality'] ?? null,
                'division'      => $row['Division'] ?? null,
                'region'        => $row['Region'] ?? null,
            ];
        }

        School::upsert(
            $insert,
            ['school_id'],
            ['school_name', 'municipality', 'division', 'region']
        );

        DB::commit();

        return response()->json([
            'status' => 'success',
            'count' => count($insert)
        ]);

    } catch (\Exception $e) {
        DB::rollback();

        return response()->json([
            'status' => 'error',
            'message' => $e->getMessage()
        ], 500);
    }
}
}