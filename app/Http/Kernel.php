<?php

namespace App\Http;

use App\Http\Middleware\EnsureActiveUser;
use App\Http\Middleware\RoleMiddleware;
use Illuminate\Foundation\Http\Kernel as HttpKernel;

class Kernel extends HttpKernel
{
    /**
     * The application's route middleware aliases.
     */
    protected $middlewareAliases = [
        'role' => RoleMiddleware::class,
        'active.user' => EnsureActiveUser::class,
    ];
}
