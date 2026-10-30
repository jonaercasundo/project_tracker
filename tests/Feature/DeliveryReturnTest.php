<?php

use App\Models\Company;
use App\Models\DeliveryReturn;
use App\Models\User;
use App\Services\DeliveryReturnSpreadsheetSyncService;
use App\Services\GoogleSheetsApiClient;
use App\Services\GoogleSheetsBillingConfiguration;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DeliveryReturnTestCase extends TestCase
{
    use RefreshDatabase;

    public function createApplication(): Application
    {
        $app = require Application::inferBasePath().'/bootstrap/app.php';
        $app->singleton(Kernel::class, DeliveryReturnConsoleKernel::class);
        $app->make(Kernel::class)->bootstrap();
        $app['config']->set('database.default', 'sqlite');
        $app['config']->set('database.connections.sqlite.database', ':memory:');
        RefreshDatabaseState::$migrated = false;
        RefreshDatabaseState::$inMemoryConnections = [];

        return $app;
    }

    protected function migrateFreshUsing(): array
    {
        return ['--path' => [
            'database/migrations/0001_01_01_000000_create_users_table.php',
            'database/migrations/2026_06_15_081615_create_permission_tables.php',
            'database/migrations/2026_09_02_024221_create_companies_table.php',
            'database/migrations/2026_09_02_024448_create_company_user_table.php',
            'database/migrations/2026_10_02_073836_create_personal_access_tokens_table.php',
        ]];
    }

    protected function setUp(): void
    {
        parent::setUp();
        require __DIR__.'/../Fixtures/jarvis_schema.php';
        Artisan::call('migrate', [
            '--path' => database_path('migrations/2026_10_30_015841_create_delivery_returns_table.php'),
            '--realpath' => true,
            '--force' => true,
        ]);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        RefreshDatabaseState::$migrated = false;
        RefreshDatabaseState::$inMemoryConnections = [];
    }
}

class DeliveryReturnConsoleKernel extends Illuminate\Foundation\Console\Kernel
{
    protected function discoverCommands(): void {}
}

pest()->extend(DeliveryReturnTestCase::class)->in(__FILE__);

function deliveryReturnUser(): User
{
    $user = User::factory()->create(['username' => fake()->unique()->userName(), 'role' => 'Administrator']);
    $user->assignRole(Role::findOrCreate('Administrator', 'web'));
    $company = Company::create(['name' => 'MMC', 'code' => 'MMC', 'is_active' => true]);
    $user->companies()->attach($company);

    return $user;
}

function prepareDeliveryReturnRecords(): void
{
    DB::table('projects')->insert(['project_id' => 1, 'project_name' => 'School Supplies']);
    DB::table('lot')->insert(['lot_id' => 1, 'project_id' => 1, 'lot_name' => 'Lot 1']);
    DB::table('school')->insert(['school_id' => 'SCH-1', 'project_id' => 1, 'school_name' => 'North School']);
    DB::table('deliveries')->insert([
        'delivery_id' => 1,
        'project_id' => 1,
        'lot_id' => 1,
        'school_id' => 'SCH-1',
        'dr_no' => 'DR-100',
        'status' => 'delivered',
        'delivery_date' => '2026-10-01',
        'package_qty' => 1,
    ]);
    DB::table('item')->insert(['item_id' => 1, 'item_name' => 'Science Kit', 'project_id' => 1, 'unit' => 'set']);
    DB::table('warehouse')->insert(['warehouse_id' => 1, 'warehouse_name' => 'Main Warehouse']);
    DB::table('inventory')->insert(['inventory_id' => 1, 'item_id' => 1, 'warehouse_id' => 1, 'qty' => 8, 'inventory_status' => 'Approved']);
}

it('shows return monitoring in the MMC sidebar and opens its project workspace', function (): void {
    prepareDeliveryReturnRecords();
    $user = deliveryReturnUser();
    $this->actingAs($user);
    $this->withSession(['company_id' => $user->companies()->first()->getKey()]);

    $this->get(route('deliveries.return-monitoring'))
        ->assertOk()
        ->assertSee('Return Monitoring')
        ->assertSee('role="combobox"', false)
        ->assertSee('Search projects by name or code...', false)
        ->assertSee('truncate whitespace-nowrap', false)
        ->assertSee('href="'.route('deliveries.return-monitoring').'"', false)
        ->assertSee('Choose a project to view or record returns.');

    $this->get(route('deliveries.return-monitoring', ['project_id' => 1]))
        ->assertOk()
        ->assertSee('Record a return')
        ->assertSee('Return monitoring')
        ->assertSee('School ID')
        ->assertSee('name="school_id" data-return-school type="text"', false)
        ->assertDontSee('<select name="school_id"', false)
        ->assertSee('Reason')
        ->assertDontSee('name="delivery_id"', false)
        ->assertDontSee('name="package_id"', false)
        ->assertDontSee('name="item_id"', false)
        ->assertDontSee('name="quantity_returned"', false)
        ->assertDontSee('name="remarks"', false)
        ->assertDontSee('name="monitoring_date"', false)
        ->assertDontSee('name="status"', false)
        ->assertDontSee('name="return_date_from"', false)
        ->assertDontSee('name="return_date_to"', false)
        ->assertDontSee('name="received_date_from"', false)
        ->assertDontSee('name="received_date_to"', false)
        ->assertDontSee('name="quantity_from"', false)
        ->assertDontSee('name="quantity_to"', false);
});

it('shows delivery and separate item and document return analytics', function (): void {
    prepareDeliveryReturnRecords();
    DB::table('delivery_returns')->insert([
        'return_type' => 'Item Return',
        'project_id' => 1,
        'lot_id' => 1,
        'school_id' => 'SCH-1',
        'delivery_id' => 1,
        'dr_no' => 'DR-100',
        'item_id' => 1,
        'quantity_returned' => 3,
        'status' => 'Received',
        'reason' => 'Damaged packaging',
        'monitoring_date' => '2026-10-05',
        'received_date' => '2026-10-06',
    ]);
    DB::table('delivery_returns')->insert([
        'return_type' => 'Document Return',
        'project_id' => 1,
        'lot_id' => 1,
        'school_id' => 'SCH-1',
        'delivery_id' => 1,
        'dr_no' => 'DR-100',
        'status' => 'In Transit',
        'reason' => 'Original delivery receipt',
        'monitoring_date' => '2026-10-07',
    ]);
    $user = deliveryReturnUser();
    $user->assignRole(Role::findOrCreate('user', 'web'));
    $this->actingAs($user)->withSession(['company_id' => $user->companies()->first()->getKey()]);

    $this->get(route('projects.reports.analytics'))
        ->assertSee('Reports & Analytics')
        ->assertSee('Delivery completion')
        ->assertSee('Returns by type')
        ->assertSee('2 returns logged')
        ->assertSee('3 items added after receipt confirmation')
        ->assertSee('Document returns are tracked separately from inventory.')
        ->assertDontSee('Recent delivery records');
});

it('credits only confirmed item returns through inventory history and keeps document returns inventory neutral', function (): void {
    prepareDeliveryReturnRecords();
    $user = deliveryReturnUser();
    $this->actingAs($user);
    $this->withSession(['company_id' => $user->companies()->first()->getKey()]);
    $monitoringDate = now()->toDateString();

    $itemReturn = $this->postJson(route('deliveries.monitoring.returns.store', ['project' => 1]), [
        'return_type' => 'Item Return',
        'school_id' => 'SCH-1',
        'reason' => 'Wrong size delivered',
    ]);
    $itemReturn->assertOk()->assertJsonPath('message', 'Return recorded. Stock changes only when an Item Return is marked Received. Google Sheets is not configured, so the spreadsheet date was not updated.');
    $returnId = (int) DB::table('delivery_returns')->where('return_type', 'Item Return')->value('return_id');
    $this->assertDatabaseHas('delivery_returns', [
        'return_id' => $returnId,
        'dr_no' => 'DR-100',
        'monitoring_date' => $monitoringDate.' 00:00:00',
        'status' => 'Reported',
        'item_id' => null,
        'quantity_returned' => null,
        'received_date' => null,
    ]);
    $this->assertDatabaseHas('inventory', ['inventory_id' => 1, 'qty' => 8]);

    $this->patchJson(route('deliveries.monitoring.returns.update', ['project' => 1, 'return' => $returnId]), [
        'status' => 'Received',
        'monitoring_date' => '2026-10-21',
        'received_date' => '2026-10-22',
        'received_by' => 'Warehouse Clerk',
        'received_warehouse_id' => 1,
        'item_id' => 1,
        'quantity_returned' => 3,
    ])->assertOk();
    $this->assertDatabaseHas('inventory', ['inventory_id' => 1, 'qty' => 11]);
    $this->assertDatabaseHas('inventory_history', [
        'delivery_return_id' => $returnId,
        'delivery_id' => 1,
        'project_id' => 1,
        'dr_no' => 'DR-100',
        'quantity' => 3,
        'change_type' => 'stock_in',
    ]);
    $this->assertDatabaseHas('delivery_returns', [
        'return_id' => $returnId,
        'monitoring_date' => '2026-10-21 00:00:00',
        'received_date' => '2026-10-22 00:00:00',
    ]);

    $this->patchJson(route('deliveries.monitoring.returns.update', ['project' => 1, 'return' => $returnId]), [
        'status' => 'Received',
        'monitoring_date' => '2026-10-22',
        'received_date' => '2026-10-22',
        'received_by' => 'Warehouse Clerk',
        'received_warehouse_id' => 1,
        'item_id' => 1,
        'quantity_returned' => 3,
    ])->assertConflict();

    $documentReturn = $this->postJson(route('deliveries.monitoring.returns.store', ['project' => 1]), [
        'return_type' => 'Document Return',
        'school_id' => 'SCH-1',
        'reason' => 'Signed delivery receipt returned',
    ])->assertOk();
    $documentId = (int) DB::table('delivery_returns')->where('return_type', 'Document Return')->value('return_id');
    $this->patchJson(route('deliveries.monitoring.returns.update', ['project' => 1, 'return' => $documentId]), [
        'status' => 'Received',
        'monitoring_date' => '2026-10-23',
        'received_date' => '2026-10-23',
        'received_by' => 'Office Clerk',
    ])->assertOk();
    $this->assertDatabaseHas('inventory', ['inventory_id' => 1, 'qty' => 11]);
    $this->assertDatabaseHas('delivery_returns', ['return_id' => $documentId, 'received_date' => '2026-10-23 00:00:00', 'received_by' => 'Office Clerk']);
    $this->assertDatabaseMissing('inventory_history', ['delivery_return_id' => $documentId]);

    $filteredReturns = $this->getJson(route('deliveries.monitoring.returns', [
        'project' => 1,
        'school_id' => 'SCH-1',
        'return_type' => 'Document Return',
        'return_status' => 'Received',
        'return_date_from' => '2026-10-22',
        'received_date_from' => '2026-10-23',
    ]));
    expect($filteredReturns->json('html'))
        ->toContain('Document Return')
        ->toContain('School ID')
        ->toContain('DR-100')
        ->toContain('Monitoring Date')
        ->toContain('Received Date / By')
        ->toContain('Signed delivery receipt returned')
        ->not->toContain('Wrong size delivered');

    $filteredItemReturns = $this->getJson(route('deliveries.monitoring.returns', [
        'project' => 1,
        'return_type' => 'Item Return',
        'return_status' => 'Received',
        'quantity_from' => 2,
        'quantity_to' => 4,
    ]));
    expect($filteredItemReturns->json('html'))
        ->toContain('Wrong size delivered')
        ->not->toContain('Signed delivery receipt returned');
});

it('writes the return dates to the exact spreadsheet row matched by School ID and BIR DR', function (): void {
    $header = array_fill(0, 99, '');
    $header[0] = 'School ID';
    $header[20] = 'BIR DR';
    $header[97] = 'Returned Date';
    $header[98] = 'Received Date';
    $data = array_fill(0, 99, '');
    $data[0] = 'SCH-1';
    $data[20] = 'DR-100';
    $sheets = Mockery::mock(GoogleSheetsApiClient::class);
    $sheets->shouldReceive('configurationStatus')->twice()->andReturn(['configured' => true, 'reason' => null]);
    $sheets->shouldReceive('values')->twice()->with("'Copy of SME LOT 2,9,11'!A:CU", '1sOESkXSA8CbgkJMA_Y-sYzkngEimsrnn3qQwBvZHtUw')->andReturn([$header, $data]);
    $sheets->shouldReceive('updateCells')->once()->with([
        ['range' => "'Copy of SME LOT 2,9,11'!CT2", 'majorDimension' => 'ROWS', 'values' => [['2026-10-30']]],
    ], '1sOESkXSA8CbgkJMA_Y-sYzkngEimsrnn3qQwBvZHtUw');
    $sheets->shouldReceive('updateCells')->once()->with([
        ['range' => "'Copy of SME LOT 2,9,11'!CT2", 'majorDimension' => 'ROWS', 'values' => [['2026-10-30']]],
        ['range' => "'Copy of SME LOT 2,9,11'!CU2", 'majorDimension' => 'ROWS', 'values' => [['2026-10-31']]],
    ], '1sOESkXSA8CbgkJMA_Y-sYzkngEimsrnn3qQwBvZHtUw');
    $deliveryReturn = new DeliveryReturn([
        'school_id' => 'SCH-1',
        'dr_no' => 'DR-100',
        'monitoring_date' => '2026-10-30',
        'received_date' => '2026-10-31',
    ]);
    $sync = new DeliveryReturnSpreadsheetSyncService;
    $configuration = new GoogleSheetsBillingConfiguration;

    expect($sync->sync($deliveryReturn, $sheets, $configuration))->toBeTrue()
        ->and($sync->sync($deliveryReturn, $sheets, $configuration, true))->toBeTrue();
});

it('does not write spreadsheet dates when multiple rows match the School ID and BIR DR', function (): void {
    $header = array_fill(0, 99, '');
    $header[0] = 'School ID';
    $header[20] = 'BIR DR';
    $header[97] = 'Returned Date';
    $header[98] = 'Received Date';
    $data = array_fill(0, 99, '');
    $data[0] = 'SCH-1';
    $data[20] = 'DR-100';
    $sheets = Mockery::mock(GoogleSheetsApiClient::class);
    $sheets->shouldReceive('configurationStatus')->once()->andReturn(['configured' => true, 'reason' => null]);
    $sheets->shouldReceive('values')->once()->andReturn([$header, $data, $data]);
    $sheets->shouldNotReceive('updateCells');

    $result = (new DeliveryReturnSpreadsheetSyncService)->sync(new DeliveryReturn([
        'school_id' => 'SCH-1',
        'dr_no' => 'DR-100',
        'monitoring_date' => '2026-10-30',
    ]), $sheets, new GoogleSheetsBillingConfiguration);

    expect($result)->toBeFalse();
});
