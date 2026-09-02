<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckCompany
{
    public function handle(
        Request $request,
        Closure $next,
        $company
    ): Response {

        $user = $request->user();

        if (!$user) {
            abort(403, 'CHECK COMPANY: User not authenticated.');
        }

        $requiredCompany = $user->companies()
            ->where('companies.code', $company)
            ->where('companies.is_active', true)
            ->first();

        if (!$requiredCompany) {
            abort(403, 'CHECK COMPANY: User does not have access to ' . $company);
        }

        $selectedCompanyId = session('company_id');

        if (!$selectedCompanyId) {
            abort(403, 'CHECK COMPANY: No company selected.');
        }

        if ((int) $selectedCompanyId !== (int) $requiredCompany->company_id) {
            abort(403, 'CHECK COMPANY: Wrong company selected.');
        }

        return $next($request);
    }
}