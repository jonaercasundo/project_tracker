<?php

use App\Http\Controllers\JarvisIntegrationController;
use App\Http\Middleware\PreventIntegrationCaching;
use Illuminate\Support\Facades\Route;

Route::prefix('settings/integrations/jarvis')->name('integrations.jarvis.')
    ->middleware(['auth', 'role:Administrator', PreventIntegrationCaching::class])
    ->group(function () {
        Route::get('/', [JarvisIntegrationController::class, 'index'])->name('index');
        Route::post('/', [JarvisIntegrationController::class, 'store'])->name('store');
        Route::put('/', [JarvisIntegrationController::class, 'update'])->name('update');
        Route::delete('/', [JarvisIntegrationController::class, 'destroy'])->name('destroy');
    });
