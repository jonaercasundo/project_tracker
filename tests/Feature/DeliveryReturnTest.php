<?php

use App\Models\Company;
use App\Models\User;
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

it('credits only confirmed item returns through inventory history and keeps document returns inventory neutral', function (): void {
    prepareDeliveryReturnRecords();
    $user = deliveryReturnUser();
    $this->actingAs($user);
    $this->withSession(['company_id' => $user->companies()->first()->getKey()]);

    $itemReturn = $this->postJson(route('deliveries.monitoring.returns.store', ['project' => 1]), [
        'return_type' => 'Item Return',
        'delivery_id' => 1,
        'item_id' => 1,
        'quantity_returned' => 3,
        'reason' => 'Wrong size delivered',
        'remarks' => 'Two cartons',
        'monitoring_date' => '2026-10-20',
        'status' => 'Reported',
    ]);
    $itemReturn->assertOk()->assertJsonPath('message', 'Return recorded. Stock changes only when an Item Return is marked Received.');
    $returnId = (int) DB::table('delivery_returns')->where('return_type', 'Item Return')->value('return_id');
    $this->assertDatabaseHas('delivery_returns', ['return_id' => $returnId, 'dr_no' => 'DR-100', 'monitoring_date' => '2026-10-20 00:00:00', 'received_date' => null]);
    $this->assertDatabaseHas('inventory', ['inventory_id' => 1, 'qty' => 8]);

    $this->patchJson(route('deliveries.monitoring.returns.update', ['project' => 1, 'return' => $returnId]), [
        'status' => 'Received',
        'monitoring_date' => '2026-10-21',
        'received_date' => '2026-10-22',
        'received_by' => 'Warehouse Clerk',
        'received_warehouse_id' => 1,
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
    ])->assertConflict();

    $documentReturn = $this->postJson(route('deliveries.monitoring.returns.store', ['project' => 1]), [
        'return_type' => 'Document Return',
        'delivery_id' => 1,
        'reason' => 'Signed delivery receipt returned',
        'monitoring_date' => '2026-10-22',
        'status' => 'In Transit',
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
        'return_type' => 'Document Return',
        'return_status' => 'Received',
        'return_date_from' => '2026-10-22',
        'received_date_from' => '2026-10-23',
    ]));
    expect($filteredReturns->json('html'))
        ->toContain('Document Return')
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
