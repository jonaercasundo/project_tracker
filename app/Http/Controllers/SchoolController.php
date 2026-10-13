<?php

namespace App\Http\Controllers;

use App\Http\Requests\SaveSchoolRequest;
use App\Models\School;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\QueryException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class SchoolController extends Controller
{
    public function index(Request $request): View
    {
        $filters = self::listQuery($request);
        $query = School::query();

        if (isset($filters['search'])) {
            $query->where(function (Builder $query) use ($filters): void {
                foreach (array_keys(SaveSchoolRequest::schoolRules()) as $column) {
                    $query->orWhere($column, 'like', '%'.$filters['search'].'%');
                }
            });
        }

        foreach (['municipality', 'division', 'region'] as $column) {
            if (isset($filters[$column])) {
                $query->where($column, $filters[$column]);
            }
        }

        return view('schools.index', [
            'schools' => $query->orderBy('school_name')->orderBy('school_id')->paginate(25)->appends($filters),
            'municipalities' => $this->filterOptions('municipality'),
            'divisions' => $this->filterOptions('division'),
            'regions' => $this->filterOptions('region'),
            'filters' => $filters,
            'listQuery' => $filters,
        ]);
    }

    public function create(Request $request): View
    {
        return view('schools.create', ['school' => new School, 'listQuery' => self::listQuery($request)]);
    }

    public function store(SaveSchoolRequest $request): RedirectResponse
    {
        $listQuery = self::listQuery($request);

        try {
            if (School::query()->whereKey($request->validated('school_id'))->exists()) {
                throw ValidationException::withMessages(['school_id' => 'This school ID already exists.']);
            }
            School::query()->create($request->validated());
        } catch (UniqueConstraintViolationException $exception) {
            throw ValidationException::withMessages(['school_id' => 'This school ID already exists.']);
        } catch (QueryException $exception) {
            report($exception);

            return back()->withInput()->with('error', 'The school could not be saved. Please try again.');
        }

        return to_route('schools.index', $listQuery)->with('success', 'School added successfully.');
    }

    public function show(Request $request, School $school): View
    {
        return view('schools.show', ['school' => $school, 'listQuery' => self::listQuery($request)]);
    }

    public function edit(Request $request, School $school): View
    {
        return view('schools.edit', ['school' => $school, 'listQuery' => self::listQuery($request)]);
    }

    public function update(SaveSchoolRequest $request, School $school): RedirectResponse
    {
        $listQuery = self::listQuery($request);

        try {
            $school->update($request->safe()->except('school_id'));
        } catch (QueryException $exception) {
            report($exception);

            return back()->withInput()->with('error', 'The school could not be updated. Please try again.');
        }

        return to_route('schools.index', $listQuery)->with('success', 'School updated successfully.');
    }

    /** @return array<string, string|int> */
    public static function listQuery(Request $request): array
    {
        $data = Validator::make($request->query(), [
            'search' => ['nullable', 'string', 'max:255'],
            'municipality' => ['nullable', 'string', 'max:255'],
            'division' => ['nullable', 'string', 'max:255'],
            'region' => ['nullable', 'string', 'max:255'],
            'page' => ['nullable', 'integer', 'min:1', 'max:1000000'],
        ])->validate();

        return array_filter($data, fn (mixed $value): bool => $value !== null && $value !== '');
    }

    /** @return Collection<int, string> */
    private function filterOptions(string $column): Collection
    {
        return School::query()->whereNotNull($column)->where($column, '!=', '')->distinct()->orderBy($column)->pluck($column);
    }
}
