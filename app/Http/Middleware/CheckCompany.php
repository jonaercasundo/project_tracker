<?php

namespace App\Http\Middleware;

use App\Services\DashboardService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckCompany
{
    public function __construct(private DashboardService $dashboards) {}

    public function handle(
        Request $request,
        Closure $next,
        string $company
    ): Response {

        $user = $request->user();

        if (! $user) {
            abort(403, 'CHECK COMPANY: User not authenticated.');
        }

        $selectedCompany = $this->dashboards->currentCompany($user, $request->session());

        if (! $selectedCompany) {
            abort(403, 'CHECK COMPANY: No authorized active company selected.');
        }

        if ($selectedCompany->code !== $company) {
            abort(403, 'CHECK COMPANY: Wrong company selected.');
        }

        return $next($request);
    }
}
