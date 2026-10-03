<?php

use App\Models\Company;
use App\Models\User;
use App\Services\ProjectDeliveryProgressService;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Testing\TestResponse;
use Laravel\Sanctum\PersonalAccessToken;
use Laravel\Sanctum\Sanctum;
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

/** Keep authentication fixtures in SQLite while operational SQL runs on temporary MariaDB tables. */
class JarvisReadSqliteToken extends PersonalAccessToken
{
    protected $connection = 'sqlite';

    protected $table = 'personal_access_tokens';
}

pest()->extend(JarvisReadTestCase::class);

dataset('jarvis read endpoints', [
    'dashboard', 'projects', 'projects/1', 'operation-masterlist',
    'deliveries', 'inventory', 'warehouses', 'lots', 'packages', 'projects/delivery-progress',
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
    DB::table('inventory_history')->insert([
        ['inventory_id' => 1, 'item_id' => 1, 'warehouse_id' => 1, 'old_qty' => 0, 'new_qty' => 10, 'change_type' => 'insert'],
        ['inventory_id' => 2, 'item_id' => 2, 'warehouse_id' => 1, 'old_qty' => 0, 'new_qty' => 5, 'change_type' => 'insert'],
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

it('preserves all delivery counts including null duplicate and unexpected package statuses', function () {
    jarvisOperations();
    DB::table('package')->insert([
        ['package_id' => 4, 'lot_id' => 1],
        ['package_id' => 5, 'lot_id' => 1],
    ]);
    DB::table('package_status')->insert([
        ['delivery_id' => 1, 'package_id' => 1, 'status' => 'delivered'],
        ['delivery_id' => 1, 'package_id' => 2, 'status' => null],
        ['delivery_id' => 1, 'package_id' => 4, 'status' => 'accepted'],
        ['delivery_id' => 1, 'package_id' => 5, 'status' => 'warehouse'],
        ['delivery_id' => 1, 'package_id' => 3, 'status' => 'paid'],
    ]);
    DB::table('grouping')->insert([
        ['group_id' => 2, 'status' => 'paid'],
        ['group_id' => 3, 'status' => 'for billing'],
    ]);
    DB::table('billing_grouped')->insert([
        ['group_id' => 2, 'dr_no' => '3502'],
        ['group_id' => 3, 'dr_no' => '3502'],
        ['group_id' => 2, 'dr_no' => '3502-X'],
    ]);
    $token = jarvisReader()->createToken('JARVIS', ['jarvis:read'])->plainTextToken;

    jarvisRead('deliveries', $token, ['delivery_id' => 1, 'package_status' => 'released', 'billing_status' => 'paid'])
        ->assertOk()->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.package_allocations_count', 5)
        ->assertJsonPath('data.0.pending_packages_count', 1)
        ->assertJsonPath('data.0.released_packages_count', 1)
        ->assertJsonPath('data.0.delivered_packages_count', 1)
        ->assertJsonPath('data.0.accepted_packages_count', 1)
        ->assertJsonPath('data.0.warehouse_packages_count', 1)
        ->assertJsonPath('data.0.billing_groups_count', 3)
        ->assertJsonPath('data.0.billed_groups_count', 1)
        ->assertJsonPath('data.0.paid_groups_count', 1)
        ->assertJsonPath('data.0.for_billing_groups_count', 1);
    jarvisRead('deliveries', $token, ['delivery_id' => 2, 'billing_status' => 'paid'])
        ->assertJsonPath('data.0.billing_groups_count', 1);
});

it('matches string receipts exactly including case and trailing spaces', function (string $receiptNumber, int $expectedCount) {
    jarvisOperations();
    DB::table('billing_grouped')->insert(['group_id' => 1, 'dr_no' => '3502-X']);
    DB::table('deliveries')->where('delivery_id', 2)->update(['dr_no' => $receiptNumber]);
    $token = jarvisReader()->createToken('JARVIS', ['jarvis:read'])->plainTextToken;

    jarvisRead('deliveries', $token, ['delivery_id' => 2])
        ->assertOk()->assertJsonPath('data.0.billing_groups_count', $expectedCount);
    jarvisRead('deliveries', $token, ['delivery_id' => 2, 'billing_status' => 'billed'])
        ->assertOk()->assertJsonCount($expectedCount, 'data');
})->with([
    'exact identifier' => ['3502-X', 1],
    'different case' => ['3502-x', 0],
    'trailing space' => ['3502-X ', 0],
]);

it('reports real project progress with package completion and recorded billing stages', function () {
    jarvisOperations();
    $token = jarvisReader()->createToken('JARVIS', ['jarvis:read'])->plainTextToken;

    jarvisRead('projects/delivery-progress', $token, ['active_only' => 0])
        ->assertOk()->assertJsonPath('data.summary.projects_count', 2)
        ->assertJsonPath('data.summary.active_projects_count', 1)
        ->assertJsonPath('data.summary.total_deliveries_count', 3)
        ->assertJsonPath('data.summary.total_packages_count', 5)
        ->assertJsonPath('data.summary.delivery_progress_percent', 40)
        ->assertJsonPath('data.projects.0.total_packages_count', 4)
        ->assertJsonPath('data.projects.0.pending_packages_count', 1)
        ->assertJsonPath('data.projects.0.released_packages_count', 1)
        ->assertJsonPath('data.projects.0.delivered_packages_count', 1)
        ->assertJsonPath('data.projects.0.accepted_packages_count', 1)
        ->assertJsonPath('data.projects.0.mixed_deliveries_count', 2)
        ->assertJsonPath('data.projects.0.completed_deliveries_count', 1)
        ->assertJsonPath('data.projects.0.remaining_deliveries_count', 1)
        ->assertJsonPath('data.projects.0.delivery_progress_percent', 50)
        ->assertJsonPath('data.projects.0.billing_progress_percent', 100)
        ->assertJsonPath('data.projects.0.last_delivery_date', '2026-10-03')
        ->assertJsonPath('data.projects.1.billing_progress_percent', null);
    $this->assertDatabaseCount('package_status', 3);
    $this->assertDatabaseCount('billing_grouped', 1);
});

it('combines progress filters and derives released receipt status from packages', function () {
    jarvisOperations();
    DB::table('package_status')->insert(['delivery_id' => 1, 'package_id' => 2, 'status' => 'released']);
    $token = jarvisReader()->createToken('JARVIS', ['jarvis:read'])->plainTextToken;

    jarvisRead('projects/delivery-progress', $token, ['project_id' => 1, 'year' => 2026, 'region' => 'Region I', 'division' => 'North', 'municipality' => 'Town A', 'delivery_status' => 'released'])
        ->assertOk()->assertJsonCount(1, 'data.projects')
        ->assertJsonPath('data.summary.total_deliveries_count', 1)
        ->assertJsonPath('data.projects.0.released_deliveries_count', 1)
        ->assertJsonPath('data.projects.0.total_packages_count', 2)
        ->assertJsonPath('data.projects.0.released_packages_count', 2)
        ->assertJsonPath('data.projects.0.delivery_progress_percent', 0);
    jarvisRead('projects/delivery-progress', $token, ['region' => 'Region II', 'division' => 'North', 'active_only' => 0])
        ->assertJsonCount(0, 'data.projects')->assertJsonPath('data.summary.delivery_progress_percent', null);
});

it('counts shared DR receipts once and does not multiply billing across allocation paths', function () {
    jarvisOperations();
    DB::table('keystage')->insert(['keystage_id' => 10, 'lot_id' => 1]);
    DB::table('package')->insert(['package_id' => 4, 'keystage_id' => 10, 'lot_id' => 1]);
    DB::table('deliveries')->insert(['delivery_id' => 4, 'project_id' => 1, 'dr_no' => '3502', 'lot_id' => 1, 'keystage_id' => 10, 'status' => 'pending']);
    DB::table('billing_grouped')->insert(['group_id' => 1, 'dr_no' => '3502-X']);
    $token = jarvisReader()->createToken('JARVIS', ['jarvis:read'])->plainTextToken;

    jarvisRead('projects/delivery-progress', $token, ['project_id' => 1])
        ->assertOk()->assertJsonPath('data.projects.0.total_deliveries_count', 2)
        ->assertJsonPath('data.projects.0.delivery_rows_count', 3)
        ->assertJsonPath('data.projects.0.total_packages_count', 7)
        ->assertJsonPath('data.projects.0.pending_packages_count', 4)
        ->assertJsonPath('data.projects.0.billing_groups_count', 2);
});

it('shows neutral package progress without allocations and keeps awarded projects active', function () {
    jarvisOperations();
    DB::table('projects')->where('project_id', 1)->update(['status' => 'Completed']);
    DB::table('deliveries')->where('project_id', 1)->update(['lot_id' => null, 'keystage_id' => null]);
    DB::table('grouping')->insert(['group_id' => 2, 'status' => 'for billing']);
    DB::table('billing_grouped')->insert(['group_id' => 2, 'dr_no' => '3502-X']);
    $token = jarvisReader()->createToken('JARVIS', ['jarvis:read'])->plainTextToken;

    jarvisRead('projects/delivery-progress', $token)
        ->assertOk()->assertJsonCount(1, 'data.projects')
        ->assertJsonPath('data.projects.0.progress_basis', 'no_package_allocations')
        ->assertJsonPath('data.projects.0.total_packages_count', 0)
        ->assertJsonPath('data.projects.0.completed_deliveries_count', 2)
        ->assertJsonPath('data.projects.0.delivery_progress_percent', null)
        ->assertJsonPath('data.projects.0.billing_progress_percent', 50);
});

it('retains projects without deliveries and returns unavailable progress instead of fabricated percentages', function () {
    jarvisOperations();
    DB::table('projects')->insert(['project_id' => 3, 'project_name' => 'New Project', 'status' => 'Pending']);
    $token = jarvisReader()->createToken('JARVIS', ['jarvis:read'])->plainTextToken;

    jarvisRead('projects/delivery-progress', $token, ['project_id' => 3])
        ->assertOk()->assertJsonCount(1, 'data.projects')
        ->assertJsonPath('data.projects.0.total_deliveries_count', 0)
        ->assertJsonPath('data.projects.0.delivery_progress_percent', null);
});

it('uses a fixed number of database queries for project progress regardless of project count', function () {
    jarvisOperations();
    $service = app(ProjectDeliveryProgressService::class);
    DB::enableQueryLog();
    $service->report([]);
    $firstQueryCount = count(DB::getQueryLog());
    DB::disableQueryLog();
    foreach (range(3, 10) as $projectId) {
        DB::table('projects')->insert(['project_id' => $projectId, 'project_name' => 'Project '.$projectId, 'status' => 'Pending']);
    }
    DB::flushQueryLog();
    DB::enableQueryLog();
    $service->report([]);
    expect(count(DB::getQueryLog()))->toBe($firstQueryCount)->toBe(2);
    DB::disableQueryLog();
});

it('counts DR allocations rather than definitions and uses one current status per delivery package pair', function () {
    jarvisOperations();
    DB::table('package')->insert(['package_id' => 4, 'lot_id' => 99]);
    DB::table('package_status')->insert([
        ['package_status_id' => 4, 'delivery_id' => 1, 'package_id' => 1, 'status' => 'accepted'],
        ['package_status_id' => 5, 'delivery_id' => 1, 'package_id' => 3, 'status' => 'delivered'],
    ]);
    $token = jarvisReader()->createToken('JARVIS', ['jarvis:read'])->plainTextToken;

    jarvisRead('projects/delivery-progress', $token, ['project_id' => 1])
        ->assertOk()->assertJsonPath('data.projects.0.total_deliveries_count', 2)
        ->assertJsonPath('data.projects.0.total_package_allocations_count', 4)
        ->assertJsonPath('data.projects.0.total_packages_count', 4)
        ->assertJsonPath('data.projects.0.pending_packages_count', 1)
        ->assertJsonPath('data.projects.0.released_packages_count', 0)
        ->assertJsonPath('data.projects.0.delivered_packages_count', 1)
        ->assertJsonPath('data.projects.0.accepted_packages_count', 2)
        ->assertJsonPath('data.projects.0.completed_packages_count', 3)
        ->assertJsonPath('data.projects.0.remaining_packages_count', 1)
        ->assertJsonPath('data.projects.0.delivery_progress_percent', 75);
    expect(DB::table('package')->where('lot_id', 1)->count())->toBe(2);
});

it('searches progress by project name or reference on the server', function (string $search) {
    jarvisOperations();
    $token = jarvisReader()->createToken('JARVIS', ['jarvis:read'])->plainTextToken;

    jarvisRead('projects/delivery-progress', $token, ['active_only' => 0, 'search' => $search])
        ->assertOk()->assertJsonCount(1, 'data.projects')
        ->assertJsonPath('data.projects.0.project_id', 1)
        ->assertJsonPath('data.summary.total_package_allocations_count', 4);
})->with(['Science', 'REF-001']);

it('sorts project progress using the requested metric', function (string $sort) {
    jarvisOperations();
    DB::table('projects')->where('project_id', 1)->update(['end_date' => '2027-01-01']);
    DB::table('projects')->where('project_id', 2)->update(['end_date' => '2026-01-01']);
    $token = jarvisReader()->createToken('JARVIS', ['jarvis:read'])->plainTextToken;

    jarvisRead('projects/delivery-progress', $token, ['active_only' => 0, 'sort' => $sort, 'direction' => 'desc'])
        ->assertOk()->assertJsonPath('data.projects.0.project_id', 1);
})->with(['project', 'progress', 'total_drs', 'total_dr_packages', 'last_delivery', 'end_date']);

it('rejects invalid progress search and ordering options', function (array $filters, string $field) {
    $token = jarvisReader()->createToken('JARVIS', ['jarvis:read'])->plainTextToken;

    jarvisRead('projects/delivery-progress', $token, $filters)->assertUnprocessable()->assertJsonValidationErrors($field);
})->with([
    [['sort' => 'dr_no'], 'sort'],
    [['direction' => 'sideways'], 'direction'],
    [['search' => str_repeat('x', 256)], 'search'],
]);

it('returns one project row with exact DR counts under strict MariaDB grouping', function () {
    if (getenv('JARVIS_MYSQL_TESTS') !== '1') {
        $this->markTestSkipped('Set JARVIS_MYSQL_TESTS=1 to use connection-local temporary MariaDB fixture tables.');
    }

    jarvisOperations();
    DB::table('projects')->insert(['project_id' => 3, 'project_name' => 'No allocations', 'status' => 'Pending']);
    DB::table('deliveries')->insert([
        ['delivery_id' => 4, 'project_id' => 1, 'school_id' => '001', 'lot_id' => 1, 'dr_no' => '3502', 'status' => 'pending', 'delivery_date' => '2026-10-02'],
        ['delivery_id' => 5, 'project_id' => 1, 'school_id' => '001', 'lot_id' => 1, 'dr_no' => '3502-x', 'status' => 'pending', 'delivery_date' => '2026-10-02'],
        ['delivery_id' => 6, 'project_id' => 1, 'school_id' => '001', 'lot_id' => 1, 'dr_no' => '3502-X ', 'status' => 'pending', 'delivery_date' => '2026-10-02'],
    ]);
    DB::table('billing_grouped')->insert(['dr_no' => '3502-X', 'group_id' => 1]);
    $filters = [
        ['active_only' => 0],
        ['project_id' => 1, 'year' => 2026, 'region' => 'Region I', 'division' => 'North', 'municipality' => 'Town A'],
        ['project_id' => 1, 'delivery_status' => 'mixed'],
        ['project_id' => 1, 'delivery_status' => 'pending'],
        ['active_only' => 0, 'region' => 'Region II'],
        ['active_only' => 0, 'sort' => 'progress', 'direction' => 'desc'],
        ['search' => 'REF-001', 'sort' => 'total_dr_packages'],
    ];
    $service = app(ProjectDeliveryProgressService::class);
    $expectedReports = array_map(fn (array $filter): array => $service->report($filter), $filters);
    $user = jarvisReader();
    $user->setConnection('sqlite');
    $user->load('roles', 'companies');
    $companyId = $user->companies->first()->company_id;
    $token = $user->createToken('JARVIS', ['jarvis:read'])->plainTextToken;
    $mysql = DB::connection('mysql');
    $tables = ['projects', 'deliveries', 'school', 'lot', 'package', 'package_status', 'package_content', 'item', 'inventory', 'inventory_history', 'billing_grouped', 'grouping'];
    $createdTables = [];

    try {
        expect($mysql->selectOne('SELECT @@SESSION.sql_mode as sql_mode')->sql_mode)->toContain('ONLY_FULL_GROUP_BY');
        foreach ($tables as $table) {
            $columns = DB::connection('sqlite')->getSchemaBuilder()->getColumns($table);
            $definitions = array_map(function (array $column) use ($table): string {
                $type = $column['type_name'] === 'integer' ? 'BIGINT' : 'VARCHAR(255)';
                if ($column['name'] === 'dr_no') {
                    $type = 'VARCHAR(100) COLLATE '.($table === 'billing_grouped' ? 'utf8mb4_bin' : 'utf8mb4_general_ci');
                }

                return '`'.$column['name'].'` '.$type.' NULL';
            }, $columns);
            $mysql->statement('CREATE TEMPORARY TABLE `'.$table.'` ('.implode(', ', $definitions).') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci');
            $createdTables[] = $table;
            $rows = DB::connection('sqlite')->table($table)->get()->map(fn (object $row): array => (array) $row)->all();
            if ($rows !== []) {
                $mysql->table($table)->insert($rows);
            }
        }
        DB::setDefaultConnection('mysql');
        $query = $service->recordsQuery(['active_only' => 0]);
        if ($sqlPath = getenv('JARVIS_PROGRESS_SQL_PATH')) {
            file_put_contents($sqlPath, $query->toRawSql().';'.PHP_EOL.$service->warehouseItemsQuery(['active_only' => 0])->toRawSql().';'.PHP_EOL);
        }
        $startedAt = microtime(true);
        $queryRows = $query->get();
        $querySeconds = microtime(true) - $startedAt;
        foreach ($filters as $index => $filter) {
            $actual = $service->report($filter);
            expect($actual['projects']->all())->toBe($expectedReports[$index]['projects']->all());
            expect($actual['summary'])->toBe($expectedReports[$index]['summary']);
        }
        $projects = $service->report(['active_only' => 0])['projects'];
        expect($projects)->toHaveCount(3);
        expect($projects->pluck('project_id')->unique())->toHaveCount(3);
        expect($projects->first()['billing_groups_count'])->toBe(2);
        expect($projects->first()['total_deliveries_count'])->toBe(4);

        Sanctum::usePersonalAccessTokenModel(JarvisReadSqliteToken::class);
        $mysql->flushQueryLog();
        $mysql->enableQueryLog();
        $startedAt = microtime(true);
        $api = jarvisRead('projects/delivery-progress', $token, ['active_only' => 0]);
        $apiSeconds = microtime(true) - $startedAt;
        $apiQueryCount = count($mysql->getQueryLog());
        $mysql->disableQueryLog();
        $api->assertOk()->assertJsonCount(3, 'data.projects');
        jarvisRead('projects/delivery-progress', $token)->assertOk()->assertJsonCount(2, 'data.projects');
        $this->withoutVite();
        $this->actingAs($user)->withSession(['company_id' => $companyId]);
        $mysql->flushQueryLog();
        $mysql->enableQueryLog();
        $startedAt = microtime(true);
        $page = $this->get('/deliveries/monitoring?active_only=0');
        $pageSeconds = microtime(true) - $startedAt;
        $pageQueryCount = count($mysql->getQueryLog());
        $mysql->disableQueryLog();
        $page->assertOk()->assertSee('Project Delivery Monitoring')->assertSee('No allocations');
        if ($htmlPath = getenv('JARVIS_MONITORING_HTML_PATH')) {
            file_put_contents($htmlPath, $page->getContent());
        }
        $this->get('/deliveries/monitoring')->assertOk();
        $dashboard = $this->getJson('/deliveries/monitoring?active_only=0')->assertOk();
        expect($dashboard->json('summary'))->toBe($api->json('data.summary'));
        expect($dashboard->json('projects'))->toBe($api->json('data.projects'));
        fwrite(STDOUT, PHP_EOL.json_encode(['strict_mariadb_fixture' => ['project_rows' => $queryRows->count(), 'query_seconds' => round($querySeconds, 4), 'api_seconds' => round($apiSeconds, 4), 'page_seconds' => round($pageSeconds, 4), 'api_operations_queries' => $apiQueryCount, 'page_operations_queries' => $pageQueryCount]]).PHP_EOL);
    } finally {
        Sanctum::usePersonalAccessTokenModel(PersonalAccessToken::class);
        DB::setDefaultConnection('sqlite');
        foreach ($createdTables as $table) {
            $mysql->statement('DROP TEMPORARY TABLE `'.$table.'`');
        }
    }
});

it('exposes the operational pipeline with compatible denominators and exact billed status', function () {
    jarvisOperations();
    DB::table('grouping')->insert([
        ['group_id' => 2, 'status' => 'for billing'],
        ['group_id' => 3, 'status' => 'paid'],
        ['group_id' => 4, 'status' => 'unknown'],
    ]);
    DB::table('billing_grouped')->insert([
        ['group_id' => 2, 'dr_no' => '3502-X', 'created_at' => '2026-10-05 12:30:00'],
        ['group_id' => 3, 'dr_no' => '3502', 'created_at' => null],
        ['group_id' => 4, 'dr_no' => '3502', 'created_at' => null],
    ]);
    $token = jarvisReader()->createToken('JARVIS', ['jarvis:read'])->plainTextToken;

    jarvisRead('projects/delivery-progress', $token, ['project_id' => 1])
        ->assertOk()->assertJsonPath('data.projects.0.pipeline.dr.total', 2)
        ->assertJsonPath('data.projects.0.pipeline.delivered.completed', 2)
        ->assertJsonPath('data.projects.0.pipeline.delivered.total', 4)
        ->assertJsonPath('data.projects.0.pipeline.delivered.percent', 50)
        ->assertJsonPath('data.projects.0.pipeline.billing.completed', 3)
        ->assertJsonPath('data.projects.0.pipeline.billing.total', 4)
        ->assertJsonPath('data.projects.0.pipeline.billing.percent', 75)
        ->assertJsonPath('data.projects.0.pipeline.billed.completed', 1)
        ->assertJsonPath('data.projects.0.pipeline.billed.percent', 25)
        ->assertJsonPath('data.projects.0.paid_groups_count', 1)
        ->assertJsonPath('data.projects.0.overall_progress', 43.75)
        ->assertJsonPath('data.projects.0.last_activity.type', 'Billing recorded')
        ->assertJsonPath('data.projects.0.last_activity.at', '2026-10-05 12:30:00')
        ->assertJsonCount(1, 'data.projects.0.data_integrity_flags')
        ->assertJsonPath('data.summary.pipeline.billing.percent', 75);
});

it('keeps warehouse inventory and stock transactions separate from package status', function () {
    jarvisOperations();
    DB::table('package_status')->where('delivery_id', 1)->update(['status' => 'warehouse']);
    $token = jarvisReader()->createToken('JARVIS', ['jarvis:read'])->plainTextToken;

    jarvisRead('projects/delivery-progress', $token, ['project_id' => 1])
        ->assertOk()->assertJsonPath('data.projects.0.warehouse_packages_count', 1)
        ->assertJsonPath('data.projects.0.warehouse_readiness.available', 10)
        ->assertJsonPath('data.projects.0.warehouse_readiness.stock_in', 0)
        ->assertJsonPath('data.projects.0.operational_pipeline.stock_out.completed', 0)
        ->assertJsonPath('data.projects.0.operational_pipeline.stock_out.percent', 0)
        ->assertJsonPath('data.projects.0.overall_progress', 62.5);
});

it('reconciles manually encoded stock quantities and current inventory through the item project', function () {
    jarvisOperations();
    DB::table('deliveries')->where('delivery_id', 1)->update(['package_qty' => 10]);
    DB::table('inventory_history')->insert([
        ['inventory_id' => 1, 'item_id' => 1, 'warehouse_id' => 1, 'old_qty' => 10, 'new_qty' => 110, 'change_type' => 'stock_in', 'batch_no' => 'MANUAL', 'changed_at' => '2026-10-04 10:00:00'],
        ['inventory_id' => 1, 'item_id' => 1, 'warehouse_id' => 1, 'old_qty' => 110, 'new_qty' => 85, 'change_type' => 'stock_out', 'batch_no' => 'RELEASE', 'changed_at' => '2026-10-05 10:00:00'],
    ]);
    DB::table('inventory')->where('inventory_id', 1)->update(['qty' => 85]);
    $token = jarvisReader()->createToken('JARVIS', ['jarvis:read'])->plainTextToken;

    jarvisRead('projects/delivery-progress', $token, ['project_id' => 1])
        ->assertOk()->assertJsonPath('data.projects.0.warehouse_readiness.required', 55)
        ->assertJsonPath('data.projects.0.warehouse_readiness.stock_in', 100)
        ->assertJsonPath('data.projects.0.warehouse_readiness.stock_out', 25)
        ->assertJsonPath('data.projects.0.warehouse_readiness.available', 85)
        ->assertJsonPath('data.projects.0.warehouse_readiness.history_balance', 85)
        ->assertJsonPath('data.projects.0.warehouse_readiness.covered', 55)
        ->assertJsonPath('data.projects.0.warehouse_readiness.percent', 100)
        ->assertJsonPath('data.projects.0.operational_pipeline.stock_out.completed', 25)
        ->assertJsonPath('data.projects.0.operational_pipeline.stock_out.total', 55)
        ->assertJsonPath('data.projects.0.operational_pipeline.stock_out.percent', 45.45)
        ->assertJsonPath('data.projects.0.overall_progress', 73.86)
        ->assertJsonPath('data.projects.0.last_activity.type', 'Inventory activity')
        ->assertJsonCount(0, 'data.projects.0.data_integrity_flags');
    jarvisRead('inventory', $token, ['project_id' => 1, 'item_id' => 1])->assertJsonPath('data.0.qty', 85);
});

it('does not let surplus inventory cover a different required item shortage', function () {
    jarvisOperations();
    DB::table('package_content')->insert(['package_id' => 1, 'item_id' => 3, 'qty' => 2]);
    DB::table('item')->insert(['item_id' => 3, 'project_id' => 1, 'item_name' => 'Missing item', 'unit' => 'pcs']);
    DB::table('inventory')->where('inventory_id', 1)->update(['qty' => 100]);
    $token = jarvisReader()->createToken('JARVIS', ['jarvis:read'])->plainTextToken;

    jarvisRead('projects/delivery-progress', $token, ['project_id' => 1])
        ->assertOk()->assertJsonPath('data.projects.0.warehouse_readiness.required', 21)
        ->assertJsonPath('data.projects.0.warehouse_readiness.available', 100)
        ->assertJsonPath('data.projects.0.warehouse_readiness.covered', 15)
        ->assertJsonPath('data.projects.0.warehouse_readiness.percent', 71.43)
        ->assertJsonCount(1, 'data.projects.0.data_integrity_flags');
});

it('keeps different inventory units separate instead of summing incompatible quantities', function () {
    jarvisOperations();
    DB::table('item')->insert(['item_id' => 3, 'project_id' => 1, 'item_name' => 'Consumable', 'unit' => 'kg']);
    DB::table('package_content')->insert(['package_id' => 1, 'item_id' => 3, 'qty' => 2]);
    $token = jarvisReader()->createToken('JARVIS', ['jarvis:read'])->plainTextToken;

    jarvisRead('projects/delivery-progress', $token, ['project_id' => 1])
        ->assertOk()->assertJsonPath('data.projects.0.warehouse_readiness.required', null)
        ->assertJsonPath('data.projects.0.warehouse_readiness.available', null)
        ->assertJsonCount(2, 'data.projects.0.warehouse_readiness.by_unit')
        ->assertJsonPath('data.projects.0.warehouse_readiness.by_unit.0.required', 15)
        ->assertJsonPath('data.projects.0.warehouse_readiness.by_unit.1.required', 6)
        ->assertJsonPath('data.projects.0.overall_progress', null);
});

it('keeps project-wide inventory unchanged by DR filters and suppresses a mixed-scope overall percentage', function () {
    jarvisOperations();
    $token = jarvisReader()->createToken('JARVIS', ['jarvis:read'])->plainTextToken;

    jarvisRead('projects/delivery-progress', $token, ['project_id' => 1, 'delivery_status' => 'mixed', 'year' => 2026])
        ->assertOk()->assertJsonPath('data.projects.0.warehouse_readiness.available', 10)
        ->assertJsonPath('data.projects.0.warehouse_readiness.required', 15)
        ->assertJsonPath('data.projects.0.warehouse_readiness.percent', 66.67)
        ->assertJsonPath('data.projects.0.overall_progress', null)
        ->assertJsonPath('data.summary.overall_progress', null);
});

it('uses recorded DR timestamps for last activity rather than scheduled dates', function () {
    jarvisOperations();
    DB::table('deliveries')->where('delivery_id', 1)->update(['created_at' => '2026-10-06 09:00:00', 'delivery_date' => '2027-01-01']);
    $token = jarvisReader()->createToken('JARVIS', ['jarvis:read'])->plainTextToken;

    jarvisRead('projects/delivery-progress', $token, ['project_id' => 1])
        ->assertOk()->assertJsonPath('data.projects.0.last_activity.type', 'DR recorded')
        ->assertJsonPath('data.projects.0.last_activity.at', '2026-10-06 09:00:00')
        ->assertJsonPath('data.projects.0.last_delivery_date', '2026-10-03');
});

it('keeps empty pipeline denominators unavailable and does not invent activity dates', function () {
    jarvisOperations();
    DB::table('projects')->insert(['project_id' => 3, 'status' => 'Pending']);
    $token = jarvisReader()->createToken('JARVIS', ['jarvis:read'])->plainTextToken;

    jarvisRead('projects/delivery-progress', $token, ['project_id' => 3])
        ->assertOk()->assertJsonPath('data.projects.0.pipeline.dr.total', 0)
        ->assertJsonPath('data.projects.0.pipeline.delivered.percent', null)
        ->assertJsonPath('data.projects.0.pipeline.billing.percent', null)
        ->assertJsonPath('data.projects.0.pipeline.billed.percent', null)
        ->assertJsonPath('data.projects.0.last_activity', null);
    jarvisRead('projects/delivery-progress', $token, ['search' => 'Missing Project'])
        ->assertOk()->assertJsonCount(0, 'data.projects')
        ->assertJsonPath('data.summary.pipeline.delivered.total', 0)
        ->assertJsonPath('data.summary.overall_progress', null);
});

it('serves the Operations page and AJAX filters using the same report as JARVIS', function () {
    jarvisOperations();
    $user = jarvisReader(true, true, false);
    $this->withoutVite();
    $this->actingAs($user)->withSession(['company_id' => $user->companies()->first()->company_id]);

    $this->get('/deliveries/monitoring')->assertOk()->assertSee('Project Delivery Monitoring')
        ->assertSee('Delivery Monitoring')->assertSee('Science Kits')->assertSee('DR Packages')
        ->assertSee('DR Summary')->assertSee('Timeline')->assertSee('Oct 03, 2026')
        ->assertSee('Operational Progress')->assertSee('Warehouse Readiness')->assertSee('Stock Out')->assertSee('Last Activity')
        ->assertDontSee('DR link missing');
    $this->getJson('/deliveries/monitoring?project_id=1')->assertOk()
        ->assertJsonPath('projects.0.delivery_progress_percent', 50)
        ->assertJsonPath('summary.total_packages_count', 4)->assertJsonStructure(['summary_html', 'projects_html']);
    $token = $user->createToken('JARVIS', ['jarvis:read'])->plainTextToken;
    $user->assignRole(Role::findOrCreate('Administrator', 'web'));
    jarvisRead('projects/delivery-progress', $token, ['project_id' => 1])
        ->assertJsonPath('data.projects.0.delivery_progress_percent', 50);
    $dashboard = $this->getJson('/deliveries/monitoring?search=REF-001&sort=progress&direction=desc');
    $api = jarvisRead('projects/delivery-progress', $token, ['search' => 'REF-001', 'sort' => 'progress', 'direction' => 'desc']);
    expect($dashboard->json('summary'))->toBe($api->json('data.summary'));
    expect($dashboard->json('projects'))->toBe($api->json('data.projects'));
});

it('returns 403 on Operations monitoring without the existing role or selected MMC company', function (string $condition) {
    $user = jarvisReader($condition !== 'other company', true, false);
    if ($condition === 'wrong role') {
        $user->syncRoles(Role::findOrCreate('finance', 'web'));
    }
    $this->actingAs($user)->withSession(['company_id' => $condition === 'no selected company' ? null : $user->companies()->first()->company_id]);

    $this->get('/deliveries/monitoring')->assertForbidden();
})->with(['wrong role', 'other company', 'no selected company']);

it('allows administrators with selected MMC access and requires login for Operations monitoring', function () {
    $this->get('/deliveries/monitoring')->assertRedirect('/login');
    jarvisOperations();
    $user = jarvisReader();
    $this->withoutVite();

    $this->actingAs($user)->withSession(['company_id' => $user->companies()->first()->company_id])
        ->get('/deliveries/monitoring')->assertOk();
});

it('returns zero counts for deliveries without matching packages or billing groups', function () {
    jarvisOperations();
    DB::table('deliveries')->where('delivery_id', 3)->update(['lot_id' => null]);
    $token = jarvisReader()->createToken('JARVIS', ['jarvis:read'])->plainTextToken;

    jarvisRead('deliveries', $token, ['delivery_id' => 3])
        ->assertOk()->assertJsonPath('data.0.package_allocations_count', 0)
        ->assertJsonPath('data.0.pending_packages_count', 0)
        ->assertJsonPath('data.0.billing_groups_count', 0);
});

it('paginates deliveries before counting allocations while preserving totals and page counts', function () {
    jarvisOperations();
    $token = jarvisReader()->createToken('JARVIS', ['jarvis:read'])->plainTextToken;

    jarvisRead('deliveries', $token, ['per_page' => 1, 'page' => 2])
        ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.delivery_id', 2)
        ->assertJsonPath('data.0.package_allocations_count', 2)
        ->assertJsonPath('data.0.delivered_packages_count', 1)
        ->assertJsonPath('data.0.accepted_packages_count', 1)
        ->assertJsonPath('meta.pagination.total', 3)->assertJsonPath('meta.pagination.last_page', 3);
    jarvisRead('deliveries', $token, ['per_page' => 1, 'page' => 4])
        ->assertOk()->assertJsonCount(0, 'data')->assertJsonPath('meta.pagination.total', 3);
});

it('refuses to roll back receipt identifiers that cannot round trip through signed integers', function (string $receiptNumber) {
    DB::table('billing_grouped')->insert(['dr_no' => $receiptNumber]);
    $migration = require database_path('migrations/2026_10_03_025127_change_billing_grouped_dr_no_to_varchar.php');

    expect(fn () => $migration->down())->toThrow(RuntimeException::class);
    $this->assertDatabaseHas('billing_grouped', ['dr_no' => $receiptNumber]);
})->with(['3502-X', '03502', '3502 ', '+3502', '2147483648', '-2147483649']);

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
    jarvisRead('deliveries', $token, ['delivery_id' => 1])
        ->assertJsonPath('data.0.package_allocations_count', 1)->assertJsonPath('data.0.pending_packages_count', 1);
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
