<?php

namespace App\Http\Controllers;

use App\Models\Company;
use Illuminate\Http\Request;

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
    public function switch(Request $request)
    {
        $validated = $request->validate([
            'company_id' => [
                'required',
                'integer',
                'exists:companies,company_id',
            ],
        ]);

        $user = auth()->user();

        // Security check:
        // The user can only switch to a company they belong to.
        if (!$user->belongsToCompany($validated['company_id'])) {
            abort(403, 'You do not have access to this company.');
        }

        session([
            'company_id' => $validated['company_id'],
        ]);

        return back()->with(
            'success',
            'Company switched successfully.'
        );
    }
}