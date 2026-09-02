<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckCompany
{
    public function handle(Request $request, Closure $next, $company)
    {
        if (!$request->user() || $request->user()->company->code !== $company) {
            abort(403);
        }

        return $next($request);
    }
}
