<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use App\Services\DashboardService;

class AuthenticatedSessionController extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }
    private function redirectByRole($user): RedirectResponse
    {
        $companyId = session('company_id');

        $company = $user->companies()
            ->where('companies.company_id', $companyId)
            ->where('is_active', true)
            ->first();

        if (!$company) {
            return redirect()->route('site.maintenance');
        }

        /*
        |--------------------------------------------------------------------------
        | MI
        |--------------------------------------------------------------------------
        */
        if ($company->code === 'MI') {

            if ($user->hasRole('Administrator')) {
                return redirect()->route('admin.dashboard');
            }
            if ($user->hasRole('user')) {
                return redirect()->route('mi_app.dashboard');
            }
            if ($user->hasRole('accounting')) {
                return redirect()->route('accounting.mi.dashboard');
            }
        }

        /*
        |--------------------------------------------------------------------------
        | MMC
        |--------------------------------------------------------------------------
        */
        if ($company->code === 'MMC') {

            if ($user->hasRole('Administrator')) {
                return redirect()->route('admin.dashboard');
            }

            if ($user->hasRole('user')) {
                return redirect()->route('projects.dashboard');
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

        return redirect()->route('site.maintenance');
    }
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        $user = $request->user();

        /*
        |--------------------------------------------------------------------------
        | Get user's active companies
        |--------------------------------------------------------------------------
        */
        $companies = $user->companies()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        /*
        |--------------------------------------------------------------------------
        | No company assigned
        |--------------------------------------------------------------------------
        */
        if ($companies->isEmpty()) {

            Auth::guard('web')->logout();

            $request->session()->invalidate();
            $request->session()->regenerateToken();

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
        | Automatically select it.
        */
        if ($companies->count() === 1) {

            session([
                'company_id' => $companies->first()->company_id,
            ]);

            return $this->redirectByRole($user);
        }

        /*
        |--------------------------------------------------------------------------
        | Multiple companies
        |--------------------------------------------------------------------------
        | Let the user choose which company to access.
        */
        return redirect()->route('company.select');
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
