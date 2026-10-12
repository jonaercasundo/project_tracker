<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\IOFactory;

class BiddingMasterlistService
{
    /** @return array{headers: array, rows: array} */
    public function read(UploadedFile $file): array
    {
        $type = strtolower($file->getClientOriginalExtension()) === 'csv' ? 'Csv' : 'Xlsx';
        if ($type === 'Xlsx') {
            $zip = new \ZipArchive;
            if ($zip->open($file->getRealPath()) !== true) {
                throw ValidationException::withMessages(['file' => 'This is not a readable XLSX workbook.']);
            }
            try {
                $expandedSize = 0;
                for ($index = 0; $index < $zip->numFiles; $index++) {
                    $expandedSize += $zip->statIndex($index)['size'];
                }
                if ($zip->numFiles > 1000 || $expandedSize > 100 * 1024 * 1024) {
                    throw ValidationException::withMessages(['file' => 'The expanded workbook is too large. Export the masterlist sheet as CSV.']);
                }
            } finally {
                $zip->close();
            }
        }
        $reader = IOFactory::createReader($type);
        $reader->setReadDataOnly(true);
        $info = $reader->listWorksheetInfo($file->getRealPath());
        if (count($info) !== 1 || $info[0]['totalRows'] > 30001 || $info[0]['totalColumns'] > 100) {
            throw ValidationException::withMessages(['file' => 'Use one sheet with at most 30,000 rows and 100 columns.']);
        }
        $book = $reader->load($file->getRealPath());
        try {
            $rows = $book->getActiveSheet()->toArray(null, false, false, false);
            $headers = array_map(fn ($value) => trim((string) $value), array_shift($rows) ?? []);
            if (count(array_unique($headers)) !== count($headers) || in_array('', $headers, true)) {
                throw ValidationException::withMessages(['file' => 'Each spreadsheet column must have a unique, nonempty heading.']);
            }

            return ['headers' => $headers, 'rows' => $rows];
        } finally {
            $book->disconnectWorksheets();
        }
    }

    /** @param array<string, mixed> $sheet @param array<string, mixed> $mapping @param array<string, mixed> $plan
     * @return array{rows: array, errors: array, processed: int}
     */
    public function parse(array $sheet, array $mapping, array $plan): array
    {
        $headers = $sheet['headers'];
        $schoolColumn = array_search($mapping['school'], $headers, true);
        $gradeColumn = array_search($mapping['grade'], $headers, true);
        if ($schoolColumn === false || $gradeColumn === false || empty($mapping['lots'])) {
            throw ValidationException::withMessages(['mapping' => 'Map the School ID, Grade Level and at least one lot column.']);
        }
        $rows = [];
        $errors = [];
        $packages = collect($plan['packages']);
        foreach ($mapping['lots'] as $column => $lot) {
            if (! in_array($column, $headers, true) || ! $packages->contains('lot_no', $lot)) {
                throw ValidationException::withMessages(['mapping' => 'Map lot columns to configured lots.']);
            }
        }
        foreach ($sheet['rows'] as $index => $cells) {
            if (count(array_filter($cells, fn ($v) => $v !== null && $v !== '')) === 0) {
                continue;
            }
            $schoolId = trim((string) ($cells[$schoolColumn] ?? ''));
            $grade = trim((string) ($cells[$gradeColumn] ?? ''));
            $hasAllocation = false;
            foreach ($mapping['lots'] as $column => $lot) {
                $value = trim((string) ($cells[array_search($column, $headers, true)] ?? ''));
                if ($value === '') {
                    continue;
                }
                $hasAllocation = true;
                $code = preg_split('/\s+/', $value)[0];
                $match = $packages->filter(fn ($p) => strcasecmp($p['lot_no'], $lot) === 0 && strcasecmp($p['grade_alias'], $grade) === 0 && strcasecmp($p['code'], $code) === 0)->values();
                $problem = null;
                if ($match->count() !== 1) {
                    $problem = 'Unknown package or grade/key stage for this lot.';
                } elseif (strcasecmp($value, $code) !== 0) {
                    $expected = $match[0]['code'].' '.$lot.' '.$match[0]['stage'].' '.$grade;
                    $shortStage = $match[0]['code'].' '.$lot.' '.preg_split('/\s+/', $match[0]['stage'])[0].' '.$grade;
                    if (strcasecmp(preg_replace('/\s+/', ' ', $value), preg_replace('/\s+/', ' ', $expected)) !== 0 && strcasecmp(preg_replace('/\s+/', ' ', $value), preg_replace('/\s+/', ' ', $shortStage)) !== 0) {
                        $problem = 'Package text disagrees with the mapped lot, stage or grade.';
                    }
                }
                if ($problem !== null) {
                    $errors[] = ['row' => $index + 2, 'school_id' => $schoolId, 'lot' => $lot, 'grade' => $grade, 'package' => $value, 'problem' => $problem];
                } else {
                    $rows[] = ['row' => $index + 2, 'school_id' => $schoolId, 'package_key' => $match[0]['key']];
                }
            }
            if (! $hasAllocation) {
                $errors[] = ['row' => $index + 2, 'school_id' => $schoolId, 'problem' => 'No package allocation in the mapped lot columns.'];
            }
        }

        return ['rows' => $rows, 'errors' => $errors, 'processed' => count($sheet['rows'])];
    }

    /** @param list<array<string, mixed>> $rows @param list<array<string, mixed>> $existing
     * @return array<string, mixed>
     */
    public function validate(array $rows, array $existing, string $mode, array $errors = []): array
    {
        if (count($rows) > 60000) {
            throw ValidationException::withMessages(['file' => 'Use at most 60,000 assignments per import.']);
        }
        $known = [];
        foreach (array_chunk(array_unique(array_column($rows, 'school_id')), 500) as $ids) {
            foreach (DB::table('school')->whereIn('school_id', $ids)->pluck('school_id') as $id) {
                $known[(string) $id] = true;
            }
        }
        $current = [];
        foreach ($existing as $row) {
            $current[$row['package_key'].'|'.$row['school_id']] = $row;
        }
        $valid = [];
        $seen = [];
        $unchanged = 0;
        foreach ($rows as $index => $row) {
            $identity = $row['package_key'].'|'.$row['school_id'];
            $problem = ! preg_match('/^\d{1,20}$/', $row['school_id']) ? 'Malformed School ID.' : (! isset($known[$row['school_id']]) ? 'School ID not found.' : null);
            if (isset($seen[$identity]) || ($mode === 'add' && isset($current[$identity]))) {
                $problem = 'Duplicate assignment.';
            }
            $seen[$identity] = true;
            if ($problem !== null) {
                $errors[] = ['row' => $row['row'] ?? $index + 1, 'school_id' => $row['school_id'], 'package' => $row['package_key'], 'problem' => $problem];

                continue;
            }
            $valid[$identity] = ['school_id' => $row['school_id'], 'package_key' => $row['package_key']];
            if (isset($current[$identity])) {
                $unchanged++;
            }
        }
        $removed = $mode === 'replace' ? count(array_diff_key($current, $valid)) : 0;
        $result = $mode === 'replace' ? $valid : array_replace($current, $valid);
        if (count($result) > 60000) {
            throw ValidationException::withMessages(['file' => 'A project may contain at most 60,000 school assignments.']);
        }

        return ['rows' => array_values($result), 'errors' => $errors,
            'summary' => ['submitted' => count($rows), 'valid' => count($valid), 'added' => count(array_diff_key($valid, $current)), 'removed' => $removed, 'unchanged' => $unchanged, 'rejected' => count($errors), 'schools' => count(array_unique(array_column($result, 'school_id'))), 'assignments' => count($result)]];
    }
}
