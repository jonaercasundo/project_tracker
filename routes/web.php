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
use App\Http\Controllers\BiddingController;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\EntryController;
use App\Http\Controllers\SchoolImportController;
use App\Http\Controllers\ActionCrawlerController;
use App\Http\Controllers\Finance_Item;
use App\Http\Controllers\InventoryController;
use App\Models\Project;
use App\Http\Controllers\ProjectDashboardController;
use App\Http\Controllers\ITinventoryController;
use App\Http\Controllers\Warehouse\WarehouseInventoryController;
use App\Http\Controllers\DeliveryReceiveController;
use App\Http\Controllers\MIAppController;
use App\Http\Controllers\QuotationController;
use App\Http\Controllers\PublicProductController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\LiquidationController;
use App\Http\Controllers\Accounting_LiquidationController;
use App\Http\Controllers\Accounting_DashboardController;
use App\Http\Controllers\ProductScanController;

    /*
    |--------------------------------------------------------------------------
    | PUBLIC ROUTE
    |--------------------------------------------------------------------------
    */
    Route::get('/', function () {return view('welcome');})->name('welcome');

        /*
        |--------------------------------------------------------------------------
        | AUTH ROUTES
        |--------------------------------------------------------------------------
        */
        Route::middleware(['auth'])->group(function () {
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
                'index'
            ])->name('admin.dashboard');


            /*
            |--------------------------------------------------------------------------
            | ROLE ACCESS MANAGEMENT
            |--------------------------------------------------------------------------
            */

            Route::get('/roleaccess', [
                RoleAccessPermissionController::class,
                'edit'
            ])->name('roleaccess.index');

            Route::patch('/roleaccess/update', [
                RoleAccessPermissionController::class,
                'update'
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

            Route::get('/projects/{project}/deliveries', [DeliveryController::class, 'index'])
            ->name('deliveries.index');

            Route::get('/projects', [ProjectController::class, 'index'])
                ->name('projects.index');

            Route::post('/projects', [ProjectController::class, 'store'])
                ->name('projects.store');

            Route::put('/projects/{project}', [ProjectController::class, 'update'])
                ->name('projects.update');
            Route::post('/deliveries/qr-generate', [DeliveryController::class, 'generate']);

            Route::post('/projects/filter', [ProjectController::class, 'filter'])
                ->name('projects.filter');
            Route::get('/projects/{project:project_id}', [ProjectController::class, 'show'])
            ->name('projects.show');
            /*
                |--------------------------------------------------------------------------
                | Allocation List Import
                |--------------------------------------------------------------------------
            */

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
            Route::get('/items', [ItemController::class, 'index'])->name('items.index');
            Route::get('/items/create', [ItemController::class, 'create'])->name('items.create');
            Route::post('/items', [ItemController::class, 'store'])->name('items.store');
            Route::get('/items/{item}/edit', [ItemController::class, 'edit'])->name('items.edit');
            Route::put('/items/{item}', [ItemController::class, 'update'])->name('items.update');
            Route::delete('/items/{item}', [ItemController::class, 'destroy'])->name('items.destroy');
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

            Route::delete('project/bidding/{bidding}', [BiddingController::class, 'project_destroy'])
                ->name('project.bidding.destroy');
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

            /*
                |--------------------------------------------------------------------------
                | Change Profile
                |--------------------------------------------------------------------------
            */
            Route::get('/bidding', [BiddingController::class, 'index'])
            ->name('bidding.index');

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

            Route::delete('/bidding/{bidding}', [BiddingController::class, 'destroy'])
                ->name('bidding.destroy');
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
                Route::get('/packages', function () { return view('operation.warehouse.packages.index'); })->name('packages.index');
                Route::get('/categories', function () { return view('operation.warehouse.categories.index'); })->name('categories.index');
                Route::get('/adjustments', function () { return view('operation.warehouse.adjustments.index'); })->name('adjustments.index');
                Route::get('/stock-in', [WarehouseInventoryController::class, 'stockInIndex'])->name('stock-in');
                Route::get('/stock-in/lots', [WarehouseInventoryController::class, 'getLotsForProject'])->name('stock-in.lots');
                Route::get('/stock-in/deliveries', [WarehouseInventoryController::class, 'getDeliveriesForLot'])->name('stock-in.deliveries');
                Route::get('/stock-in/items', [WarehouseInventoryController::class, 'getDeliveryItems'])->name('stock-in.items');
                Route::post('/stock-in/save', [WarehouseInventoryController::class, 'saveStockIn'])->name('stock-in.save');
                Route::get('/transfer', function () {return view('operation.warehouse.transfer.index');})->name('transfer');
                Route::get('/returns', function () { return view('operation.warehouse.returns.index');})->name('returns');
                Route::get('/history', function () { return view('operation.warehouse.history.index'); })->name('history');
                Route::get('/transactions', function () { return view('operation.warehouse.transactions.index');})->name('transactions');
        });  
        /*
            |--------------------------------------------------------------------------
            | MI ROUTES
            |--------------------------------------------------------------------------
        */
        Route::middleware(['auth','company.context:MI','role:user'])->group(function () {

            Route::get('/mi/create', [MIAppController::class, 'create'])->name('mi_app.create');
            Route::get('/mi/settings', [MIAppController::class, 'settings'])->name('mi_app.settings');
            Route::get('/mi/designer', [MIAppController::class, 'index'])->name('mi_app.index');
            Route::get('/mi/dashboard', [MIAppController::class, 'dashboard'])->name('mi_app.dashboard');
            Route::post('/mi/setting_store', [MIAppController::class, 'setting_store'])->name('mi_app.store');
            Route::post('/mi/store', [MIAppController::class, 'store'])->name('mi_app.store_1');
            Route::get('/mi/{product}', [MIAppController::class, 'show'])->name('mi_app.show');
            Route::get('/mi/{product}/edit', [MIAppController::class, 'edit'])->name('mi_app.edit');
            Route::put('/mi/{product}', [MIAppController::class, 'update'])->name('mi_app.update');
            Route::delete('/mi/{product}', [MIAppController::class, 'destroy'])->name('mi_app.destroy');
            Route::get('/taxonomy/{type}/{product}/edit', [MIAppController::class, 'taxonomy_edit'])->name('taxonomy.edit');
            Route::put('/taxonomy/{type}/{product}', [MIAppController::class, 'taxonomy_update'])->name('taxonomy.update');
            Route::delete('/taxonomy/{type}/{product}', [MIAppController::class, 'taxonomy_destroy'])->name('taxonomy.destroy');

            Route::get('/liquidation', [LiquidationController::class, 'index'])->name('liquidation.index');
            Route::get('/liquidation/create', [LiquidationController::class, 'create'])->name('liquidation.create');
            Route::post('/liquidation', [LiquidationController::class, 'store'])->name('liquidation.store');
            Route::get('/liquidation/{liquidation}', [LiquidationController::class, 'show'])->name('liquidation.show');
            Route::get(
                '/liquidation/{id}/edit',
                [LiquidationController::class, 'edit']
            )->name('liquidation.edit');
            Route::put('/liquidation/{liquidation}', [LiquidationController::class, 'update'])->name('liquidation.update');
            Route::delete('/liquidation/{liquidation}', [LiquidationController::class, 'destroy'])->name('liquidation.destroy');
            Route::get('/liquidation/{liquidation}/pdf', [LiquidationController::class, 'downloadPdf'])->name('liquidation.pdf');
        });
        Route::middleware(['auth','company.context:MI','role:accounting'])->group(function () {
            Route::get('/accounting/dashboard', [Accounting_DashboardController::class, 'dashboard'])->name('accounting.mi.dashboard');
            Route::get('/accounting/liquidation', [Accounting_LiquidationController::class, 'index'])->name('accounting.mi.liquidation.index');
            Route::get('/accounting/liquidation/{liquidation}', [Accounting_LiquidationController::class, 'show'])->name('accounting.mi.liquidation.show');
            Route::get('/accounting/liquidation/{liquidation}/pdf', [Accounting_LiquidationController::class, 'downloadPdf'])->name('accounting.mi.liquidation.pdf');
        });
    });
    
    Route::get('/it.assets/{asset}', [ITinventoryController::class, 'show'])->name('it.asset.show');

    /*
        |--------------------------------------------------------------------------
        | Delivery Routes
        |--------------------------------------------------------------------------
    */
    Route::get('/receive-delivery/{packageStatus}', [DeliveryReceiveController::class, 'show'])->name('delivery.receive');
    Route::post('/receive-delivery/{packageStatus}', [DeliveryReceiveController::class,'store'])->name('delivery.receive.store');
    Route::get('/delivery-success', function () {return view('operation.delivery.success'); })->name('delivery.success');
    Route::get('/test-drive', function () { return view('test'); });
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
                [ProductScanController::class, 'scan'] // TODO: replace with the actual controller that resolves a scanned product tag and renders the product-detail view
            )->name('mi_app.scan');

        });
require __DIR__.'/auth.php';