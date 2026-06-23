<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\RoleAccessPermissionController;
use App\Http\Controllers\ProfileController;
use Spatie\Permission\Models\Role;
use App\Models\User;
use App\Http\Controllers\UserController;
use App\Http\Controllers\DeliveryController;
use App\Http\Controllers\PplFormController;
use App\Http\Controllers\TikTokController;
use App\Http\Controllers\LocationController;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
/*
|--------------------------------------------------------------------------
| PUBLIC ROUTE
|--------------------------------------------------------------------------
*/
Route::get('/', function () {
    return view('welcome');
});

/*
|--------------------------------------------------------------------------
| AUTH ROUTES
|--------------------------------------------------------------------------
*/
Route::middleware(['auth'])->group(function () {

    /*
    |--------------------------------------------------------------------------
    | ADMIN ROUTES
    |--------------------------------------------------------------------------
    */
    Route::middleware(['role:Administrator'])->group(function () {
        Route::post('/users', [UserController::class, 'store'])
        ->name('users.store');
        Route::delete('/users/{user}', [UserController::class, 'destroy'])
        ->name('users.destroy');
        // Dashboard
        Route::get('/admin/dashboard', function () {
            $users = User::with('roles')->get();
            $roles = Role::all();
            return view('admin.dashboard', compact('users', 'roles'));
        })->name('admin.dashboard');

        /*
        |--------------------------------------------------------------------------
        | ROLE ACCESS MANAGEMENT
        |--------------------------------------------------------------------------
        */
        Route::get('/roleaccess', [RoleAccessPermissionController::class, 'edit'])
            ->name('roleaccess.edit');

        Route::post('/roleaccess/update', [RoleAccessPermissionController::class, 'update'])
        ->name('roleaccess.update');
        Route::delete('/roleaccess', [RoleAccessPermissionController::class, 'destroy'])
            ->name('roleaccess.destroy');
    });
    /*
        |--------------------------------------------------------------------------
        | PROJECTS
        |--------------------------------------------------------------------------
    */
    Route::middleware(['role:user'])->group(function () {

        Route::get('/projects/dashboard', function () {
            return view('projects.dashboard', [
                'totalProjects' => \App\Models\Project::count(),
                'pendingProjects' => \App\Models\Project::where('status', 'Pending')->count(),
                'deliveredProjects' => \App\Models\Project::where('status', 'Delivered')->count(),
            ]);
        })->name('projects.dashboard');

        Route::get('/projects/{project}/deliveries', [DeliveryController::class, 'index'])
        ->name('deliveries.index');

        Route::get('/projects', [ProjectController::class, 'index'])
            ->name('projects.index');

        Route::post('/projects', [ProjectController::class, 'store'])
            ->name('projects.store');

        Route::put('/projects/{project}', [ProjectController::class, 'update'])
            ->name('projects.update');

        Route::post('/projects/filter', [ProjectController::class, 'filter'])
            ->name('projects.filter');
        Route::get('/projects/{project:project_id}', [ProjectController::class, 'show'])
        ->name('projects.show');


        Route::get('/deliveries/tracking', [DeliveryController::class, 'index'])
        ->name('deliveries.tracking');
        Route::get('/api/regions', [LocationController::class, 'regions']);
        Route::get('/api/divisions', [LocationController::class, 'divisions']);
        Route::get('/api/municipalities', [LocationController::class, 'municipalities']);
        Route::get('/api/lots', function (Request $request) {
            return DB::table('lot')
                ->where('project_id', $request->project)
                ->select('lot_id', 'lot_name')
                ->orderBy('lot_name')
                ->get();
        });
    });

    /*
    |--------------------------------------------------------------------------
    | ADMIN ROUTES
    |--------------------------------------------------------------------------
    */
    Route::middleware(['role:finance'])->group(function () {

        Route::get('/ppl-forms', [PplFormController::class, 'index'])
            ->name('ppl_forms.index');

        Route::get('/ppl-forms/create', [PplFormController::class, 'create'])
            ->name('ppl_forms.create');

        Route::post('/ppl-forms/store', [PplFormController::class, 'store'])
            ->name('ppl_forms.store');

        // ✅ FIXED HERE
        Route::get('/ppl-forms/{id}', [PplFormController::class, 'show'])
            ->name('ppl_forms.show');

        Route::post('/ppl-forms/import', [PplFormController::class, 'import'])
            ->name('ppl_forms.import');

        Route::get('/dashboard', function () {
            return view('finance.dashboard');
        })->name('finance.dashboard');

    });
    /*
        |--------------------------------------------------------------------------
        | Change Profile
        |--------------------------------------------------------------------------
    */
    Route::get('/profile', [ProfileController::class, 'edit'])
        ->name('profile.edit');

    Route::patch('/profile', [ProfileController::class, 'update'])
        ->name('profile.update');

    Route::delete('/profile', [ProfileController::class, 'destroy'])
        ->name('profile.destroy');
});
Route::get('/tiktok/trends', [TikTokController::class, 'fetchHomeDecorTrends'])
    ->name('tiktok.trends');
Route::get('/pinterest/trends', [TikTokController::class, 'fetchHomePinterestTrends'])
    ->name('pinterest.trends');
Route::get('/google/trends', [TikTokController::class, 'fetchHomeGoogleTrends'])
    ->name('google.trends');
require __DIR__.'/auth.php';