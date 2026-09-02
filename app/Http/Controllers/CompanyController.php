<?php

namespace App\Http\Controllers;

use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;

class CompanyController extends Controller
{
    /**
     * Display all companies.
     */
    public function index()
    {
        $companies = Company::orderBy('name')->get();

        return view('admin.companies.index', compact('companies'));
    }

    /**
     * Show the form for creating a company.
     */
    public function create()
    {
        return view('admin.companies.create');
    }

    /**
     * Store a new company.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
            ],
            'code' => [
                'required',
                'string',
                'max:50',
                'unique:companies,code',
            ],
            'is_active' => [
                'nullable',
                'boolean',
            ],
        ]);

        $validated['is_active'] = $request->boolean('is_active');

        Company::create($validated);

        return redirect()
            ->route('companies.index')
            ->with('success', 'Company created successfully.');
    }

    /**
     * Show the form for editing a company.
     */
    public function edit(Company $company)
    {
        return view('admin.companies.edit', compact('company'));
    }

    /**
     * Update a company.
     */
    public function update(Request $request, Company $company)
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
            ],
            'code' => [
                'required',
                'string',
                'max:50',
                'unique:companies,code,' . $company->company_id . ',company_id',
            ],
            'is_active' => [
                'nullable',
                'boolean',
            ],
        ]);

        $validated['is_active'] = $request->boolean('is_active');

        $company->update($validated);

        return redirect()
            ->route('admin.companies.index')
            ->with('success', 'Company updated successfully.');
    }

    /**
     * Delete a company.
     */
    public function destroy(Company $company)
    {
        $company->delete();

        return redirect()
            ->route('admin.companies.index')
            ->with('success', 'Company deleted successfully.');
    }

    /**
     * Switch company while already logged in.
     */
public function switch(Request $request): RedirectResponse
{
    $validated = $request->validate([
        'company_id' => [
            'required',
            'integer',
            'exists:companies,company_id',
        ],
    ]);

    $user = auth()->user();

    $company = $user->companies()
        ->where('companies.company_id', $validated['company_id'])
        ->where('companies.is_active', true)
        ->first();

    if (!$company) {
        abort(403, 'You do not have access to this company.');
    }

    session([
        'company_id' => $company->company_id,
    ]);

    return $this->redirectForCompany($company, $user);
}

    /**
     * Show company selection screen.
     */
    public function select(): RedirectResponse|\Illuminate\View\View
    {
        $user = auth()->user();

        $companies = $user->companies()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        /*
        |--------------------------------------------------------------------------
        | No active company
        |--------------------------------------------------------------------------
        */
        if ($companies->isEmpty()) {

            auth()->logout();

            return redirect()
                ->route('login')
                ->withErrors([
                    'email' => 'Your account is not assigned to any active company.',
                ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Only one company
        |--------------------------------------------------------------------------
        */
        if ($companies->count() === 1) {

            $company = $companies->first();

            session([
                'company_id' => $company->company_id,
            ]);

            return $this->redirectForCompany($company, $user);
        }

        /*
        |--------------------------------------------------------------------------
        | Multiple companies
        |--------------------------------------------------------------------------
        */
        return view('admin.company.select', compact('companies'));
    }

    /**
     * Store selected company.
     */
    public function selectStore(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'company_id' => [
                'required',
                'integer',
                'exists:companies,company_id',
            ],
        ]);

        $user = auth()->user();

        /*
        |--------------------------------------------------------------------------
        | Security check
        |--------------------------------------------------------------------------
        */
        if (!$user->belongsToCompany($validated['company_id'])) {
            abort(403, 'You do not have access to this company.');
        }

        /*
        |--------------------------------------------------------------------------
        | Get selected active company
        |--------------------------------------------------------------------------
        */
        $company = Company::where('company_id', $validated['company_id'])
            ->where('is_active', true)
            ->first();

        if (!$company) {
            abort(403, 'This company is inactive.');
        }

        /*
        |--------------------------------------------------------------------------
        | Save selected company
        |--------------------------------------------------------------------------
        */
        session([
            'company_id' => $company->company_id,
        ]);

        /*
        |--------------------------------------------------------------------------
        | Redirect according to company + role
        |--------------------------------------------------------------------------
        */
        return $this->redirectForCompany($company, $user);
    }

    /**
     * Determine where the user should go based on company and role.
     */
    private function redirectForCompany(
        Company $company,
        $user
    ): RedirectResponse {

        /*
        |--------------------------------------------------------------------------
        | MMC
        |--------------------------------------------------------------------------
        */
        if ($company->code === 'MMC') {

            if ($user->hasRole('user')) {
                return redirect()->route('projects.dashboard');
            }

            if ($user->hasRole('Administrator')) {
                return redirect()->route('admin.dashboard');
            }

            if ($user->hasRole('finance')) {
                return redirect()->route('finance.dashboard');
            }

            if ($user->hasRole('IT')) {
                return redirect()->route('it.dashboard');
            }

            if ($user->hasRole('Warehouse_officer')) {
                return redirect()->route('warehouse.dashboard');
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Metroinc / MI
        |--------------------------------------------------------------------------
        */
        if ($company->code === 'MI') {

            if ($user->hasRole('user')) {
                return redirect()->route('mi_app.dashboard');
            }

            if ($user->hasRole('Administrator')) {
                return redirect()->route('admin.dashboard');
            }
        }

        /*
        |--------------------------------------------------------------------------
        | No available module
        |--------------------------------------------------------------------------
        */
        return redirect()->route('site.maintenance');
    }
}