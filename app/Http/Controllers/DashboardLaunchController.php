<?php

namespace App\Http\Controllers;

use App\Services\DashboardService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DashboardLaunchController extends Controller
{
    public function __invoke(Request $request, DashboardService $dashboards): RedirectResponse
    {
        return $dashboards->redirect($request);
    }
}
