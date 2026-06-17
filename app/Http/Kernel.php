<?php

namespace App\Http;

use Illuminate\Foundation\Http\Kernel as HttpKernel;

class Kernel extends HttpKernel
{
    /**
     * The application's route middleware aliases.
     */
    protected $middlewareAliases = [
        'role' => \App\Http\Middleware\RoleMiddleware::class,
    ];
}