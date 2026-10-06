<?php

namespace App\Services;

use App\Models\Company;
use App\Models\User;
use Illuminate\Contracts\Session\Session;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardService
{
    /** @return Collection<int, Company> */
    public function availableCompanies(User $user): Collection
    {
        return $user->companies()->where('companies.is_active', true)->orderBy('companies.name')->get();
    }

    public function currentCompany(User $user, Session $session): ?Company
    {
        $companyId = $session->get('company_id');
        $company = null;

        if (is_int($companyId) || (is_string($companyId) && ctype_digit($companyId))) {
            $company = $user->companies()
                ->where('companies.company_id', $companyId)
                ->where('companies.is_active', true)
                ->first();
        }

        if (! $company) {
            $session->forget('company_id');
        }

        return $company;
    }

    public function redirect(Request $request): RedirectResponse
    {
        $user = $request->user();
        $session = $request->session();
        $session->forget('url.intended');
        $company = $this->currentCompany($user, $session);

        if ($company) {
            return $this->redirectForCompany($user, $company);
        }

        $companies = $this->availableCompanies($user);

        if ($companies->isEmpty()) {
            Auth::guard('web')->logout();
            $session->invalidate();
            $session->regenerateToken();

            return redirect()->route('login')->withErrors([
                'email' => 'Your account is not assigned to any active company.',
            ]);
        }

        if ($companies->count() > 1) {
            return redirect()->route('company.select');
        }

        $company = $companies->first();
        $session->put('company_id', $company->getKey());

        return $this->redirectForCompany($user, $company);
    }

    public function selectCompany(Request $request, int $companyId): RedirectResponse
    {
        $user = $request->user();
        $company = $user->companies()
            ->where('companies.company_id', $companyId)
            ->where('companies.is_active', true)
            ->first();

        abort_unless($company, 403, 'You do not have access to this active company.');

        $request->session()->regenerate();
        $request->session()->put('company_id', $company->getKey());
        $request->session()->forget('url.intended');

        return $this->redirectForCompany($user, $company);
    }

    private function redirectForCompany(User $user, Company $company): RedirectResponse
    {
        $map = match ($company->code) {
            'MMC' => [
                'Administrator' => 'admin.dashboard',
                'user' => 'projects.dashboard',
                'finance' => 'finance.dashboard',
                'IT' => 'it.dashboard',
                'Warehouse_officer' => 'warehouse.dashboard',
            ],
            'MI' => [
                'Administrator' => 'admin.dashboard',
                'user' => 'mi_app.dashboard',
                'accounting' => 'accounting.mi.dashboard',
            ],
            default => [],
        };

        foreach ($map as $role => $route) {
            if ($user->hasRole($role)) {
                return redirect()->route($route);
            }
        }

        if ($company->code === 'MI' && $user->canAccessMIApprovals()) {
            return redirect()->route('mi.approvals');
        }

        return redirect()->route('site.maintenance');
    }
}
