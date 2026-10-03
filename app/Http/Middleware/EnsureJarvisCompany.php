<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureJarvisCompany
{
    public function handle(Request $request, Closure $next): Response
    {
        $company = $request->user()->companies()
            ->where('companies.code', 'MMC')
            ->where('companies.is_active', true)
            ->first();

        abort_unless($company, 403, 'Active MMC company membership is required.');

        if ($request->query->has('company_id')) {
            abort_unless(is_scalar($request->query('company_id')) && (string) $request->query('company_id') === (string) $company->company_id, 403, 'Company access denied.');
        }

        $request->attributes->set('jarvis_company', $company);

        return $next($request);
    }
}
