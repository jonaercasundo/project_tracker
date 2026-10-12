<?php

namespace App\Http\Controllers;

use App\Models\ProjectInformation;
use App\Models\School;
use App\Services\BiddingMasterlistService;
use App\Services\BiddingSchoolService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BiddingSchoolController extends Controller
{
    public function __construct(private BiddingSchoolService $schools, private BiddingMasterlistService $masterlists) {}

    public function search(Request $request): JsonResponse
    {
        Gate::authorize('create', ProjectInformation::class);
        $data = $request->validate(['q' => ['nullable', 'string', 'max:100']]);
        $query = School::query();
        if (! empty($data['q'])) {
            $query->where(fn ($q) => $q->where('school_id', 'like', '%'.$data['q'].'%')->orWhere('school_name', 'like', '%'.$data['q'].'%'));
        }

        return response()->json($query->orderBy('school_name')->orderBy('school_id')->paginate(25, ['school_id', 'school_name', 'region', 'division', 'municipality', 'address']));
    }

    public function workspace(Request $request): JsonResponse|StreamedResponse
    {
        $data = $request->validate([
            'action' => ['required', 'in:begin,upload,preview,confirm,list,remove,reassign,errors'],
            'project_id' => ['nullable', 'integer'], 'token' => ['nullable', 'uuid'],
            'revision' => ['nullable', 'string', 'size:64'],
            'school_plan' => ['nullable', 'string', 'max:2000000'],
            'ids' => ['nullable', 'string', 'max:1500000'], 'package_key' => ['nullable', 'uuid'],
            'old_package_key' => ['nullable', 'uuid'],
            'mode' => ['nullable', 'in:add,replace'], 'mapping' => ['nullable', 'array'],
            'mapping.school' => ['required_with:mapping', 'string', 'max:255'],
            'mapping.grade' => ['required_with:mapping', 'string', 'max:255'],
            'mapping.lots' => ['required_with:mapping', 'array', 'max:20'],
            'mapping.lots.*' => ['string', 'max:50'],
            'page' => ['nullable', 'integer', 'min:1'], 'search' => ['nullable', 'string', 'max:100'],
            'region' => ['nullable', 'string', 'max:255'], 'division' => ['nullable', 'string', 'max:255'], 'municipality' => ['nullable', 'string', 'max:255'],
            'file' => ['nullable', 'file', 'max:10240', 'mimes:csv,txt,xlsx', 'extensions:csv,xlsx'],
            'school_id' => ['nullable', 'string', 'max:255'],
        ]);
        $project = isset($data['project_id']) ? ProjectInformation::findOrFail($data['project_id']) : null;
        Gate::authorize($project ? (in_array($data['action'], ['begin', 'list'], true) ? 'view' : 'update') : 'create', $project ?? ProjectInformation::class);
        if ($data['action'] === 'begin') {
            $draft = $this->schools->begin($project);
            if (isset($data['revision']) && $data['revision'] !== $draft['base']) {
                throw ValidationException::withMessages(['school_plan' => 'School configuration changed since this page opened. Reload before editing.']);
            }

            return response()->json(['token' => $this->schools->putDraft($draft), 'count' => count($draft['rows'])]);
        }
        $draft = $this->schools->draft($data['token'] ?? '', $project?->id);
        if ($data['action'] === 'upload') {
            if (! $request->hasFile('file')) {
                throw ValidationException::withMessages(['file' => 'Choose a CSV or XLSX masterlist.']);
            }
            try {
                $draft['sheet'] = $this->masterlists->read($request->file('file'));
            } catch (ValidationException $e) {
                throw $e;
            } catch (\Throwable $e) {
                throw ValidationException::withMessages(['file' => 'The workbook could not be read. Export a single sheet as CSV or XLSX.']);
            }
            $draft['filename'] = mb_substr(preg_replace('/[^\pL\pN ._-]/u', '_', basename($request->file('file')->getClientOriginalName())), 0, 200);

            return response()->json(['token' => $this->schools->putDraft($draft), 'headers' => $draft['sheet']['headers'], 'rows' => count($draft['sheet']['rows'])]);
        }
        if ($data['action'] === 'preview') {
            $plan = $this->schools->validatePlan($data['school_plan'] ?? '');
            if (isset($data['ids'])) {
                if (! in_array($data['package_key'] ?? '', array_column($plan['packages'], 'key'), true)) {
                    throw ValidationException::withMessages(['package_key' => 'Select a configured key stage and package.']);
                }
                $ids = preg_split('/[\s,;]+/', trim($data['ids']), -1, PREG_SPLIT_NO_EMPTY);
                $parsed = ['rows' => array_map(fn ($id) => ['school_id' => $id, 'package_key' => $data['package_key']], $ids), 'errors' => [], 'processed' => count($ids)];
                $filename = 'School IDs';
            } else {
                if (! isset($draft['sheet'], $data['mapping'])) {
                    throw ValidationException::withMessages(['file' => 'Upload and map a masterlist first.']);
                }
                $parsed = $this->masterlists->parse($draft['sheet'], $data['mapping'], $plan);
                $filename = $draft['filename'];
            }
            $preview = $this->masterlists->validate($parsed['rows'], $draft['rows'], $data['mode'] ?? 'add', $parsed['errors']);
            $definitions = collect($plan['packages'])->keyBy('key');
            $lotSummary = [];
            foreach ($preview['rows'] as $row) {
                $lot = $definitions->get($row['package_key'])['lot_no'] ?? 'Removed package';
                $lotSummary[$lot]['schools'][$row['school_id']] = true;
                $lotSummary[$lot]['assignments'] = ($lotSummary[$lot]['assignments'] ?? 0) + 1;
            }
            $preview['summary']['lots'] = array_map(fn ($lot) => ['schools' => count($lot['schools']), 'assignments' => $lot['assignments']], $lotSummary);
            $draft['pending'] = $preview;
            $draft['pending']['history'] = ['filename' => $filename, 'rows_processed' => $parsed['processed'], 'valid_assignments' => $preview['summary']['valid'], 'rejected_rows' => count($preview['errors']), 'summary' => json_encode($preview['summary'])];
            $draft['pending']['mode'] = $data['mode'] ?? 'add';
            unset($draft['sheet']);

            return response()->json(['token' => $this->schools->putDraft($draft), 'summary' => $preview['summary'], 'errors' => array_slice($preview['errors'], 0, 50)]);
        }
        if ($data['action'] === 'confirm') {
            if (! isset($draft['pending']) || ($draft['pending']['mode'] === 'replace' && count($draft['pending']['errors']) > 0)) {
                throw ValidationException::withMessages(['file' => 'Replacement imports must have no rejected rows. Correct the masterlist and preview again.']);
            }
            $draft['rows'] = $draft['pending']['rows'];
            $draft['history'][] = $draft['pending']['history'];
            unset($draft['pending']);

            return response()->json(['token' => $this->schools->putDraft($draft), 'count' => count($draft['rows'])]);
        }
        if ($data['action'] === 'errors') {
            $errors = $draft['pending']['errors'] ?? [];

            return response()->streamDownload(function () use ($errors): void {
                $out = fopen('php://output', 'w');
                fputcsv($out, ['Row', 'School ID', 'Lot', 'Grade', 'Package', 'Problem']);
                foreach ($errors as $error) {
                    $row = array_map(function ($key) use ($error): string {
                        $text = (string) ($error[$key] ?? '');

                        return preg_match('/^[\s]*[=+@-]/', $text) ? "'".$text : $text;
                    }, ['row', 'school_id', 'lot', 'grade', 'package', 'problem']);
                    fputcsv($out, $row);
                }
                fclose($out);
            }, 'masterlist-errors.csv', ['Content-Type' => 'text/csv']);
        }
        if ($data['action'] === 'remove') {
            if (empty($data['package_key']) || empty($data['school_id'])) {
                throw ValidationException::withMessages(['school_id' => 'Choose an assignment to remove.']);
            }
            $draft['rows'] = array_values(array_filter($draft['rows'], fn ($r) => $r['school_id'] !== $data['school_id'] || $r['package_key'] !== $data['package_key']));
            unset($draft['pending']);

            return response()->json(['token' => $this->schools->putDraft($draft), 'count' => count($draft['rows'])]);
        }
        if ($data['action'] === 'reassign') {
            $plan = $this->schools->validatePlan($data['school_plan'] ?? '');
            $key = $data['package_key'] ?? '';
            if (! in_array($key, array_column($plan['packages'], 'key'), true)) {
                throw ValidationException::withMessages(['package_key' => 'Choose an existing package.']);
            }
            $found = false;
            foreach ($draft['rows'] as &$row) {
                if ($row['school_id'] === ($data['school_id'] ?? '') && $row['package_key'] === $key) {
                    throw ValidationException::withMessages(['package_key' => 'This school is already assigned to the selected package.']);
                }
                if ($row['school_id'] === ($data['school_id'] ?? '') && $row['package_key'] === ($data['old_package_key'] ?? '')) {
                    $row['package_key'] = $key;
                    $found = true;
                }
            }
            unset($row);
            if (! $found) {
                throw ValidationException::withMessages(['school_id' => 'This assignment no longer exists.']);
            }
            unset($draft['pending']);

            return response()->json(['token' => $this->schools->putDraft($draft)]);
        }
        $rows = array_values(array_filter($draft['rows'], fn ($r) => empty($data['package_key']) || $r['package_key'] === $data['package_key']));
        $ids = array_unique(array_column($rows, 'school_id'));
        $matches = [];
        if (! empty($data['search']) || ! empty($data['region']) || ! empty($data['division']) || ! empty($data['municipality'])) {
            foreach (array_chunk($ids, 500) as $chunk) {
                $query = School::query()->whereIn('school_id', $chunk);
                if (! empty($data['search'])) {
                    $query->where(fn ($q) => $q->where('school_id', 'like', '%'.$data['search'].'%')->orWhere('school_name', 'like', '%'.$data['search'].'%'));
                }
                foreach (['region', 'division', 'municipality'] as $field) {
                    if (! empty($data[$field])) {
                        $query->where($field, $data[$field]);
                    }
                }
                foreach ($query->pluck('school_id') as $id) {
                    $matches[$id] = true;
                }
            }
            $rows = array_values(array_filter($rows, fn ($r) => isset($matches[$r['school_id']])));
        }
        $page = $data['page'] ?? 1;
        $slice = array_slice($rows, ($page - 1) * 25, 25);
        $schools = School::query()->whereIn('school_id', array_column($slice, 'school_id'))->get(['school_id', 'school_name', 'region', 'division', 'municipality', 'address'])->keyBy('school_id');

        return response()->json(['rows' => array_map(fn ($r) => array_merge($r, ['school' => $schools->get($r['school_id'])]), $slice), 'total' => count($rows), 'page' => $page, 'counts' => array_count_values(array_column($draft['rows'], 'package_key')), 'schools' => count(array_unique(array_column($draft['rows'], 'school_id')))]);
    }
}
