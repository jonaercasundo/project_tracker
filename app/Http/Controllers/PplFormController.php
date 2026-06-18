<?php

namespace App\Http\Controllers;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Illuminate\Http\Request;
use App\Models\PplForm;
use Illuminate\Support\Facades\DB;

class PplFormController extends Controller
{
public function index(Request $request)
{
    $query = PplForm::query();

    // ===============================
    // SEARCH
    // ===============================
    if ($request->filled('search')) {
        $search = $request->search;

        $query->where(function ($q) use ($search) {
            $q->where('project_code', 'like', "%{$search}%")
              ->orWhere('project_title', 'like', "%{$search}%")
              ->orWhere('project_id_no', 'like', "%{$search}%")
              ->orWhere('region', 'like', "%{$search}%")
              ->orWhere('bidder', 'like', "%{$search}%");
        });
    }

    // ===============================
    // SORTING (SAFE)
    // ===============================
    $allowedSorts = [
        'id',
        'project_code',
        'lot_number',
        'project_title',
        'project_id_no',
        'region',
        'bid_opening',
        'abc',
        'bidder',
    ];

    $sortBy = $request->get('sort_by', 'id');
    $sortOrder = $request->get('sort_order', 'desc');

    $sortBy = in_array($sortBy, $allowedSorts) ? $sortBy : 'id';
    $sortOrder = $sortOrder === 'asc' ? 'asc' : 'desc';

    $query->orderBy($sortBy, $sortOrder);

    // ===============================
    // PAGINATION
    // ===============================
    $data = $query->paginate(10)->withQueryString();

    return view('finance.ppl_forms.index', compact('data'));
}

    public function create()
    {
        return view('finance.ppl_forms.create');
    }

    public function store(Request $request)
    {
        PplForm::create($request->all());

        return redirect('/ppl-forms')->with('success', 'Saved successfully');
    }

        public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv'
        ]);

        $file = $request->file('file');
        $spreadsheet = IOFactory::load($file->getPathname());
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray();

        $inserted = 0;
        $failed = 0;

        // skip header row
        foreach ($rows as $index => $row) {

            if ($index === 0) continue;

            try {

                PplForm::create([
                    'project_code' => $this->clean($row[0] ?? null),
                    'lot_number' => $this->clean($row[1] ?? null),
                    'project_title' => $this->clean($row[2] ?? null),
                    'project_id_no' => $this->clean($row[3] ?? null),
                    'region' => $this->clean($row[4] ?? null),

                    'bid_opening' => $this->cleanDate($row[5] ?? null),

                    'abc' => $this->cleanNumber($row[12] ?? null),
                    'bidder' => $this->clean($row[38] ?? null),
                    'authorized_signatory' => $this->clean($row[39] ?? null),
                ]);

                $inserted++;

            } catch (\Exception $e) {
                $failed++;
                continue;
            }
        }

        return back()->with('success',
            "Import completed. Inserted: $inserted | Failed: $failed"
        );
    }

    // ---------------- HELPERS ----------------

    private function clean($value)
    {
        if (empty($value)) return null;

        return trim(mb_convert_encoding($value, 'UTF-8', 'auto'));
    }

    private function cleanDate($value)
    {
        if (empty($value)) return null;

        try {
            return date('Y-m-d', strtotime($value));
        } catch (\Exception $e) {
            return null;
        }
    }

    private function cleanNumber($value)
    {
        if (empty($value)) return null;

        return (float) str_replace([',', ' '], '', $value);
    }

}
