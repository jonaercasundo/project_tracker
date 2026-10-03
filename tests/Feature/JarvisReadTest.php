<?php

use App\Models\Company;
use App\Models\User;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Testing\TestResponse;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class JarvisReadTestCase extends TestCase
{
    use RefreshDatabase;

    public function createApplication(): Application
    {
        $app = require Application::inferBasePath().'/bootstrap/app.php';
        $app->singleton(Kernel::class, JarvisReadConsoleKernel::class);
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
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        RefreshDatabaseState::$migrated = false;
        RefreshDatabaseState::$inMemoryConnections = [];
    }
}

/** Avoid unrelated legacy command discovery, as in JarvisIntegrationTest. */
class JarvisReadConsoleKernel extends Illuminate\Foundation\Console\Kernel
{
    protected function discoverCommands(): void {}
}

pest()->extend(JarvisReadTestCase::class);

dataset('jarvis read endpoints', [
    'dashboard', 'projects', 'projects/1', 'operation-masterlist',
    'deliveries', 'inventory', 'warehouses', 'lots', 'packages',
]);

function jarvisReader(bool $mmc = true, bool $active = true, bool $administrator = true): User
{
    $role = $administrator ? 'Administrator' : 'user';
    $user = User::factory()->create(['username' => fake()->unique()->userName(), 'role' => $role]);
    $user->assignRole(Role::findOrCreate($role, 'web'));
    $company = Company::create(['name' => $mmc ? 'MMC' : 'MI', 'code' => $mmc ? 'MMC' : 'MI', 'is_active' => $active]);
    $user->companies()->attach($company);

    return $user;
}

function jarvisRead(string $path, string $token, array $filters = []): TestResponse
{
    Auth::forgetGuards();

    return test()->withToken($token)->getJson('/api/jarvis/'.$path.($filters ? '?'.http_build_query($filters) : ''));
}

/** Minimal records in the inspected legacy schema; legacy models have no factories. */
function jarvisOperations(): void
{
    DB::table('projects')->insert([
        ['project_id' => 1, 'project_name' => 'Science Kits', 'project_code' => 'SCI', 'ref_no' => 'REF-001', 'status' => 'Ongoing', 'agency' => 'DepEd', 'created_at' => '2026-01-01', 'contract_amount' => 1000],
        ['project_id' => 2, 'project_name' => 'Furniture', 'project_code' => 'FUR', 'ref_no' => 'REF-002', 'status' => 'Delivered', 'agency' => 'DepEd', 'created_at' => '2025-01-01', 'contract_amount' => 2000],
    ]);
    DB::table('lot')->insert([
        ['lot_id' => 1, 'project_id' => 1, 'lot_name' => 'LOT 1', 'contract_no' => 'C1'],
        ['lot_id' => 2, 'project_id' => 2, 'lot_name' => 'LOT 2', 'contract_no' => 'C2'],
    ]);
    DB::table('school')->insert([
        ['school_id' => '001', 'school_name' => 'Central School', 'region' => 'Region I', 'division' => 'North', 'municipality' => 'Town A'],
        ['school_id' => '002', 'school_name' => 'South School', 'region' => 'Region II', 'division' => 'South', 'municipality' => 'Town B'],
    ]);
    DB::table('warehouse')->insert([
        ['warehouse_id' => 1, 'warehouse_name' => 'Main', 'warehouse_address' => 'A'],
        ['warehouse_id' => 2, 'warehouse_name' => 'Empty', 'warehouse_address' => 'B'],
    ]);
    DB::table('logistics_location')->insert(['logistics_location_id' => 1, 'warehouse_id' => 1]);
    DB::table('deliveries')->insert([
        ['delivery_id' => 1, 'project_id' => 1, 'school_id' => '001', 'lot_id' => 1, 'keystage_id' => null, 'dr_no' => '3502', 'status' => 'delivered', 'delivery_date' => '2026-10-02', 'delivered_date' => '2026-10-03', 'accepted_date' => null, 'logistics_location_id' => 1, 'package_qty' => 2],
        ['delivery_id' => 2, 'project_id' => 1, 'school_id' => '001', 'lot_id' => 1, 'keystage_id' => null, 'dr_no' => '3502-X', 'status' => 'accepted', 'delivery_date' => '2026-10-01', 'delivered_date' => '2026-10-02', 'accepted_date' => '2026-10-03', 'logistics_location_id' => 1, 'package_qty' => 1],
        ['delivery_id' => 3, 'project_id' => 2, 'school_id' => '002', 'lot_id' => 2, 'keystage_id' => null, 'dr_no' => '3503', 'status' => 'pending', 'delivery_date' => '2025-10-01', 'delivered_date' => null, 'accepted_date' => null, 'logistics_location_id' => null, 'package_qty' => 1],
    ]);
    DB::table('package')->insert([
        ['package_id' => 1, 'package_num' => 1, 'lot_id' => 1, 'keystage_id' => null],
        ['package_id' => 2, 'package_num' => 2, 'lot_id' => 1, 'keystage_id' => null],
        ['package_id' => 3, 'package_num' => 1, 'lot_id' => 2, 'keystage_id' => null],
    ]);
    DB::table('package_status')->insert([
        ['package_status_id' => 1, 'delivery_id' => 1, 'package_id' => 1, 'status' => 'released'],
        ['package_status_id' => 2, 'delivery_id' => 2, 'package_id' => 1, 'status' => 'delivered'],
        ['package_status_id' => 3, 'delivery_id' => 2, 'package_id' => 2, 'status' => 'accepted'],
    ]);
    DB::table('item')->insert([
        ['item_id' => 1, 'item_name' => 'Microscope', 'project_id' => 1, 'unit' => 'pcs', 'price' => 50],
        ['item_id' => 2, 'item_name' => 'Desk', 'project_id' => 2, 'unit' => 'pcs', 'price' => 100],
    ]);
    DB::table('package_content')->insert([
        ['package_id' => 1, 'item_id' => 1, 'qty' => 2],
        ['package_id' => 2, 'item_id' => 1, 'qty' => 3],
        ['package_id' => 3, 'item_id' => 2, 'qty' => 1],
    ]);
    DB::table('inventory')->insert([
        ['inventory_id' => 1, 'item_id' => 1, 'warehouse_id' => 1, 'qty' => 10, 'inventory_status' => 'Approved'],
        ['inventory_id' => 2, 'item_id' => 2, 'warehouse_id' => 1, 'qty' => 5, 'inventory_status' => 'For Approval'],
        ['inventory_id' => 3, 'item_id' => 1, 'warehouse_id' => 1, 'qty' => 0, 'inventory_status' => 'Approved'],
    ]);
    DB::table('grouping')->insert(['group_id' => 1, 'status' => 'billed']);
    DB::table('billing_grouped')->insert(['id' => 1, 'group_id' => 1, 'dr_no' => 3502]);
    DB::table('items')->insert(['id' => 1, 'item_id' => 'KIT-0001', 'code_prefix' => 'KIT', 'item_name' => 'Catalog kit', 'project_id' => 'BID-2026', 'lot_id' => 90, 'active' => 1, 'price' => 10]);
}

it('returns 401 without a bearer token on every read endpoint', function (string $endpoint) {
    $this->get('/api/jarvis/'.$endpoint)->assertUnauthorized()->assertJsonPath('success', false);
})->with('jarvis read endpoints');

it('returns 403 without active MMC membership on every endpoint', function (string $endpoint) {
    $token = jarvisReader(false)->createToken('JARVIS', ['jarvis:read'])->plainTextToken;

    jarvisRead($endpoint, $token)->assertForbidden()->assertJsonPath('success', false);
})->with('jarvis read endpoints');

it('returns 403 for inactive company membership', function () {
    $token = jarvisReader(true, false)->createToken('JARVIS', ['jarvis:read'])->plainTextToken;

    jarvisRead('dashboard', $token)->assertForbidden();
});

it('returns 403 for a different company even when the owner belongs to both', function () {
    $user = jarvisReader();
    $other = Company::create(['name' => 'MI', 'code' => 'MI', 'is_active' => true]);
    $user->companies()->attach($other);
    $token = $user->createToken('JARVIS', ['jarvis:read'])->plainTextToken;

    jarvisRead('projects', $token, ['company_id' => $other->company_id])->assertForbidden();
});

it('returns 403 without jarvis read ability', function () {
    $token = jarvisReader()->createToken('JARVIS', ['other:read'])->plainTextToken;

    jarvisRead('dashboard', $token)->assertForbidden();
});

it('returns 401 for invalid expired revoked or differently named tokens', function (string $kind) {
    $user = jarvisReader();
    $issued = $user->createToken($kind === 'other' ? 'OTHER' : 'JARVIS', ['jarvis:read'], $kind === 'expired' ? now()->subMinute() : null);
    if ($kind === 'revoked') {
        $issued->accessToken->delete();
    }
    $token = $kind === 'invalid' ? 'not-a-token' : $issued->plainTextToken;

    jarvisRead('dashboard', $token)->assertUnauthorized()->assertJsonPath('success', false);
})->with(['invalid', 'expired', 'revoked', 'other']);

it('returns 403 when the token owner loses administrator access', function () {
    $token = jarvisReader(true, true, false)->createToken('JARVIS', ['jarvis:read'])->plainTextToken;

    jarvisRead('dashboard', $token)->assertForbidden();
});

it('returns 401 for session authentication without a JARVIS bearer token', function () {
    $user = jarvisReader();

    $this->actingAs($user)->getJson('/api/jarvis/dashboard')->assertUnauthorized();
});

it('returns structured data on every read endpoint without modifying operations', function (string $endpoint) {
    jarvisOperations();
    $user = jarvisReader();
    $token = $user->createToken('JARVIS', ['jarvis:read'])->plainTextToken;

    jarvisRead($endpoint, $token)
        ->assertOk()->assertJsonPath('success', true)->assertJsonPath('meta.company.code', 'MMC')
        ->assertJsonStructure(['success', 'data', 'meta' => ['supported_filters', 'definitions']]);
    $this->assertDatabaseCount('deliveries', 3);
    $this->assertDatabaseCount('package_status', 3);
    $this->assertDatabaseHas('inventory', ['inventory_id' => 1, 'qty' => 10]);
})->with('jarvis read endpoints');

it('filters projects by real status reference name year and pending allocations', function () {
    jarvisOperations();
    $token = jarvisReader()->createToken('JARVIS', ['jarvis:read'])->plainTextToken;

    jarvisRead('projects', $token, ['status' => 'Ongoing', 'search' => 'Science', 'year' => 2026, 'ref_no' => 'REF-001', 'has_pending_deliveries' => 1])
        ->assertJsonCount(1, 'data')->assertJsonPath('data.0.project_id', 1)
        ->assertJsonPath('data.0.pending_delivery_rows_count', 1);
});

it('returns inclusive delivery date filters and independent package and billing statuses', function () {
    jarvisOperations();
    $token = jarvisReader()->createToken('JARVIS', ['jarvis:read'])->plainTextToken;

    jarvisRead('deliveries', $token, ['project_id' => 1, 'lot_id' => 1, 'warehouse_id' => 1, 'region' => 'Region I', 'division' => 'North', 'municipality' => 'Town A', 'delivery_status' => 'delivered', 'package_status' => 'released', 'billing_status' => 'billed', 'date_from' => '2026-10-02', 'date_to' => '2026-10-02'])
        ->assertJsonCount(1, 'data')->assertJsonPath('data.0.delivery_id', 1)
        ->assertJsonPath('data.0.released_packages_count', 1)
        ->assertJsonPath('data.0.pending_packages_count', 1)
        ->assertJsonPath('data.0.billed_groups_count', 1);
});

it('does not coerce alphanumeric receipt numbers into billing matches', function () {
    jarvisOperations();
    $token = jarvisReader()->createToken('JARVIS', ['jarvis:read'])->plainTextToken;

    jarvisRead('deliveries', $token, ['delivery_id' => 2, 'billing_status' => 'unknown'])
        ->assertJsonCount(1, 'data')->assertJsonPath('data.0.dr_no', '3502-X')
        ->assertJsonPath('data.0.billing_groups_count', 0);
});

it('filters by the selected delivery event date', function () {
    jarvisOperations();
    $token = jarvisReader()->createToken('JARVIS', ['jarvis:read'])->plainTextToken;

    jarvisRead('deliveries', $token, ['date_field' => 'delivered_date', 'date_from' => '2026-10-02', 'date_to' => '2026-10-02'])
        ->assertJsonCount(1, 'data')->assertJsonPath('data.0.delivery_id', 2);
});

it('includes missing package statuses as pending without generating status records', function () {
    jarvisOperations();
    $token = jarvisReader()->createToken('JARVIS', ['jarvis:read'])->plainTextToken;

    jarvisRead('packages', $token, ['project_id' => 1, 'package_status' => 'pending'])
        ->assertJsonCount(1, 'data')->assertJsonPath('data.0.package_id', 2)
        ->assertJsonPath('data.0.package_status_id', null)->assertJsonPath('data.0.delivery_id', 1);
    $this->assertDatabaseCount('package_status', 3);
});

it('matches packages by keystage before lot and inherits item lot from keystage', function () {
    jarvisOperations();
    DB::table('keystage')->insert(['keystage_id' => 10, 'lot_id' => 1]);
    DB::table('package')->insert(['package_id' => 4, 'keystage_id' => 10, 'lot_id' => null, 'package_num' => 4]);
    DB::table('package_content')->insert(['package_id' => 4, 'item_id' => 1, 'qty' => 1]);
    DB::table('deliveries')->where('delivery_id', 1)->update(['keystage_id' => 10]);
    $token = jarvisReader()->createToken('JARVIS', ['jarvis:read'])->plainTextToken;

    jarvisRead('packages', $token, ['delivery_id' => 1])->assertJsonCount(1, 'data')->assertJsonPath('data.0.package_id', 4);
    jarvisRead('operation-masterlist', $token, ['source' => 'operations', 'package_id' => 4, 'lot_id' => 1])->assertJsonCount(1, 'data')->assertJsonPath('data.0.item_id', 1);
});

it('separates catalog identifiers from operational lot item membership', function () {
    jarvisOperations();
    $token = jarvisReader()->createToken('JARVIS', ['jarvis:read'])->plainTextToken;

    jarvisRead('operation-masterlist', $token, ['catalog_project_id' => 'BID-2026', 'active' => 1])
        ->assertJsonCount(1, 'data')->assertJsonPath('data.0.item_id', 'KIT-0001')->assertJsonPath('data.0.active', true);
    jarvisRead('operation-masterlist', $token, ['source' => 'operations', 'lot_id' => 1])
        ->assertJsonCount(1, 'data')->assertJsonPath('data.0.item_name', 'Microscope');
});

it('filters inventory via item project and keeps approval separate from positive stock', function () {
    jarvisOperations();
    $token = jarvisReader()->createToken('JARVIS', ['jarvis:read'])->plainTextToken;

    jarvisRead('inventory', $token, ['project_id' => 1, 'available_only' => 1, 'inventory_status' => 'Approved'])
        ->assertJsonCount(1, 'data')->assertJsonPath('data.0.qty', 10);
    jarvisRead('inventory', $token, ['available_only' => 1])->assertJsonCount(2, 'data');
});

it('includes empty warehouses and aggregates only the selected stock', function () {
    jarvisOperations();
    $token = jarvisReader()->createToken('JARVIS', ['jarvis:read'])->plainTextToken;

    jarvisRead('warehouses', $token)->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.recorded_qty', 15)->assertJsonPath('data.0.approved_qty', 10)
        ->assertJsonPath('data.1.recorded_qty', 0);
    jarvisRead('warehouses', $token, ['project_id' => 1])->assertJsonCount(1, 'data')->assertJsonPath('data.0.recorded_qty', 10);
});

it('counts schools once and limits all dashboard metrics to selected projects', function () {
    jarvisOperations();
    $token = jarvisReader()->createToken('JARVIS', ['jarvis:read'])->plainTextToken;

    jarvisRead('dashboard', $token, ['year' => 2026])
        ->assertJsonPath('data.projects_count', 1)
        ->assertJsonPath('data.projects_with_pending_deliveries_count', 1)
        ->assertJsonPath('data.deliveries.delivery_rows_count', 2)
        ->assertJsonPath('data.deliveries.schools_with_delivered_or_accepted_rows', 1)
        ->assertJsonPath('data.deliveries.schools_with_delivered_or_accepted_packages', 1)
        ->assertJsonPath('data.inventory.recorded_qty', 10);
});

it('returns 404 for an unknown project', function () {
    $token = jarvisReader()->createToken('JARVIS', ['jarvis:read'])->plainTextToken;

    jarvisRead('projects/999', $token)->assertNotFound()->assertJsonPath('success', false);
});

it('counts package receipts independently of stale delivery row status', function () {
    jarvisOperations();
    DB::table('deliveries')->where('project_id', 1)->update(['status' => 'pending']);
    $token = jarvisReader()->createToken('JARVIS', ['jarvis:read'])->plainTextToken;

    jarvisRead('dashboard', $token, ['project_id' => 1])
        ->assertJsonPath('data.deliveries.schools_with_delivered_or_accepted_rows', 0)
        ->assertJsonPath('data.deliveries.schools_with_delivered_or_accepted_packages', 1);
});

it('paginates deterministically and exposes the full filtered total', function () {
    jarvisOperations();
    $token = jarvisReader()->createToken('JARVIS', ['jarvis:read'])->plainTextToken;

    jarvisRead('projects', $token, ['per_page' => 1, 'page' => 2])
        ->assertJsonCount(1, 'data')->assertJsonPath('data.0.project_id', 2)
        ->assertJsonPath('meta.pagination.total', 2)->assertJsonPath('meta.pagination.last_page', 2);
});

it('rejects unsupported unsafe or invalid filters with structured 422 errors', function (string $endpoint, array $filters, string $field) {
    $token = jarvisReader()->createToken('JARVIS', ['jarvis:read'])->plainTextToken;

    jarvisRead($endpoint, $token, $filters)->assertUnprocessable()
        ->assertJsonPath('success', false)->assertJsonValidationErrors($field);
})->with([
    ['projects', ['per_page' => 101], 'per_page'],
    ['projects', ['page' => 0], 'page'],
    ['projects', ['year' => 0], 'year'],
    ['projects', ['project_id' => [1]], 'project_id'],
    ['projects', ['sort' => 'project_id desc; drop table projects'], 'sort'],
    ['deliveries', ['date_field' => 'released_at'], 'date_field'],
    ['deliveries', ['date_from' => '2026-10-03', 'date_to' => '2026-10-02'], 'date_to'],
    ['deliveries', ['date_from' => 'today'], 'date_from'],
    ['packages', ['package_status' => 'billed'], 'package_status'],
    ['inventory', ['available_only' => 'yes'], 'available_only'],
    ['inventory', ['inventory_status' => 'Pending'], 'inventory_status'],
    ['operation-masterlist', ['source' => 'catalog', 'lot_id' => 1], 'lot_id'],
    ['projects/1', ['year' => 2026], 'year'],
]);

it('does not expose write methods', function (string $method) {
    $token = jarvisReader()->createToken('JARVIS', ['jarvis:read'])->plainTextToken;

    $this->withToken($token)->json($method, '/api/jarvis/projects')->assertMethodNotAllowed()->assertJsonPath('success', false);
    $this->assertDatabaseCount('projects', 0);
})->with(['POST', 'PUT', 'PATCH', 'DELETE']);

it('keeps package listing query count bounded as records grow', function () {
    jarvisOperations();
    $token = jarvisReader()->createToken('JARVIS', ['jarvis:read'])->plainTextToken;
    DB::enableQueryLog();

    jarvisRead('packages', $token, ['per_page' => 1])->assertOk();
    $smallCount = count(DB::getQueryLog());
    DB::flushQueryLog();
    Auth::forgetGuards();
    jarvisRead('packages', $token, ['per_page' => 100])->assertOk();

    expect(count(DB::getQueryLog()))->toBeLessThanOrEqual($smallCount);
    DB::disableQueryLog();
});

it('filters operational lots by project and lot name', function () {
    jarvisOperations();
    $token = jarvisReader()->createToken('JARVIS', ['jarvis:read'])->plainTextToken;

    jarvisRead('lots', $token, ['project_id' => 1, 'lot_name' => 'LOT 1'])->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.lot_id', 1)->assertJsonPath('data.0.delivery_rows_count', 2);
});

it('excludes cancelled deliveries from pending project summaries', function () {
    jarvisOperations();
    DB::table('deliveries')->where('delivery_id', 1)->update(['status' => 'cancelled']);
    $token = jarvisReader()->createToken('JARVIS', ['jarvis:read'])->plainTextToken;

    jarvisRead('projects', $token, ['project_id' => 1, 'has_pending_deliveries' => 0])
        ->assertJsonCount(1, 'data')->assertJsonPath('data.0.pending_delivery_rows_count', 0);
});

it('returns zero summaries and an empty page for unmatched projects', function () {
    jarvisOperations();
    $token = jarvisReader()->createToken('JARVIS', ['jarvis:read'])->plainTextToken;

    jarvisRead('dashboard', $token, ['project_id' => 999])->assertJsonPath('data.projects_count', 0)
        ->assertJsonPath('data.deliveries.delivery_rows_count', 0)->assertJsonPath('data.inventory.recorded_qty', 0);
    jarvisRead('projects', $token, ['search' => 'unknown'])->assertJsonCount(0, 'data')->assertJsonPath('meta.pagination.total', 0);
});

it('returns matching paid billing groups without duplicating delivery rows', function () {
    jarvisOperations();
    DB::table('grouping')->insert(['group_id' => 2, 'status' => 'paid']);
    DB::table('billing_grouped')->insert(['id' => 2, 'group_id' => 2, 'dr_no' => 3502]);
    $token = jarvisReader()->createToken('JARVIS', ['jarvis:read'])->plainTextToken;

    jarvisRead('deliveries', $token, ['billing_status' => 'paid'])->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.billing_groups_count', 2)->assertJsonPath('data.0.paid_groups_count', 1);
});

it('bounds payloads and does not expose school contact or supplier cost fields', function () {
    jarvisOperations();
    $token = jarvisReader()->createToken('JARVIS', ['jarvis:read'])->plainTextToken;

    jarvisRead('deliveries', $token)->assertJsonMissingPath('data.0.contact')->assertJsonMissingPath('data.0.contact_person');
    jarvisRead('operation-masterlist', $token)->assertJsonMissingPath('data.0.supplier_price');
});

it('returns 422 with a clear message for an unsupported release date', function () {
    $token = jarvisReader()->createToken('JARVIS', ['jarvis:read'])->plainTextToken;

    jarvisRead('deliveries', $token, ['released_from' => '2026-10-02'])->assertUnprocessable()
        ->assertJsonPath('errors.released_from.0', 'Unsupported filter for this endpoint.');
});

it('preserves JSON errors and retry headers when rate limited', function () {
    $token = jarvisReader()->createToken('JARVIS', ['jarvis:read'])->plainTextToken;
    $this->withToken($token)->getJson('/api/jarvis/projects')->assertOk();
    for ($request = 0; $request < 60; $request++) {
        Auth::forgetGuards();
        $response = $this->withToken($token)->getJson('/api/jarvis/projects');
    }

    $response->assertTooManyRequests()->assertJsonPath('success', false)->assertHeader('Retry-After');
});
