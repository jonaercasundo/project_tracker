<?php

namespace App\Services;

use App\Models\User;

class DashboardService
{
    public static function route(User $user): string
    {
        $map = [
            'user' => 'projects.dashboard',
            'finance'       => 'finance.dashboard',
            'Administrator'       => 'admin.dashboard',
        ];

        foreach ($map as $role => $route) {
            if ($user->hasRole($role)) {
                return route($route);
            }
        }

        return route('dashboard');
    }
}