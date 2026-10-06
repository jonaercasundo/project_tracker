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
