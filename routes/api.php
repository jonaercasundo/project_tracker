<?php

use App\Http\Controllers\JarvisReadController;
use App\Http\Controllers\JarvisTestController;
use App\Http\Middleware\EnsureJarvisCompany;
use App\Http\Middleware\EnsureJarvisToken;
use Illuminate\Support\Facades\Route;

Route::prefix('jarvis')->name('jarvis.')
    ->middleware(['auth:sanctum', EnsureJarvisToken::class, 'throttle:60,1'])
    ->group(function () {
        Route::get('/test', JarvisTestController::class)->name('test');
        Route::middleware(EnsureJarvisCompany::class)->group(function () {
            Route::get('/dashboard', [JarvisReadController::class, 'dashboard'])->name('dashboard');
            Route::get('/projects', [JarvisReadController::class, 'projects'])->name('projects');
            Route::get('/projects/delivery-progress', [JarvisReadController::class, 'deliveryProgress'])->name('projects.delivery-progress');
            Route::get('/projects/{id}', [JarvisReadController::class, 'project'])->whereNumber('id')->name('projects.show');
            Route::get('/operation-masterlist', [JarvisReadController::class, 'masterlist'])->name('masterlist');
            Route::get('/deliveries', [JarvisReadController::class, 'deliveries'])->name('deliveries');
            Route::get('/inventory', [JarvisReadController::class, 'inventory'])->name('inventory');
            Route::get('/warehouses', [JarvisReadController::class, 'warehouses'])->name('warehouses');
            Route::get('/lots', [JarvisReadController::class, 'lots'])->name('lots');
            Route::get('/packages', [JarvisReadController::class, 'packages'])->name('packages');
        });
    });
