<?php

namespace App\Http\Controllers;

use App\Services\AccountingWorkspace;
use Illuminate\Http\Request;
use Illuminate\View\View;

class Accounting_DashboardController extends Controller
{
    public function dashboard(Request $request, AccountingWorkspace $workspace): View
    {
        return (new AccountingWorkspaceController($workspace))->dashboard($request);
    }
}
