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

    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        $user = $request->user(); // ✅ BEST PRACTICE

        if ($user->hasRole('Administrator')) {
            return redirect()->route('admin.dashboard');
        }
        elseif ($user->hasRole('user')) {
            return redirect()->route('projects.dashboard');
        }
        elseif ($user->hasRole('finance')) {
            return redirect()->route('finance.dashboard');
        }
        elseif ($user->hasRole('IT')) {
            return redirect()->route('it.dashboard');
        } elseif ($user->hasRole('Warehouse_officer')) {
            return redirect()->route('warehouse.dashboard');
        }
        return redirect()->intended(route('dashboard', absolute: false));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
