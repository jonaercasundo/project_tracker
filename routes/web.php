<?php

use App\Http\Controllers\Accounting_DashboardController;
use App\Http\Controllers\Accounting_LiquidationController;
use App\Http\Controllers\AccountingWorkspaceController;
use App\Http\Controllers\ActionCrawlerController;
use App\Http\Controllers\BiddingController;
use App\Http\Controllers\BiddingDocumentController;
use App\Http\Controllers\BiddingFolderController;
use App\Http\Controllers\BiddingSchoolController;
use App\Http\Controllers\BillingDashboardController;
use App\Http\Controllers\BillingRecordController;
use App\Http\Controllers\BillingScannerController;
use App\Http\Controllers\BudgetRequestController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\DashboardLaunchController;
use App\Http\Controllers\DeliveryController;
use App\Http\Controllers\DeliveryMonitoringController;
use App\Http\Controllers\DeliveryReceiveController;
use App\Http\Controllers\EntryController;
use App\Http\Controllers\Finance_Item;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\ItemManagementImportController;
use App\Http\Controllers\ITinventoryController;
use App\Http\Controllers\LiquidationController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\LogisticsChannelController;
use App\Http\Controllers\MIAppController;
use App\Http\Controllers\MIApprovalController;
use App\Http\Controllers\MmcPplWorkbookImportController;
use App\Http\Controllers\OperationsLifecycleController;
use App\Http\Controllers\PplFormController;
use App\Http\Controllers\ProductScanController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\ProjectDashboardController;
use App\Http\Controllers\ProjectFinancialPlanController;
use App\Http\Controllers\PublicProductController;
use App\Http\Controllers\QuotationController;
use App\Http\Controllers\RoleAccessPermissionController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\SchoolController;
use App\Http\Controllers\SchoolImportController;
use App\Http\Controllers\SchoolManagementImportController;
use App\Http\Controllers\TikTokController;
use App\Http\Controllers\TravelLiquidationController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\Warehouse\WarehouseInventoryController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Models\Role;

/*
|--------------------------------------------------------------------------
| PUBLIC ROUTE
|--------------------------------------------------------------------------
*/
Route::get('/', function () {
    return view('welcome');
})->name('welcome');

/*
|--------------------------------------------------------------------------
| AUTH ROUTES
|--------------------------------------------------------------------------
*/
Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard/launch', DashboardLaunchController::class)->name('dashboard.launch');

    Route::get('/deliveries/monitoring', [DeliveryMonitoringController::class, 'index'])
        ->middleware(['company.context:MMC', 'role:user,Administrator'])
        ->name('deliveries.monitoring');
    Route::get('/deliveries/monitoring/logistics', [DeliveryMonitoringController::class, 'logistics'])
        ->middleware(['company.context:MMC', 'role:user,Administrator'])
        ->name('deliveries.monitoring.logistics');
    Route::get('/deliveries/monitoring/locations', [DeliveryMonitoringController::class, 'locations'])
        ->middleware(['company.context:MMC', 'role:user,Administrator'])
        ->name('deliveries.monitoring.locations');
    Route::get('/deliveries/monitoring/{project}/details', [DeliveryMonitoringController::class, 'details'])
        ->whereNumber('project')
        ->middleware(['company.context:MMC', 'role:user,Administrator'])
        ->name('deliveries.monitoring.details');
    Route::get('/site-maintenance', function () {
        return view('site.maintenance');
    })->name('site.maintenance');
    // Company selection after login
    Route::get('/company/select', [CompanyController::class, 'select'])
        ->name('company.select');

    Route::post('/company/select', [CompanyController::class, 'selectStore'])
        ->name('company.select.store');

    // Switch company from dashboard
    Route::post('/company/switch', [CompanyController::class, 'switch'])
        ->name('company.switch');
    /*
    |--------------------------------------------------------------------------
    | ADMIN ROUTES
    |--------------------------------------------------------------------------
    */
    Route::middleware(['role:user'])->group(function () {

        Route::middleware(['company.context:MMC'])->prefix('logistics-channel')->name('logistics-channel.')->group(function () {
            Route::get('/', [LogisticsChannelController::class, 'index'])->name('index');
            Route::post('/integrations', [LogisticsChannelController::class, 'store'])->middleware('throttle:20,1')->name('integrations.store');
            Route::patch('/integrations/{integration}', [LogisticsChannelController::class, 'update'])->middleware('throttle:20,1')->name('integrations.update');
            Route::post('/integrations/{integration}/rotate', [LogisticsChannelController::class, 'rotate'])->middleware('throttle:10,1')->name('integrations.rotate');
            Route::post('/integrations/{integration}/credentials/{credential}/revoke', [LogisticsChannelController::class, 'revoke'])->middleware('throttle:10,1')->name('integrations.credentials.revoke');
            Route::post('/integrations/adopt-environment', [LogisticsChannelController::class, 'adopt'])->middleware('throttle:5,1')->name('integrations.adopt');
            Route::get('/schools', [LogisticsChannelController::class, 'schools'])->name('schools');
            Route::post('/events/{event}/retry', [LogisticsChannelController::class, 'retryEvent'])->whereNumber('event')->middleware('throttle:10,1')->name('events.retry');
        });

    });
    Route::middleware(['role:Administrator'])->group(function () {

        Route::post('/users', [UserController::class, 'store'])
            ->name('users.store');

        Route::delete('/users/{user}', [UserController::class, 'destroy'])
            ->name('users.destroy');
        /*
            |--------------------------------------------------------------------------
            | ADMIN DASHBOARD
            |--------------------------------------------------------------------------
            */

        Route::get('/admin/dashboard', [
            RoleAccessPermissionController::class,
            'index',
        ])->name('admin.dashboard');

        /*
        |--------------------------------------------------------------------------
        | ROLE ACCESS MANAGEMENT
        |--------------------------------------------------------------------------
        */

        Route::get('/roleaccess', [
            RoleAccessPermissionController::class,
            'edit',
        ])->name('roleaccess.index');

        Route::patch('/roleaccess/update', [
            RoleAccessPermissionController::class,
            'update',
        ])->name('roleaccess.update');

        Route::delete('/roleaccess', [RoleAccessPermissionController::class, 'destroy'])->name('roleaccess.destroy');

        Route::get('/companies', [CompanyController::class, 'index'])->name('companies.index');
        Route::get('/companies/create', [CompanyController::class, 'create'])->name('companies.create');
        Route::post('/companies', [CompanyController::class, 'store'])->name('companies.store');
        Route::get('/companies/{company}/edit', [CompanyController::class, 'edit'])->name('companies.edit');
        Route::put('/companies/{company}', [CompanyController::class, 'update'])->name('companies.update');
        Route::delete('/companies/{company}', [CompanyController::class, 'destroy'])->name('companies.destroy');
        // Role Management
        Route::get('/roles', [RoleController::class, 'index'])->name('roles.index');
        Route::get('/roles/create', [RoleController::class, 'create'])->name('roles.create');
        Route::post('/roles', [RoleController::class, 'store'])->name('roles.store');
        Route::get('/roles/{role}/edit', [RoleController::class, 'edit'])->name('roles.edit');
        Route::put('/roles/{role}', [RoleController::class, 'update'])->name('roles.update');
        Route::delete('/roles/{role}', [RoleController::class, 'destroy'])->name('roles.destroy');

        Route::post('/users/reset-password', [UserController::class, 'resetPassword'])->name('users.reset-password');
    });
    /*
        |--------------------------------------------------------------------------
        | PROJECTS
        |--------------------------------------------------------------------------
    */
    Route::middleware([
        'auth',
        'company.context:MMC',
        'role:user',
    ])->group(function () {

        Route::get('/projects/dashboard', [ProjectDashboardController::class, 'index'])
            ->name('projects.dashboard');

        Route::get('/operations/lifecycle', [OperationsLifecycleController::class, 'index'])
            ->name('operations.lifecycle.index');
        Route::get('/operations/lifecycle/reconcile', [OperationsLifecycleController::class, 'reconcile'])
            ->name('operations.lifecycle.reconcile');
        Route::post('/operations/lifecycle/reconcile', [OperationsLifecycleController::class, 'confirmSourceLink'])
            ->name('operations.lifecycle.reconcile.confirm');
        Route::post('/operations/lifecycle', [OperationsLifecycleController::class, 'store'])
            ->name('operations.lifecycle.store');
        Route::get('/operations/lifecycle/{track}', [OperationsLifecycleController::class, 'show'])
            ->whereNumber('track')->name('operations.lifecycle.show');
        Route::patch('/operations/lifecycle/{track}', [OperationsLifecycleController::class, 'update'])
            ->whereNumber('track')->name('operations.lifecycle.update');
        Route::post('/operations/lifecycle/{track}/verify-noa', [OperationsLifecycleController::class, 'verifyNoa'])
            ->whereNumber('track')->name('operations.lifecycle.verify-noa');
        Route::post('/operations/lifecycle/{track}/verify-ntp', [OperationsLifecycleController::class, 'verifyNtp'])
            ->whereNumber('track')->name('operations.lifecycle.verify-ntp');
        Route::post('/operations/lifecycle/{track}/tasks', [OperationsLifecycleController::class, 'storeTask'])
            ->whereNumber('track')->name('operations.lifecycle.tasks.store');
        Route::patch('/operations/lifecycle/{track}/tasks/{task}', [OperationsLifecycleController::class, 'updateTask'])
            ->whereNumber('track')->whereNumber('task')->name('operations.lifecycle.tasks.update');

        Route::get('/projects/{project}/deliveries', [DeliveryController::class, 'index'])
            ->name('deliveries.index');

        Route::get('/projects', [ProjectController::class, 'index'])
            ->name('projects.index');

        Route::post('/projects', [ProjectController::class, 'store'])
            ->name('projects.store');

        Route::get('/projects/{project:project_id}/edit', [ProjectController::class, 'edit'])
            ->name('projects.edit');

        Route::put('/projects/{project:project_id}', [ProjectController::class, 'update'])
            ->name('projects.update');

        Route::delete('/projects/{project:project_id}', [ProjectController::class, 'destroy'])
            ->name('projects.destroy');
        Route::post('/deliveries/qr-generate', [DeliveryController::class, 'generate']);

        Route::post('/projects/filter', [ProjectController::class, 'filter'])
            ->name('projects.filter');
        Route::get('/projects/{project:project_id}/schools-data', [ProjectController::class, 'schoolsData'])->name('projects.schools-data');
        Route::get('/projects/{project:project_id}/items-data', [ProjectController::class, 'itemsData'])->name('projects.items-data');
        Route::get('/projects/{project:project_id}/packages-data', [ProjectController::class, 'packagesData'])->name('projects.packages-data');
        Route::get('/projects/{project:project_id}/lots-data', [ProjectController::class, 'lotsData'])->name('projects.lots-data');
        Route::get('/projects/{project:project_id}/keystages-data', [ProjectController::class, 'keystagesData'])->name('projects.keystages-data');
        Route::get('/projects/{project:project_id}/detail-options', [ProjectController::class, 'detailOptions'])->name('projects.detail-options');
        /*
            |--------------------------------------------------------------------------
            | Allocation List Import
            |--------------------------------------------------------------------------
        */

        Route::get('/schools/import', [SchoolManagementImportController::class, 'index'])->name('schools.import');
        Route::post('/schools/import/preview', [SchoolManagementImportController::class, 'preview'])->middleware('throttle:10,1')->name('schools.import.preview');
        Route::post('/schools/import/confirm', [SchoolManagementImportController::class, 'confirm'])->middleware('throttle:10,1')->name('schools.import.confirm');
        Route::get('/schools/template', [SchoolManagementImportController::class, 'template'])->name('schools.template');
        Route::get('/schools/view/{school}', [SchoolController::class, 'show'])->where('school', '.+')->name('schools.show');
        Route::get('/schools/edit/{school}', [SchoolController::class, 'edit'])->where('school', '.+')->name('schools.edit');
        Route::match(['put', 'patch'], '/schools/record/{school}', [SchoolController::class, 'update'])->where('school', '.+')->name('schools.update');
        Route::resource('schools', SchoolController::class)->only(['index', 'create', 'store']);

        Route::get('/school/import', [SchoolImportController::class, 'index'])->name('school.index');
        Route::post('/school/preview', [SchoolImportController::class, 'preview'])->name('school.preview');
        Route::post('/school/save', [SchoolImportController::class, 'import'])->name('school.import');
        Route::get('/test-python', [SchoolImportController::class, 'testPython']);
        Route::get('/extract-schools', [SchoolImportController::class, 'extractSchools']);
        /*
            |--------------------------------------------------------------------------
            | ITEMS
            |--------------------------------------------------------------------------
        */
        Route::get('/items/import', [ItemManagementImportController::class, 'index'])->name('catalog-items.import');
        Route::post('/items/import/preview', [ItemManagementImportController::class, 'preview'])
            ->middleware('throttle:10,1')->name('catalog-items.import.preview');
        Route::post('/items/import/confirm', [ItemManagementImportController::class, 'confirm'])
            ->middleware('throttle:10,1')->name('catalog-items.import.confirm');
        Route::get('/items/template', [ItemManagementImportController::class, 'template'])->name('catalog-items.template');
        Route::get('/items', [ItemController::class, 'index'])->name('catalog-items.index');
        Route::get('/items/create', [ItemController::class, 'create'])->name('catalog-items.create');
        Route::post('/items', [ItemController::class, 'store'])->name('catalog-items.store');
        Route::get('/items/{item}/edit', [ItemController::class, 'edit'])->whereNumber('item')->name('catalog-items.edit');
        Route::match(['put', 'patch'], '/items/{item}', [ItemController::class, 'update'])->whereNumber('item')->name('catalog-items.update');
        Route::get('/items/{item}', [ItemController::class, 'show'])->whereNumber('item')->name('catalog-items.show');
        /*
            |--------------------------------------------------------------------------
            | DELIVERIES
            |--------------------------------------------------------------------------
        */
        Route::get('/scan/{id}', [DeliveryController::class, 'scan'])->name('scan.page');
        Route::get('/entry/{id}/{delivery_id}', [EntryController::class, 'show'])
            ->name('entry.show');

        Route::get('/deliveries/tracking', [DeliveryController::class, 'index'])
            ->name('deliveries.tracking');

        Route::get('/deliveries/pdf', [DeliveryController::class, 'generate'])
            ->name('deliveries.pdf');

        Route::get('/api/lots', function (Request $request) {
            return DB::table('lot')
                ->where('project_id', $request->project)
                ->select('lot_id', 'lot_name')
                ->orderBy('lot_name')
                ->get();
        });

        Route::get('/deliveries/batch-qr', [DeliveryController::class, 'batchQr'])
            ->name('deliveries.batch-qr');
        Route::post('/deliveries/labels', [DeliveryController::class, 'generateLabels'])
            ->name('deliveries.labels');

        Route::get('/filter/lots', [DeliveryController::class, 'getLots']);
        Route::get('/filter/regions', [DeliveryController::class, 'getRegions']);
        Route::get('/filter/divisions', [DeliveryController::class, 'getDivisions']);
        Route::get('/filter/municipalities', [DeliveryController::class, 'getMunicipalities']);
        /*
            |--------------------------------------------------------------------------
            | INVENTORIES
            |--------------------------------------------------------------------------
        */
        Route::get('/inventories/create', [InventoryController::class, 'create'])
            ->name('inventory.create');
        Route::get('/operation_inventories', [InventoryController::class, 'operation_index'])
            ->name('operation_inventory.index');
        Route::get('/inventories', [InventoryController::class, 'index'])
            ->name('inventory.index');
        Route::get('/inventories/{inventory}/edit', [InventoryController::class, 'edit'])
            ->name('inventory.edit');
        Route::put('/inventories/{inventory}', [InventoryController::class, 'update'])
            ->name('inventory.update');
        Route::get('/inventories/{inventory}/show', [InventoryController::class, 'show'])
            ->name('inventory.show');
        Route::get('/inventory-summary', [InventoryController::class, 'summary'])
            ->name('inventory.summary');

        Route::get('/inventory-history', [InventoryController::class, 'history'])
            ->name('inventory.history');
        Route::post('/inventory', [InventoryController::class, 'store'])
            ->name('inventory.store');

        /*
            |--------------------------------------------------------------------------
            | BIDDING
            |--------------------------------------------------------------------------
        */

        Route::get('project/bidding', [BiddingController::class, 'project_index'])
            ->name('project.bidding.index');

        Route::get('project/bidding/catalog', [BiddingController::class, 'catalog'])->name('project.bidding.catalog');

        Route::get('project/bidding/schools/search', [BiddingSchoolController::class, 'search'])->name('project.bidding.schools.search');
        Route::post('project/bidding/schools/workspace', [BiddingSchoolController::class, 'workspace'])->middleware('throttle:60,1')->name('project.bidding.schools.workspace');

        Route::get('project/bidding/create', [BiddingController::class, 'project_create'])
            ->name('project.bidding.create');

        Route::post('project/bidding', [BiddingController::class, 'project_store'])
            ->name('project.bidding.store');

        Route::get('project/bidding/{bidding}', [BiddingController::class, 'project_show'])
            ->name('project.bidding.show');

        Route::get('project/bidding/{bidding}/edit', [BiddingController::class, 'project_edit'])
            ->name('project.bidding.edit');

        Route::put('project/bidding/{bidding}', [BiddingController::class, 'project_update'])
            ->name('project.bidding.update');

        Route::post('project/bidding/{bidding}/award-reversal', [BiddingController::class, 'reverseAward'])
            ->name('project.bidding.award-reversal');

        Route::delete('project/bidding/{bidding}', [BiddingController::class, 'project_destroy'])
            ->name('project.bidding.destroy');

        Route::prefix('project/bidding/{bidding}')->name('project.bidding.')->group(function (): void {
            Route::post('folders', [BiddingFolderController::class, 'store'])->name('folders.store');
            Route::put('folders/{folder}', [BiddingFolderController::class, 'update'])->name('folders.update');
            Route::delete('folders/{folder}', [BiddingFolderController::class, 'destroy'])->name('folders.destroy');
            Route::get('documents', [BiddingDocumentController::class, 'index'])->name('documents.index');
            Route::post('documents', [BiddingDocumentController::class, 'store'])->name('documents.store');
            Route::post('documents/zip', [BiddingDocumentController::class, 'zip'])->name('documents.zip');
            Route::put('documents/{document}', [BiddingDocumentController::class, 'update'])->name('documents.update');
            Route::delete('documents/{document}', [BiddingDocumentController::class, 'destroy'])->name('documents.destroy');
            Route::post('documents/{document}/replace', [BiddingDocumentController::class, 'replace'])->name('documents.replace');
            Route::get('documents/{document}/download', [BiddingDocumentController::class, 'download'])->name('documents.download');
            Route::get('documents/{document}/preview', [BiddingDocumentController::class, 'preview'])->name('documents.preview');
            Route::get('documents/{document}/history', [BiddingDocumentController::class, 'history'])->name('documents.history');
            Route::get('documents/{document}/versions/{version}/download', [BiddingDocumentController::class, 'downloadVersion'])->name('documents.versions.download');
        });
    });
    /*
        |--------------------------------------------------------------------------
        | ADMIN ROUTES
        |--------------------------------------------------------------------------
    */
    Route::get('/projects/{project:project_id}', [ProjectController::class, 'show'])
        ->middleware(['company.context:MMC', 'role:user,finance'])
        ->name('projects.show');

    Route::middleware(['role:finance'])->group(function () {
        Route::get('/dashboard', [ProjectFinancialPlanController::class, 'dashboard'])
            ->name('finance.dashboard');
        Route::get('/finance/plans/monitoring/export', [ProjectFinancialPlanController::class, 'exportMonitoring'])
            ->name('finance.plans.monitoring.export');

        Route::middleware('company.context:MMC')->prefix('/finance/ppl-import')->name('finance.ppl-import.')->group(function (): void {
            Route::get('/', [MmcPplWorkbookImportController::class, 'index'])->name('index');
            Route::post('/', [MmcPplWorkbookImportController::class, 'store'])->name('store');
            Route::get('/{batch}', [MmcPplWorkbookImportController::class, 'show'])->whereNumber('batch')->name('show');
            Route::patch('/{batch}/rows/{row}/mapping', [MmcPplWorkbookImportController::class, 'updateMapping'])
                ->whereNumber('batch')->whereNumber('row')->name('rows.mapping');
            Route::post('/{batch}/confirm', [MmcPplWorkbookImportController::class, 'confirm'])->whereNumber('batch')->name('confirm');
            Route::post('/{batch}/cancel', [MmcPplWorkbookImportController::class, 'cancel'])->whereNumber('batch')->name('cancel');
            Route::get('/{batch}/download', [MmcPplWorkbookImportController::class, 'download'])->whereNumber('batch')->name('download');
        });

        Route::get('/finance/plans', [ProjectFinancialPlanController::class, 'index'])
            ->name('finance.plans.index');
        Route::get('/finance/plans/create', [ProjectFinancialPlanController::class, 'create'])
            ->name('finance.plans.create');
        Route::post('/finance/plans', [ProjectFinancialPlanController::class, 'store'])
            ->name('finance.plans.store');
        Route::get('/finance/plans/{financialPlan}/edit', [ProjectFinancialPlanController::class, 'edit'])
            ->whereNumber('financialPlan')
            ->name('finance.plans.edit');
        Route::put('/finance/plans/{financialPlan}', [ProjectFinancialPlanController::class, 'update'])
            ->whereNumber('financialPlan')
            ->name('finance.plans.update');
        Route::get('/finance/plans/{financialPlan}', [ProjectFinancialPlanController::class, 'show'])
            ->whereNumber('financialPlan')
            ->name('finance.plans.show');
        Route::delete('/finance/plans/{financialPlan}', [ProjectFinancialPlanController::class, 'destroy'])
            ->whereNumber('financialPlan')
            ->middleware('company.context:MMC')
            ->name('finance.plans.destroy');

        Route::middleware('company.context:MMC')->group(function (): void {
            Route::prefix('/finance/operations/lifecycle')->name('finance.operations.lifecycle.')->group(function (): void {
                Route::get('/', [OperationsLifecycleController::class, 'index'])->name('index');
                Route::get('/{track}', [OperationsLifecycleController::class, 'show'])
                    ->whereNumber('track')->name('show');
            });

            Route::get('/ppl-forms', [PplFormController::class, 'index'])
                ->name('ppl_forms.index');

            Route::get('/ppl-forms/create', [PplFormController::class, 'create'])
                ->name('ppl_forms.create');

            Route::get('/ppl-forms/source/{bidding}/{lot}', [PplFormController::class, 'sourceMapping'])
                ->whereNumber('bidding')->whereNumber('lot')->name('ppl_forms.source');

            Route::get('/ppl-forms/source/legacy/{bidding}/{project}/{lot}', [PplFormController::class, 'legacyLotSourceMapping'])
                ->whereNumber('bidding')->whereNumber('project')->whereNumber('lot')->name('ppl_forms.legacy_source');

            Route::post('/ppl-forms/store', [PplFormController::class, 'store'])
                ->name('ppl_forms.store');

            Route::get('/ppl-forms/{id}/edit', [PplFormController::class, 'edit'])
                ->whereNumber('id')->name('ppl_forms.edit');
            Route::put('/ppl-forms/{id}', [PplFormController::class, 'update'])
                ->whereNumber('id')->name('ppl_forms.update');

            // ✅ FIXED HERE
            Route::get('/ppl-forms/{id}', [PplFormController::class, 'show'])
                ->name('ppl_forms.show');

            Route::post('/ppl-forms/import', [PplFormController::class, 'import'])
                ->name('ppl_forms.import');
        });

        /*
            |--------------------------------------------------------------------------
            | Change Profile
            |--------------------------------------------------------------------------
        */
        Route::get('/bidding', [BiddingController::class, 'index'])
            ->name('bidding.index');

        Route::get('/bidding/catalog', [BiddingController::class, 'catalog'])->name('bidding.catalog');

        Route::get('bidding/schools/search', [BiddingSchoolController::class, 'search'])->name('bidding.schools.search');
        Route::post('bidding/schools/workspace', [BiddingSchoolController::class, 'workspace'])->middleware('throttle:60,1')->name('bidding.schools.workspace');

        Route::get('/bidding/create', [BiddingController::class, 'create'])
            ->name('bidding.create');
        Route::get('deliveries/labels', [DeliveryController::class, 'generateLabels']);
        Route::post('/bidding', [BiddingController::class, 'store'])
            ->name('bidding.store');

        Route::get('/bidding/{bidding}', [BiddingController::class, 'show'])
            ->name('bidding.show');

        Route::get('/bidding/{bidding}/edit', [BiddingController::class, 'edit'])
            ->name('bidding.edit');

        Route::put('/bidding/{bidding}', [BiddingController::class, 'update'])
            ->name('bidding.update');

        Route::post('/bidding/{bidding}/award-reversal', [BiddingController::class, 'reverseAward'])
            ->name('bidding.award-reversal');

        Route::delete('/bidding/{bidding}', [BiddingController::class, 'destroy'])
            ->name('bidding.destroy');

        Route::prefix('bidding/{bidding}')->middleware('company.context:MMC')->name('bidding.')->group(function (): void {
            Route::post('folders', [BiddingFolderController::class, 'store'])->name('folders.store');
            Route::put('folders/{folder}', [BiddingFolderController::class, 'update'])->name('folders.update');
            Route::delete('folders/{folder}', [BiddingFolderController::class, 'destroy'])->name('folders.destroy');
            Route::get('documents', [BiddingDocumentController::class, 'index'])->name('documents.index');
            Route::post('documents', [BiddingDocumentController::class, 'store'])->name('documents.store');
            Route::post('documents/zip', [BiddingDocumentController::class, 'zip'])->name('documents.zip');
            Route::put('documents/{document}', [BiddingDocumentController::class, 'update'])->name('documents.update');
            Route::delete('documents/{document}', [BiddingDocumentController::class, 'destroy'])->name('documents.destroy');
            Route::post('documents/{document}/replace', [BiddingDocumentController::class, 'replace'])->name('documents.replace');
            Route::get('documents/{document}/download', [BiddingDocumentController::class, 'download'])->name('documents.download');
            Route::get('documents/{document}/preview', [BiddingDocumentController::class, 'preview'])->name('documents.preview');
            Route::get('documents/{document}/history', [BiddingDocumentController::class, 'history'])->name('documents.history');
            Route::get('documents/{document}/versions/{version}/download', [BiddingDocumentController::class, 'downloadVersion'])->name('documents.versions.download');
        });
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
    Route::get('/tiktok/trends', [TikTokController::class, 'fetchHomeDecorTrends'])
        ->name('tiktok.trends');
    Route::get('/pinterest/trends', [TikTokController::class, 'fetchHomePinterestTrends'])
        ->name('pinterest.trends');
    Route::get('/google/trends', [TikTokController::class, 'fetchHomeGoogleTrends'])
        ->name('google.trends');

    Route::get('finance/items', [Finance_Item::class, 'index'])->name('items.index');
    Route::get('finance/items/create', [Finance_Item::class, 'create'])->name('items.create');
    Route::post('finance/items', [Finance_Item::class, 'store'])->name('items.store');
    Route::get('finance/items/{item}/edit', [Finance_Item::class, 'edit'])->name('items.edit');
    Route::put('finance/items/{item}', [Finance_Item::class, 'update'])->name('items.update');
    Route::delete('finance/items/{item}', [Finance_Item::class, 'destroy'])->name('items.destroy');

    Route::prefix('api')->group(function () {

        Route::get('/countries', [LocationController::class, 'countries']);

        Route::get('/regions', [LocationController::class, 'regions']);

        Route::get('/provinces', [LocationController::class, 'provinces']);

        Route::get('/cities', [LocationController::class, 'cities']);

        Route::get('/barangays', [LocationController::class, 'barangays']);
    });
    Route::get('/crawl', [ActionCrawlerController::class, 'crawl']);
    Route::middleware(['role:IT'])->group(function () {

        Route::get('/it_dashboard', function () {
            return view('it.dashboard');
        })->name('it.dashboard');
        Route::get('/it.assets/create', [ITinventoryController::class, 'create'])->name('it.asset.create');
        Route::get('/it.assets', [ITinventoryController::class, 'index'])->name('it.asset.index');
        Route::post('/it.assets', [ITinventoryController::class, 'store'])->name('it.asset.store');
        Route::get('/it.assets/{asset}/edit', [ITinventoryController::class, 'edit'])->name('it.asset.edit');
        Route::put('/it.assets/{asset}', [ITinventoryController::class, 'update'])->name('it.asset.update');
        Route::delete('/it.assets/{asset}', [ITinventoryController::class, 'destroy'])->name('it.asset.destroy');
    });
    Route::middleware(['role:Warehouse_officer'])
        ->prefix('warehouse')
        ->name('warehouse.')
        ->group(function () {
            /*
                |--------------------------------------------------------------------------
                | WAREHOUSE ROUTES
                |--------------------------------------------------------------------------
            */
            Route::post('/warehouse/inventory/scan', [WarehouseInventoryController::class, 'scan'])->name('inventory.scan');
            Route::post('/warehouse/inventory/scan/validate', [WarehouseInventoryController::class, 'validateScan'])->name('inventory.scan.validate');
            Route::post('/warehouse/inventory/scan/save', [WarehouseInventoryController::class, 'saveScan'])->name('inventory.scan.save');
            Route::get('/dashboard', [WarehouseInventoryController::class, 'dashboard'])->name('dashboard');
            Route::get('/stock-out', [WarehouseInventoryController::class, 'scanner'])->name('stock-out');
            Route::get('/categories', function () {
                return view('operation.warehouse.categories.index');
            })->name('categories.index');
            Route::get('/adjustments', function () {
                return view('operation.warehouse.adjustments.index');
            })->name('adjustments.index');
            Route::get('/stock-in', [WarehouseInventoryController::class, 'stockInIndex'])->name('stock-in');
            Route::get('/stock-in/lots', [WarehouseInventoryController::class, 'getLotsForProject'])->name('stock-in.lots');
            Route::get('/stock-in/deliveries', [WarehouseInventoryController::class, 'getDeliveriesForLot'])->name('stock-in.deliveries');
            Route::get('/stock-in/items', [WarehouseInventoryController::class, 'getDeliveryItems'])->name('stock-in.items');
            Route::post('/stock-in/save', [WarehouseInventoryController::class, 'saveStockIn'])->name('stock-in.save');
            Route::get('/transfer', function () {
                return view('operation.warehouse.transfer.index');
            })->name('transfer');
            Route::get('/returns', function () {
                return view('operation.warehouse.returns.index');
            })->name('returns');
            Route::get('/history', function () {
                return view('operation.warehouse.history.index');
            })->name('history');
            Route::get('/transactions', function () {
                return view('operation.warehouse.transactions.index');
            })->name('transactions');
        });

    // Inventory List — kept outside the 'warehouse.' name prefix so routes resolve
    // as inventory.index / inventory.show / etc., matching the view's route() calls.
    Route::middleware(['role:Warehouse_officer'])
        ->prefix('warehouse/inventory')
        ->name('warehouse.inventory.')
        ->group(function () {
            Route::get('/', [InventoryController::class, 'index'])->name('index');
            Route::get('/create', [InventoryController::class, 'create'])->name('create');
            Route::post('/', [InventoryController::class, 'store'])->name('store');
            Route::get('/{inventory}', [InventoryController::class, 'show'])->name('show');
            Route::get('/{inventory}/edit', [InventoryController::class, 'edit'])->name('edit');
            Route::put('/{inventory}', [InventoryController::class, 'update'])->name('update');
        });
    /*
        |--------------------------------------------------------------------------
        | MI ROUTES
        |--------------------------------------------------------------------------
    */
    Route::get('/mi/approvals', [MIApprovalController::class, 'index'])->middleware(['company.context:MI'])->name('mi.approvals');
    Route::post('/mi/approvals/{type}/{recordId}', [MIApprovalController::class, 'decide'])->middleware(['company.context:MI'])->whereIn('type', ['budget', 'travel'])->whereNumber('recordId')->name('mi.approvals.decide');
    Route::middleware(['company.context:MI', 'role:user,Accounting,accounting,Executive,executive'])->group(function () {

        Route::get('/mi/create', [MIAppController::class, 'create'])->name('mi_app.create');
        Route::get('/mi/settings', [MIAppController::class, 'settings'])->name('mi_app.settings');
        Route::get('/mi/designer', [MIAppController::class, 'index'])->name('mi_app.index');
        Route::get('/mi/dashboard', [MIAppController::class, 'dashboard'])->name('mi_app.dashboard');
        Route::post('/mi/setting_store', [MIAppController::class, 'setting_store'])->name('mi_app.store');
        Route::post('/mi/store', [MIAppController::class, 'store'])->name('mi_app.store_1');
        Route::get('/mi/{product}', [MIAppController::class, 'show'])->name('mi_app.show');
        Route::get('/mi/{product}/edit', [MIAppController::class, 'edit'])->name('mi_app.edit');
        Route::match(['put', 'patch'], '/mi/{product}', [MIAppController::class, 'update'])->name('mi_app.update');
        Route::delete('/mi/{product}', [MIAppController::class, 'destroy'])->name('mi_app.destroy');
        Route::get('/taxonomy/{type}/{product}/edit', [MIAppController::class, 'taxonomy_edit'])->name('taxonomy.edit');
        Route::put('/taxonomy/{type}/{product}', [MIAppController::class, 'taxonomy_update'])->name('taxonomy.update');
        Route::delete('/taxonomy/{type}/{product}', [MIAppController::class, 'taxonomy_destroy'])->name('taxonomy.destroy');

        Route::get('/liquidation', [LiquidationController::class, 'index'])->name('liquidation.index');
        Route::get('/liquidation/create', [LiquidationController::class, 'create'])->name('liquidation.create');
        Route::post('/liquidation', [LiquidationController::class, 'store'])->name('liquidation.store');
        Route::get('/liquidation/{liquidation}', [LiquidationController::class, 'show'])->name('liquidation.show');
        Route::get(
            '/liquidation/{liquidation}/edit',
            [LiquidationController::class, 'edit']
        )->name('liquidation.edit');
        Route::put('/liquidation/{liquidation}', [LiquidationController::class, 'update'])->name('liquidation.update');
        Route::delete('/liquidation/{liquidation}', [LiquidationController::class, 'destroy'])->name('liquidation.destroy');
        Route::get('/liquidation/{liquidation}/pdf', [LiquidationController::class, 'downloadPdf'])->name('liquidation.pdf');

        // --- Budget Request (employee submits, approver/accounting/release workflow) ---
        Route::get('/budget-requests', [BudgetRequestController::class, 'index'])->middleware('role:user')->name('budget_requests.index');
        Route::get('/budget-requests/create', [BudgetRequestController::class, 'create'])->name('budget_requests.create');
        Route::post('/budget-requests', [BudgetRequestController::class, 'store'])->name('budget_requests.store');
        Route::get('/budget-requests/{budgetRequest}', [BudgetRequestController::class, 'show'])->name('budget_requests.show');
        Route::get('/budget-requests/{budgetRequest}/edit', [BudgetRequestController::class, 'edit'])->name('budget_requests.edit');
        Route::put('/budget-requests/{budgetRequest}', [BudgetRequestController::class, 'update'])->name('budget_requests.update');
        Route::delete('/budget-requests/{budgetRequest}', [BudgetRequestController::class, 'destroy'])->name('budget_requests.destroy');

        // Workflow transitions - one per box in the diagram
        Route::post('/budget-requests/{budgetRequest}/received', [BudgetRequestController::class, 'markReceived'])->name('budget_requests.received');
        Route::post('/budget-requests/{budgetRequest}/resubmit', [BudgetRequestController::class, 'resubmit'])->name('budget_requests.resubmit');

        // --- Travel Liquidation (tied to a budget_requests row, review/approve workflow) ---
        // NOTE: renamed from 'liquidation.*' to 'travel_liquidation.*' to stop colliding
        // with the plain Liquidation routes above (both used the same route names,
        // so route()/redirect()->route() calls anywhere in the app always resolved
        // to whichever block was registered last).
        Route::get('/travel_liquidation', [TravelLiquidationController::class, 'index'])->name('travel_liquidation.index');
        Route::get('/travel_liquidation/create', [TravelLiquidationController::class, 'create'])->name('travel_liquidation.create');
        Route::post('/travel_liquidation', [TravelLiquidationController::class, 'store'])->name('travel_liquidation.store');
        Route::post('/travel_liquidation/{liquidation}/submit', [TravelLiquidationController::class, 'submit'])->name('travel_liquidation.submit');
        Route::get('/travel_liquidation/{liquidation}', [TravelLiquidationController::class, 'show'])->name('travel_liquidation.show');
        Route::get('/travel_liquidation/{liquidation}/edit', [TravelLiquidationController::class, 'edit'])->name('travel_liquidation.edit');
        Route::put('/travel_liquidation/{liquidation}', [TravelLiquidationController::class, 'update'])->name('travel_liquidation.update');
        Route::delete('/travel_liquidation/{liquidation}', [TravelLiquidationController::class, 'destroy'])->name('travel_liquidation.destroy');
        Route::get('/travel_liquidation/{liquidation}/pdf', [TravelLiquidationController::class, 'downloadPdf'])->name('travel_liquidation.pdf');

        // Balance-check sign-off (the dashed box in your diagram)
    });
    Route::middleware(['company.context:MI'])->group(function () {
        Route::post('/budget-requests/{budgetRequest}/approve', [BudgetRequestController::class, 'approve'])->name('budget_requests.approve');
        Route::post('/travel_liquidation/{liquidation}/approve', [TravelLiquidationController::class, 'approve'])->name('travel_liquidation.approve');
        Route::get('/mi/processing/budgets/{budgetRequest}', [BudgetRequestController::class, 'processing'])->name('budget_requests.processing');
        Route::get('/mi/processing/travel/{liquidation}', [TravelLiquidationController::class, 'processing'])->name('travel_liquidation.processing');
        Route::post('/travel_liquidation/{liquidation}/settlement', [TravelLiquidationController::class, 'recordSettlement'])->name('travel_liquidation.settlement');
        Route::post('/travel_liquidation/{liquidation}/close', [TravelLiquidationController::class, 'close'])->name('travel_liquidation.close');
    });
    Route::middleware(['company.context:MI', 'role:user,Executive,executive,Accounting,accounting'])->group(function () {
        Route::get('/mi-liquidation-receipts/{item}', [LiquidationController::class, 'receipt'])->name('liquidation.receipt');
    });
    Route::middleware(['company.context:MI', 'role:Accounting,accounting'])->group(function () {
        Route::post('/budget-requests/{budgetRequest}/note', [BudgetRequestController::class, 'noteByAccounting'])->name('budget_requests.note');
        Route::post('/budget-requests/{budgetRequest}/release', [BudgetRequestController::class, 'release'])->name('budget_requests.release');
        Route::post('/travel_liquidation/{liquidation}/note', [TravelLiquidationController::class, 'noteByAccounting'])->name('travel_liquidation.note');

        Route::get('/accounting', [Accounting_DashboardController::class, 'dashboard'])->name('accounting.mi.home');
        Route::get('/accounting/dashboard', [Accounting_DashboardController::class, 'dashboard'])->name('accounting.mi.dashboard');
        Route::get('/accounting/workspace/{section}', [AccountingWorkspaceController::class, 'index'])->name('accounting.mi.workspace');
        Route::get('/accounting/budgets/{budgetRequest}', [AccountingWorkspaceController::class, 'budget'])->name('accounting.mi.budgets.show');
        Route::get('/accounting/travel/{liquidation}', [AccountingWorkspaceController::class, 'travel'])->name('accounting.mi.travel.show');
        Route::get('/accounting/liquidation', [Accounting_LiquidationController::class, 'index'])->name('accounting.mi.liquidation.index');
        Route::get('/accounting/liquidation/{liquidation}', [Accounting_LiquidationController::class, 'show'])->name('accounting.mi.liquidation.show');
        Route::get('/accounting/liquidation/{liquidation}/pdf', [Accounting_LiquidationController::class, 'downloadPdf'])->name('accounting.mi.liquidation.pdf');
    });

    Route::prefix('billing')
        ->name('billing.')
        ->middleware('company.context:MMC')
        ->group(function (): void {
            Route::get('/dashboard', [BillingDashboardController::class, 'index'])
                ->middleware('can:billing.records.view')
                ->name('dashboard');

            Route::get('/scanner', [BillingScannerController::class, 'index'])
                ->middleware('can:billing.qr.scan')
                ->name('scanner');
            Route::get('/scanner/lookup', [BillingScannerController::class, 'lookup'])
                ->middleware('can:billing.qr.scan')
                ->name('scanner.lookup');
            Route::post('/records/{delivery}/for-billing', [BillingScannerController::class, 'markForBilling'])
                ->whereNumber('delivery')
                ->middleware('can:billing.for-billing.mark')
                ->name('for-billing');
            Route::post('/records/{delivery}/billed', [BillingScannerController::class, 'confirmBilled'])
                ->whereNumber('delivery')
                ->middleware('can:billing.billed.confirm')
                ->name('billed');
            Route::post('/records/{delivery}/hold', [BillingScannerController::class, 'placeOnHold'])
                ->whereNumber('delivery')
                ->middleware('can:billing.exceptions.manage')
                ->name('hold');
            Route::post('/records/{delivery}/release-hold', [BillingScannerController::class, 'releaseHold'])
                ->whereNumber('delivery')
                ->middleware('can:billing.exceptions.manage')
                ->name('release-hold');

            Route::get('/records', [BillingRecordController::class, 'index'])
                ->middleware('can:billing.records.view')
                ->name('records.index');
            Route::get('/records/export', [BillingRecordController::class, 'export'])
                ->middleware('can:billing.reports.export')
                ->name('records.export');
            Route::get('/records/{delivery}', [BillingRecordController::class, 'show'])
                ->whereNumber('delivery')
                ->middleware('can:billing.records.view')
                ->name('records.show');
        });
});

Route::get('/it.assets/{asset}', [ITinventoryController::class, 'show'])->name('it.asset.show');

/*
    |--------------------------------------------------------------------------
    | Delivery Routes
    |--------------------------------------------------------------------------
*/
Route::get('/receive-delivery/{packageStatus}', [DeliveryReceiveController::class, 'show'])->name('delivery.receive');
Route::post('/receive-delivery/{packageStatus}', [DeliveryReceiveController::class, 'store'])->name('delivery.receive.store');
Route::get('/delivery-success', function () {
    return view('operation.delivery.success');
})->name('delivery.success');
Route::get('/test-drive', function () {
    return view('test');
});
Route::get('/p/{product}', [PublicProductController::class, 'show'])->name('public.product.show');
Route::prefix('mi-app')
    ->name('mi_app.')
    ->group(function () {

        Route::middleware(['throttle:10,1'])
            ->group(function () {

                Route::post(
                    '/quotation/download',
                    [QuotationController::class, 'download']
                )->name('quotation.download');

                Route::post(
                    '/quotation/print',
                    [QuotationController::class, 'print']
                )->name('quotation.print');

            });

        Route::get(
            '/scan',
            [ProductScanController::class, 'scan']
        )->name('scan');

    });
require __DIR__.'/auth.php';
