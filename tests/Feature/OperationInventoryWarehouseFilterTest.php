<?php

use App\Models\Company;
use App\Models\Inventory;
use App\Models\Item;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class OperationInventoryTestCase extends TestCase
{
    use RefreshDatabase;

    public function createApplication(): Application
    {
        $app = require Application::inferBasePath().'/bootstrap/app.php';
        $app->singleton(Kernel::class, OperationInventoryConsoleKernel::class);
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
        ]];
    }

    protected function setUp(): void
    {
        parent::setUp();
        require __DIR__.'/../Fixtures/jarvis_schema.php';
        $this->withoutVite();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        RefreshDatabaseState::$migrated = false;
        RefreshDatabaseState::$inMemoryConnections = [];
    }
}

/** Match the legacy operations test harness without unrelated console discovery. */
class OperationInventoryConsoleKernel extends Illuminate\Foundation\Console\Kernel
{
    protected function discoverCommands(): void {}
}

pest()->extend(OperationInventoryTestCase::class);

function inventoryFilterUser(string $companyCode = 'MMC', string $role = 'user'): User
{
    $user = User::factory()->create(['username' => fake()->unique()->userName(), 'role' => $role]);
    $user->assignRole(Role::findOrCreate($role, 'web'));
    $company = Company::create(['code' => $companyCode, 'name' => $companyCode, 'is_active' => true]);
    $user->companies()->attach($company);
    test()->actingAs($user)->withSession(['company_id' => $company->company_id]);

    return $user;
}

function inventoryFilterFixtures(): void
{
    DB::table('projects')->insert([
        ['project_id' => 1, 'project_name' => 'School'],
        ['project_id' => 2, 'project_name' => 'Office'],
    ]);
    foreach ([1 => 'Pampanga', 2 => 'Cebu', 3 => 'Davao', 4 => 'New Warehouse'] as $id => $name) {
        Warehouse::create(['warehouse_id' => $id, 'warehouse_name' => $name]);
    }
    Item::create(['item_id' => 1, 'item_name' => 'Chair', 'project_id' => 1, 'unit' => 'pc', 'price' => 100, 'supplier_price' => 50]);
    Item::create(['item_id' => 2, 'item_name' => 'Desk', 'project_id' => 2, 'unit' => 'pc', 'price' => 200, 'supplier_price' => 100]);
    foreach ([1, 2, 3] as $id) {
        Inventory::create(['inventory_id' => $id, 'item_id' => 1, 'warehouse_id' => $id, 'qty' => $id * 10, 'inventory_status' => 'Approved', 'created_at' => '2026-10-01 12:00:00']);
    }
    Inventory::create(['inventory_id' => 4, 'item_id' => 2, 'warehouse_id' => 1, 'qty' => 5, 'inventory_status' => 'For Approval', 'created_at' => '2026-10-02 12:00:00']);
}

it('shows all warehouse stocks separately with dynamic options and the existing totals', function () {
    inventoryFilterUser();
    inventoryFilterFixtures();

    $response = $this->get('/operation_inventories');

    $response->assertOk()->assertViewIs('inventory.index')->assertSee('All Warehouses')->assertSee('New Warehouse')->assertSee('7,000.00');
    expect($response->viewData('inventories')->total())->toBe(4);
    expect($response->viewData('inventories')->sum('qty'))->toBe(65);
    foreach ($response->viewData('inventories') as $inventory) {
        expect($inventory->relationLoaded('warehouse'))->toBeTrue();
    }
});

it('shows only selected warehouse stock and quantities', function (int $warehouseId, array $ids, int $quantity, string $value) {
    inventoryFilterUser();
    inventoryFilterFixtures();

    $response = $this->get('/operation_inventories?warehouse_id='.$warehouseId);

    $response->assertOk()->assertSee($value);
    expect($response->viewData('inventories')->pluck('inventory_id')->sort()->values()->all())->toBe($ids);
    expect($response->viewData('inventories')->sum('qty'))->toBe($quantity);
    $document = new DOMDocument;
    @$document->loadHTML($response->getContent());
    $selected = (new DOMXPath($document))->query('//select[@name="warehouse_id"]/option[@selected]');
    expect($selected->item(0)->getAttribute('value'))->toBe((string) $warehouseId);
})->with([
    'Pampanga' => [1, [1, 4], 15, '2,000.00'],
    'Cebu' => [2, [2], 20, '2,000.00'],
    'Davao' => [3, [3], 30, '3,000.00'],
]);

it('combines warehouse with search project and status filters', function (array $filters, array $ids) {
    inventoryFilterUser();
    inventoryFilterFixtures();

    $response = $this->get('/operation_inventories?'.http_build_query(['warehouse_id' => 1, ...$filters]));

    $response->assertOk();
    expect($response->viewData('inventories')->pluck('inventory_id')->all())->toBe($ids);
})->with([
    'search' => [['search' => 'chair'], [1]],
    'project' => [['project_id' => 2], [4]],
    'status' => [['inventory_status' => 'Approved'], [1]],
    'all filters' => [['search' => 'desk', 'project_id' => 2, 'inventory_status' => 'For Approval'], [4]],
    'no match' => [['search' => 'chair', 'project_id' => 2], []],
]);

it('shows inventory transactions and current stock grouped by item and warehouse', function () {
    inventoryFilterUser();
    inventoryFilterFixtures();
    Inventory::create(['inventory_id' => 5, 'item_id' => 1, 'warehouse_id' => 1, 'qty' => 7, 'inventory_status' => 'Approved', 'created_at' => '2026-10-03 12:00:00']);
    DB::table('inventory_history')->insert([
        ['item_id' => 1, 'warehouse_id' => 1, 'old_qty' => 10, 'new_qty' => 15, 'change_type' => 'stock_in'],
        ['item_id' => 1, 'warehouse_id' => 1, 'old_qty' => 15, 'new_qty' => 12, 'change_type' => 'stock_out'],
        ['item_id' => 1, 'warehouse_id' => 2, 'old_qty' => 0, 'new_qty' => 8, 'change_type' => 'stock_in'],
    ]);

    $filtered = $this->get('/inventory-balances?'.http_build_query([
        'project_id' => 1,
        'warehouse_id' => 1,
        'search' => 'Chair',
    ]));

    $filtered->assertOk()->assertSee('Stock In Quantity')->assertSee('Stock Out Quantity')->assertSee('Stock on Hand Quantity');
    expect($filtered->viewData('inventories')->total())->toBe(1);
    expect((array) $filtered->viewData('inventories')->first())->toMatchArray([
        'stock_in_quantity' => 5,
        'stock_out_quantity' => 3,
        'stock_on_hand_quantity' => 2,
    ]);

    $projectRows = $this->get('/inventory-balances?project_id=1')->viewData('inventories');
    expect($projectRows->total())->toBe(3);
    expect($projectRows->getCollection()->pluck('warehouse_name')->unique()->count())->toBe(3);
});

it('calculates inventory percentages against actual delivery quantities and preserves negative balances', function () {
    inventoryFilterUser();
    inventoryFilterFixtures();
    DB::table('item')->insert([
        'item_id' => 3,
        'item_name' => 'Table',
        'project_id' => 1,
    ]);
    Inventory::create(['inventory_id' => 5, 'item_id' => 3, 'warehouse_id' => 1, 'qty' => 60, 'inventory_status' => 'Approved', 'created_at' => '2026-10-03 12:00:00']);
    DB::table('logistics_location')->insert([
        ['logistics_location_id' => 1, 'warehouse_id' => 1],
        ['logistics_location_id' => 2, 'warehouse_id' => 2],
    ]);
    DB::table('package')->insert([
        ['package_id' => 1, 'lot_id' => 1],
        ['package_id' => 2, 'lot_id' => 2],
    ]);
    DB::table('package_content')->insert([
        ['package_id' => 1, 'item_id' => 1, 'qty' => 2],
        ['package_id' => 2, 'item_id' => 1, 'qty' => 3],
        ['package_id' => 1, 'item_id' => 3, 'qty' => 18],
    ]);
    DB::table('deliveries')->insert([
        ['delivery_id' => 1, 'project_id' => 1, 'lot_id' => 1, 'package_qty' => 10, 'logistics_location_id' => 1],
        ['delivery_id' => 2, 'project_id' => 1, 'lot_id' => 2, 'package_qty' => 10, 'logistics_location_id' => 2],
    ]);
    DB::table('inventory_history')->insert([
        ['item_id' => 1, 'warehouse_id' => 1, 'old_qty' => 0, 'new_qty' => 10, 'change_type' => 'stock_in'],
        ['item_id' => 1, 'warehouse_id' => 1, 'old_qty' => 22, 'new_qty' => 10, 'change_type' => 'stock_out'],
        ['item_id' => 1, 'warehouse_id' => 2, 'old_qty' => 0, 'new_qty' => 20, 'change_type' => 'stock_in'],
        ['item_id' => 1, 'warehouse_id' => 2, 'old_qty' => 30, 'new_qty' => 20, 'change_type' => 'stock_out'],
        ['item_id' => 3, 'warehouse_id' => 1, 'old_qty' => 0, 'new_qty' => 90, 'change_type' => 'stock_in'],
        ['item_id' => 3, 'warehouse_id' => 1, 'old_qty' => 120, 'new_qty' => 90, 'change_type' => 'stock_out'],
    ]);

    $response = $this->get('/inventory-balances?project_id=1&search=a');

    $response->assertOk()
        ->assertSee('Warehouse Summary')
        ->assertSee('Stock In % of Actual DR')
        ->assertSee('Stock Out % of Stock In')
        ->assertSee('Stock on Hand % of Actual DR')
        ->assertSee('120.0%')
        ->assertSee('-10.0%');

    $summaries = $response->viewData('warehouseSummaries')->keyBy('warehouse_id');
    expect($summaries)->toHaveCount(3);
    expect(round((float) $summaries[1]->stock_in_percentage, 1))->toBe(50.0);
    expect(round((float) $summaries[1]->stock_out_percentage, 1))->toBe(42.0);
    expect(round((float) $summaries[1]->stock_on_hand_percentage, 1))->toBe(29.0);
    expect(round((float) $summaries[2]->stock_in_percentage, 1))->toBe(66.7);
    expect((float) $summaries[3]->stock_in_percentage)->toBe(0.0);

    $inventories = $response->viewData('inventories')->getCollection()->keyBy(fn ($inventory) => $inventory->item_id.':'.$inventory->warehouse_id);
    expect(round((float) $inventories['1:1']->stock_in_percentage, 1))->toBe(50.0);
    expect(round((float) $inventories['1:1']->stock_out_percentage, 1))->toBe(120.0);
    expect((float) $inventories['1:1']->stock_on_hand_quantity)->toBe(-2.0);
    expect(round((float) $inventories['1:1']->stock_on_hand_percentage, 1))->toBe(-10.0);
});

it('sums large delivery allocation sets in inventory balances', function () {
    inventoryFilterUser();
    inventoryFilterFixtures();
    DB::table('logistics_location')->insert(['logistics_location_id' => 1, 'warehouse_id' => 1]);
    DB::table('package')->insert(['package_id' => 1, 'lot_id' => 1]);
    DB::table('package_content')->insert(['package_id' => 1, 'item_id' => 1, 'qty' => 2]);

    foreach (array_chunk(range(1, 10001), 100) as $deliveryIds) {
        DB::table('deliveries')->insert(array_map(static fn (int $deliveryId): array => [
            'delivery_id' => $deliveryId,
            'project_id' => 1,
            'lot_id' => 1,
            'package_qty' => 3,
            'logistics_location_id' => 1,
        ], $deliveryIds));
    }

    $response = $this->get('/inventory-balances');

    $response->assertOk();
    $inventory = $response->viewData('inventories')->first(fn ($inventory) => $inventory->item_id === 1 && $inventory->warehouse_id === 1);
    expect((int) $inventory->actual_dr_quantity)->toBe(60006);
});

it('shows zero percentages when a filtered quantity has no total', function () {
    inventoryFilterUser();
    inventoryFilterFixtures();

    $response = $this->get('/inventory-balances?project_id=2');

    $summary = $response->viewData('warehouseSummaries')->first();
    $inventory = $response->viewData('inventories')->first();
    expect((float) $summary->stock_in_percentage)->toBe(0.0);
    expect((float) $summary->stock_out_percentage)->toBe(0.0);
    expect((float) $inventory->stock_in_percentage)->toBe(0.0);
    expect((float) $inventory->stock_out_percentage)->toBe(0.0);
});

it('attributes delivery quantity through the configured school region warehouse mapping', function () {
    inventoryFilterUser();
    inventoryFilterFixtures();
    DB::table('school')->insert(['school_id' => 'S-REGION-I', 'region' => 'Region I']);
    DB::table('package')->insert(['package_id' => 9, 'lot_id' => 9]);
    DB::table('package_content')->insert(['package_id' => 9, 'item_id' => 1, 'qty' => 2]);
    DB::table('deliveries')->insert([
        'delivery_id' => 9,
        'project_id' => 1,
        'school_id' => 'S-REGION-I',
        'lot_id' => 9,
        'package_qty' => 3,
        'dr_no' => 'DR-REGION-I',
    ]);
    DB::table('inventory_history')->insert([
        'item_id' => 1,
        'warehouse_id' => 1,
        'old_qty' => 0,
        'new_qty' => 3,
        'change_type' => 'stock_in',
    ]);

    $response = $this->get('/inventory-balances?project_id=1&warehouse_id=1&search=Chair');

    $inventory = $response->viewData('inventories')->first();
    $summary = $response->viewData('warehouseSummaries')->first();
    expect($inventory->warehouse_id)->toBe(1);
    expect((int) $inventory->actual_dr_quantity)->toBe(6);
    expect(round((float) $inventory->stock_in_percentage, 1))->toBe(50.0);
    expect((int) $summary->actual_dr_quantity)->toBe(6);
    expect(round((float) $summary->stock_in_percentage, 1))->toBe(50.0);
});

it('marks actual delivery quantity unavailable when school region has no warehouse mapping', function () {
    inventoryFilterUser();
    inventoryFilterFixtures();
    DB::table('school')->insert(['school_id' => 'S-UNKNOWN-REGION', 'region' => 'Unmapped Region']);
    DB::table('package')->insert(['package_id' => 10, 'lot_id' => 10]);
    DB::table('package_content')->insert(['package_id' => 10, 'item_id' => 1, 'qty' => 2]);
    DB::table('deliveries')->insert([
        'delivery_id' => 10,
        'project_id' => 1,
        'school_id' => 'S-UNKNOWN-REGION',
        'lot_id' => 10,
        'package_qty' => 3,
        'dr_no' => 'DR-UNKNOWN-REGION',
    ]);

    $response = $this->get('/inventory-balances?project_id=1&warehouse_id=1&search=Chair');

    $response->assertOk()
        ->assertSee('Address-to-warehouse mapping needs review')
        ->assertSee('Some Actual DR quantities could not be confidently attributed to a warehouse');
    $inventory = $response->viewData('inventories')->first();
    $summary = $response->viewData('warehouseSummaries')->first();
    expect($inventory->actual_dr_quantity)->toBeNull();
    expect($inventory->stock_in_percentage)->toBeNull();
    expect($summary->actual_dr_quantity)->toBeNull();
    expect($summary->stock_on_hand_percentage)->toBeNull();
});

it('does not allow the inventory balances filter to select another company project', function () {
    $user = inventoryFilterUser();
    inventoryFilterFixtures();
    Schema::table('projects', fn ($table) => $table->unsignedInteger('company_id')->nullable());
    DB::table('projects')->where('project_id', 1)->update(['company_id' => $user->currentCompany()->getKey()]);
    DB::table('projects')->where('project_id', 2)->update(['company_id' => 999]);

    $this->get('/inventory-balances?project_id=2')->assertNotFound();
});

it('keeps the inventory balances page limited to MMC users', function (string $company, string $role) {
    inventoryFilterUser($company, $role);

    $this->get('/inventory-balances')->assertForbidden();
})->with([
    'other company' => ['MI', 'user'],
    'other role' => ['MMC', 'accounting'],
]);

it('preserves every filter on pagination links and keeps pages warehouse scoped', function () {
    inventoryFilterUser();
    inventoryFilterFixtures();
    foreach (range(5, 15) as $id) {
        Inventory::create(['inventory_id' => $id, 'item_id' => 1, 'warehouse_id' => 2, 'qty' => 1, 'inventory_status' => 'Approved', 'created_at' => '2026-10-03 12:00:00']);
    }
    $query = ['warehouse_id' => 2, 'search' => 'chair', 'project_id' => 1, 'inventory_status' => 'Approved'];

    $response = $this->get('/operation_inventories?'.http_build_query($query));

    $response->assertOk();
    $url = $response->viewData('inventories')->nextPageUrl();
    parse_str(parse_url($url, PHP_URL_QUERY), $parameters);
    expect($parameters)->toEqual([...$query, 'page' => 2]);
    $next = $this->get($url)->assertOk();
    expect($next->viewData('inventories')->total())->toBe(12);
    expect($next->viewData('inventories')->pluck('warehouse_id')->unique()->all())->toBe([2]);
});

it('resets all filters through the existing reset link', function () {
    inventoryFilterUser();
    inventoryFilterFixtures();
    $response = $this->get('/operation_inventories?warehouse_id=2&search=chair&project_id=1&inventory_status=Approved');
    $document = new DOMDocument;
    @$document->loadHTML($response->getContent());
    $reset = (new DOMXPath($document))->query('//a[normalize-space(.)="Reset"]')->item(0)->getAttribute('href');

    $response = $this->get($reset);

    $response->assertOk();
    expect(parse_url($reset, PHP_URL_QUERY))->toBeNull();
    expect($response->viewData('inventories')->total())->toBe(4);
});

it('filters inventory history by a company-scoped project and preserves the selected project', function () {
    $user = inventoryFilterUser();
    inventoryFilterFixtures();
    Schema::table('projects', fn ($table) => $table->unsignedInteger('company_id')->nullable());
    Schema::table('inventory_history', function ($table): void {
        $table->string('changed_by')->nullable();
        $table->text('remarks')->nullable();
    });
    $companyId = $user->currentCompany()->getKey();
    DB::table('projects')->where('project_id', 1)->update(['company_id' => $companyId]);
    DB::table('projects')->where('project_id', 2)->update(['company_id' => 999]);
    foreach (range(1, 51) as $id) {
        DB::table('inventory_history')->insert([
            'history_id' => $id,
            'item_id' => 1,
            'warehouse_id' => 1,
            'old_qty' => $id,
            'new_qty' => $id + 1,
            'change_type' => 'update',
            'changed_at' => '2026-10-01 12:00:00',
        ]);
    }
    DB::table('inventory_history')->insert([
        ['history_id' => 52, 'item_id' => 2, 'warehouse_id' => 1, 'old_qty' => 2, 'new_qty' => 3, 'change_type' => 'update', 'changed_at' => '2026-10-02 12:00:00'],
        ['history_id' => 53, 'item_id' => null, 'warehouse_id' => 1, 'old_qty' => 3, 'new_qty' => 4, 'change_type' => 'update', 'changed_at' => '2026-10-03 12:00:00'],
    ]);

    $filters = [
        'project_id' => 1,
        'search' => 'Chair',
        'change_type' => 'update',
        'warehouse_id' => 1,
        'date_from' => '2026-10-01',
        'date_to' => '2026-10-01',
    ];
    $response = $this->get('/inventory-history?'.http_build_query($filters));

    $response->assertOk()->assertSee('Project')->assertSee('School');
    expect($response->viewData('histories')->total())->toBe(51);
    expect($response->viewData('projects')->pluck('project_id')->all())->toBe([1]);
    parse_str(parse_url($response->viewData('histories')->nextPageUrl(), PHP_URL_QUERY), $parameters);
    expect($parameters)->toMatchArray([
        'project_id' => '1',
        'search' => 'Chair',
        'change_type' => 'update',
        'warehouse_id' => '1',
        'date_from' => '2026-10-01',
        'date_to' => '2026-10-01',
    ]);

    $this->get($response->viewData('histories')->nextPageUrl())->assertOk();
});

it('does not expose a project outside the selected company through the history filter', function () {
    $user = inventoryFilterUser();
    inventoryFilterFixtures();
    Schema::table('projects', fn ($table) => $table->unsignedInteger('company_id')->nullable());
    DB::table('projects')->where('project_id', 2)->update(['company_id' => 999]);

    $this->get('/inventory-history?project_id=2')->assertNotFound();
});

it('rejects invalid warehouse IDs with validation errors', function (mixed $id) {
    inventoryFilterUser();
    inventoryFilterFixtures();

    $response = $this->from('/operation_inventories')->get('/operation_inventories?'.http_build_query(['warehouse_id' => $id]));

    $response->assertRedirect('/operation_inventories')->assertSessionHasErrors('warehouse_id');
})->with(['missing ID' => 9999, 'non integer' => 'bad', 'array' => [[1]], 'injection' => '1 OR 1=1']);

it('treats an empty warehouse selection as all warehouses', function () {
    inventoryFilterUser();
    inventoryFilterFixtures();

    $response = $this->get('/operation_inventories?warehouse_id=');

    $response->assertOk();
    expect($response->viewData('inventories')->total())->toBe(4);
});

it('keeps inventory creation viewing editing and updating available', function () {
    inventoryFilterUser();
    inventoryFilterFixtures();
    $this->get('/inventories/create')->assertOk()->assertSee('Pampanga');
    $this->get('/inventories/1/show')->assertOk()->assertSee('Chair');
    $this->get('/inventories/1/edit')->assertOk()->assertSee('Chair');

    $response = $this->put('/inventories/1', ['qty' => 12, 'inventory_status' => 'Approved']);

    $response->assertRedirect(route('inventory.index'));
    $this->assertDatabaseHas('inventory', ['inventory_id' => 1, 'qty' => 12, 'warehouse_id' => 1]);
});

it('requires authentication to list inventories', function () {
    $this->get('/operation_inventories?warehouse_id=1')->assertRedirect(route('login'));
});

it('rejects users outside the MMC company or operations role', function (string $company, string $role) {
    inventoryFilterUser($company, $role);

    $this->get('/operation_inventories?warehouse_id=1')->assertForbidden();
})->with(['wrong company' => ['MI', 'user'], 'wrong role' => ['MMC', 'accounting']]);

it('rejects a different selected company even with MMC membership', function () {
    inventoryFilterUser();
    $company = Company::create(['code' => 'MI', 'name' => 'MI', 'is_active' => true]);

    $this->withSession(['company_id' => $company->company_id])->get('/operation_inventories?warehouse_id=1')->assertForbidden();
});
