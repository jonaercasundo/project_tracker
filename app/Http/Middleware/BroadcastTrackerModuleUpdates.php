<?php

namespace App\Http\Middleware;

use App\Services\TrackerModuleResolver;
use App\Services\TrackerUpdateBroadcaster;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class BroadcastTrackerModuleUpdates
{
    public function __construct(
        private TrackerModuleResolver $moduleResolver,
        private TrackerUpdateBroadcaster $broadcaster,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        return $next($request);
    }

    public function terminate(Request $request, Response $response): void
    {
        $user = $request->user();

        if (! $user || ! $this->moduleResolver->isSuccessfulMutation($request, $response->getStatusCode())) {
            return;
        }

        $modules = $this->moduleResolver->forRequest($request);
        $companyId = $request->session()->get('company_id');

        if ($modules === [] || ! is_numeric($companyId) || ! $user->belongsToCompany((int) $companyId)) {
            return;
        }

        $this->broadcaster->forCompany((int) $companyId, $modules);
    }
}
