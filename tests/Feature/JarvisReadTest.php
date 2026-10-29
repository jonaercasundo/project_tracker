<?php

use App\Models\Company;
use App\Models\User;
use App\Services\MmcProjectIntelligence;
use App\Services\ProjectDeliveryProgressService;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
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

function createJarvisLogisticsTables(): void
{
    Schema::create('logistics_webhook_events', function (Blueprint $table): void {
        $table->id();
        $table->string('integration_id');
        $table->string('external_event_id');
        $table->string('event_type');
        $table->string('dr_reference');
        $table->unsignedInteger('resolved_project_id')->nullable();
        $table->string('resolved_school_id')->nullable();
        $table->dateTime('event_occurred_at');
        $table->string('processing_status');
        $table->string('reconciliation_status');
    });

    Schema::create('logistics_dr_tracking', function (Blueprint $table): void {
        $table->id();
        $table->string('integration_id');
        $table->unsignedInteger('project_id');
        $table->string('school_id');
        $table->string('dr_reference');
        $table->char('school_scope_hash', 64);
        $table->char('dr_reference_hash', 64);
        $table->string('logistics_status');
        $table->string('latest_event_id');
        $table->unsignedInteger('latest_webhook_event_row_id');
        $table->dateTime('event_occurred_at');
        $table->unsignedBigInteger('latest_sequence');
        $table->dateTime('last_synchronized_at');
        $table->string('reconciliation_status');
        $table->timestamps();
        $table->unique(['integration_id', 'project_id', 'school_scope_hash', 'dr_reference_hash']);
    });
}

function insertJarvisLogisticsTracking(int $projectId, string $schoolId, string $drReference, string $status, string $occurredAt, string $integrationId = 'logistics-test'): int
{
    return (int) DB::table('logistics_dr_tracking')->insertGetId([
        'integration_id' => $integrationId,
        'project_id' => $projectId,
        'school_id' => $schoolId,
        'dr_reference' => $drReference,
        'school_scope_hash' => hash('sha256', $schoolId),
        'dr_reference_hash' => hash('sha256', $drReference),
        'logistics_status' => $status,
        'latest_event_id' => 'event-'.$projectId.'-'.$schoolId.'-'.$drReference,
        'latest_webhook_event_row_id' => 1,
        'event_occurred_at' => $occurredAt,
        'latest_sequence' => 1,
        'last_synchronized_at' => $occurredAt,
        'reconciliation_status' => 'synchronized',
        'created_at' => $occurredAt,
        'updated_at' => $occurredAt,
    ]);
}

it('counts completed DRs once regardless of delivery rows packages items and quantities', function () {
    jarvisOperations();
    DB::table('logistics_location')->where('logistics_location_id', 1)->update(['logistics_id' => 7]);
    DB::table('deliveries')->where('project_id', 1)->update(['package_qty' => 251557]);
    DB::table('deliveries')->insert(['delivery_id' => 4, 'project_id' => 1, 'dr_no' => '3502-X', 'lot_id' => 1, 'logistics_location_id' => 1, 'status' => 'accepted']);
    DB::table('item')->insert(['item_id' => 3, 'project_id' => 1, 'unit' => 'pcs']);
    DB::table('package_content')->insert(['package_id' => 1, 'item_id' => 3, 'qty' => 99999]);
    DB::table('package_status')->insert([
        ['delivery_id' => 4, 'package_id' => 1, 'status' => 'delivered'],
        ['delivery_id' => 4, 'package_id' => 2, 'status' => 'released'],
        ['delivery_id' => 4, 'package_id' => 2, 'status' => 'accepted'],
    ]);

    $project = app(ProjectDeliveryProgressService::class)->report(['project_id' => 1])['projects']->first();

    expect($project['operational_pipeline']['delivered'])->toMatchArray(['completed' => 1, 'total' => 2, 'percent' => 50.0, 'unit' => 'Delivery Receipts']);
    expect($project['logistics_delivery_performance']['partners'][0])->toMatchArray(['total_assigned_drs' => 2, 'delivered_drs' => 1, 'delivery_percentage' => 50.0]);
    expect($project['unassigned_logistics_drs'])->toBe(0);
});

it('keeps unassigned DRs in project totals and renders receipt based delivery progress', function () {
    jarvisOperations();
    DB::table('logistics_location')->where('logistics_location_id', 1)->update(['logistics_id' => 7]);
    DB::table('deliveries')->insert(['delivery_id' => 4, 'project_id' => 1, 'dr_no' => 'UNASSIGNED', 'status' => 'delivered']);
    $user = jarvisReader();
    $this->withoutVite();
    $this->actingAs($user)->withSession(['company_id' => $user->companies()->first()->company_id]);

    $page = $this->get('/deliveries/monitoring?project_id=1');

    $page->assertSee('Delivery Receipts')->assertSee('/ 3 DRs')->assertSee('66.7%')
        ->assertSee('Logistics Delivery Performance')->assertSee('1 DRs without logistics partner')->assertDontSee('DR package allocations');
    $project = $page->viewData('report')['projects']->first();
    expect($project['operational_pipeline']['delivered'])->toMatchArray(['completed' => 2, 'total' => 3, 'percent' => 66.67]);
    expect($project['logistics_delivery_performance'])->toMatchArray(['assigned_drs' => 2, 'delivered_assigned_drs' => 1, 'unassigned_drs' => 1, 'delivered_unassigned_drs' => 1]);
    $this->getJson('/deliveries/monitoring/1/details?section=delivered')->assertJsonPath('records.total', 2);
});

it('counts distinct stock-out DRs once when matching package item history exists', function () {
    jarvisOperations();
    DB::table('package_status')->insert([
        ['delivery_id' => 1, 'package_id' => 2, 'status' => 'released'],
    ]);
    DB::table('inventory_history')->insert([
        ['item_id' => 1, 'warehouse_id' => 1, 'old_qty' => 20, 'new_qty' => 18, 'change_type' => 'stock_out', 'batch_no' => 'BATCH-1'],
        ['item_id' => 1, 'warehouse_id' => 1, 'old_qty' => 18, 'new_qty' => 16, 'change_type' => 'stock_out', 'batch_no' => 'BATCH-2'],
    ]);

    $project = app(ProjectDeliveryProgressService::class)->report(['project_id' => 1])['projects']->first();

    expect($project['operational_pipeline']['stock_out'])->toMatchArray([
        'completed' => 1,
        'total' => 2,
        'percent' => 50.0,
        'unit' => 'DRs',
    ]);
});

it('shows zero stock-out DRs and zero percent when no matching stock-out history exists', function () {
    jarvisOperations();
    DB::table('item')->insert(['item_id' => 3, 'item_name' => 'Workbook', 'project_id' => 1, 'unit' => 'boxes']);
    DB::table('package_content')->insert(['package_id' => 1, 'item_id' => 3, 'qty' => 1]);

    $user = jarvisReader();
    $this->withoutVite();
    $this->actingAs($user)->withSession(['company_id' => $user->companies()->first()->company_id]);
    $page = $this->get('/deliveries/monitoring?project_id=1');
    $project = $page->viewData('report')['projects']->first();

    $page->assertSee('2 DRs')->assertSee('0.0%')->assertDontSee('Per item unit');
    expect($project['operational_pipeline']['stock_out'])->toMatchArray([
        'completed' => 0,
        'total' => 2,
        'percent' => 0.0,
    ]);
});

it('excludes invalid DRs even when their package statuses indicate delivery completion', function (string $status) {
    jarvisOperations();
    DB::table('deliveries')->where('delivery_id', 2)->update(['status' => $status]);

    $project = app(ProjectDeliveryProgressService::class)->report(['project_id' => 1])['projects']->first();

    expect($project['operational_pipeline']['delivered'])->toMatchArray(['completed' => 0, 'total' => 1, 'percent' => 0.0]);
    expect($project['logistics_delivery_performance']['unassigned_drs'])->toBe(1);
})->with(['cancelled', 'void', 'voided', 'deleted', 'rejected', 'Cancelled']);

it('uses delivery completion independently of billing status', function (string $billingStatus) {
    jarvisOperations();
    DB::table('grouping')->where('group_id', 1)->update(['status' => $billingStatus]);
    DB::table('billing_grouped')->insert(['group_id' => 1, 'dr_no' => '3502-X']);

    $project = app(ProjectDeliveryProgressService::class)->report(['project_id' => 1])['projects']->first();

    expect($project['operational_pipeline']['delivered'])->toMatchArray(['completed' => 1, 'total' => 2, 'percent' => 50.0]);
})->with(['for billing', 'billed', 'paid']);

it('reconciles partner totals with project totals across logistics locations', function () {
    jarvisOperations();
    DB::table('logistics_location')->where('logistics_location_id', 1)->update(['logistics_id' => 7]);
    DB::table('logistics_location')->insert([
        ['logistics_location_id' => 2, 'logistics_id' => 7],
        ['logistics_location_id' => 3, 'logistics_id' => 8],
    ]);
    DB::table('deliveries')->insert([
        ['delivery_id' => 4, 'project_id' => 1, 'dr_no' => 'OTHER-LOCATION', 'status' => 'accepted', 'logistics_location_id' => 2],
        ['delivery_id' => 5, 'project_id' => 1, 'dr_no' => 'OTHER-PARTNER', 'status' => 'delivered', 'logistics_location_id' => 3],
    ]);

    $project = app(ProjectDeliveryProgressService::class)->report(['project_id' => 1])['projects']->first();

    expect($project['operational_pipeline']['delivered'])->toMatchArray(['completed' => 3, 'total' => 4, 'percent' => 75.0]);
    expect($project['logistics_delivery_performance'])->toMatchArray(['assigned_drs' => 4, 'delivered_assigned_drs' => 3, 'unassigned_drs' => 0]);
    expect($project['logistics_delivery_performance']['partners'][0])->toMatchArray(['logistics_id' => 8, 'total_assigned_drs' => 1, 'delivered_drs' => 1, 'delivery_percentage' => 100.0]);
    expect($project['logistics_delivery_performance']['partners'][1])->toMatchArray(['logistics_id' => 7, 'total_assigned_drs' => 3, 'delivered_drs' => 2, 'delivery_percentage' => 66.67]);
});

it('keeps conflicting partner assignments in the project without counting the DR twice', function () {
    jarvisOperations();
    DB::table('logistics_location')->where('logistics_location_id', 1)->update(['logistics_id' => 7]);
    DB::table('logistics_location')->insert(['logistics_location_id' => 2, 'logistics_id' => 8]);
    DB::table('deliveries')->insert(['delivery_id' => 4, 'project_id' => 1, 'dr_no' => '3502-X', 'status' => 'accepted', 'logistics_location_id' => 2]);

    $project = app(ProjectDeliveryProgressService::class)->report(['project_id' => 1])['projects']->first();

    expect($project['operational_pipeline']['delivered'])->toMatchArray(['completed' => 1, 'total' => 2]);
    expect($project['logistics_delivery_performance'])->toMatchArray(['assigned_drs' => 1, 'delivered_assigned_drs' => 0, 'unassigned_drs' => 1, 'conflicting_drs' => 1]);
});

it('does not count unfinished delivery row statuses as completed when no packages exist', function (string $status) {
    jarvisOperations();
    DB::table('deliveries')->where('project_id', 1)->update(['lot_id' => null, 'status' => $status]);

    $project = app(ProjectDeliveryProgressService::class)->report(['project_id' => 1])['projects']->first();

    expect($project['operational_pipeline']['delivered'])->toMatchArray(['completed' => 0, 'total' => 2, 'percent' => 0.0]);
})->with(['pending', 'released', 'unreleased', 'for approval']);

it('excludes empty DR identifiers from both project and logistics totals', function (?string $identifier) {
    jarvisOperations();
    DB::table('deliveries')->where('delivery_id', 2)->update(['dr_no' => $identifier]);

    $project = app(ProjectDeliveryProgressService::class)->report(['project_id' => 1])['projects']->first();

    expect($project['operational_pipeline']['delivered'])->toMatchArray(['completed' => 0, 'total' => 1]);
    expect($project['logistics_delivery_performance']['unassigned_drs'])->toBe(1);
})->with([null, '', '   ']);

it('renders N/A delivery completion and empty logistics safely when a project has no DRs', function () {
    jarvisOperations();
    DB::table('projects')->insert(['project_id' => 3, 'project_name' => 'No receipts', 'status' => 'Pending']);
    $user = jarvisReader();
    $this->withoutVite();
    $this->actingAs($user)->withSession(['company_id' => $user->companies()->first()->company_id]);

    $page = $this->get('/deliveries/monitoring?project_id=3');

    $page->assertSee('/ 0 DRs')->assertSee('N/A')->assertSee('No assigned logistics partners.');
    expect($page->viewData('report')['projects']->first()['operational_pipeline']['delivered'])->toMatchArray(['completed' => 0, 'total' => 0, 'percent' => null]);
});

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
        ->assertJsonPath('data.summary.delivery_progress_percent', 33.33)
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

it('paginates project progress before aggregation and bounds summary response size', function () {
    jarvisOperations();
    $projects = [];
    $deliveries = [];
    for ($projectId = 3; $projectId <= 62; $projectId++) {
        $projects[] = ['project_id' => $projectId, 'project_name' => 'Progress fixture '.$projectId, 'status' => 'Ongoing'];
        for ($receipt = 0; $receipt < 100; $receipt++) {
            $deliveries[] = ['project_id' => $projectId, 'lot_id' => 1, 'dr_no' => 'P'.$projectId.'-DR'.$receipt, 'status' => 'pending', 'delivery_date' => '2026-10-03'];
        }
    }
    DB::table('projects')->insert($projects);
    foreach (array_chunk($deliveries, 100) as $chunk) {
        DB::table('deliveries')->insert($chunk);
    }
    $token = jarvisReader()->createToken('JARVIS', ['jarvis:read'])->plainTextToken;
    foreach ([['compact' => 0, 'per_page' => 20], ['compact' => 1, 'per_page' => 20], ['compact' => 1, 'project_id' => 62]] as $profileFilters) {
        DB::enableQueryLog();
        DB::flushQueryLog();
        $profileStart = microtime(true);
        $profileResponse = jarvisRead('projects/delivery-progress', $token, $profileFilters)->assertOk();
        $profileSeconds = microtime(true) - $profileStart;
        $profileQueries = DB::getQueryLog();
        DB::disableQueryLog();
        $operationsQueries = array_values(array_filter($profileQueries, fn (array $query): bool => str_contains($query['query'], 'projects')));
        $calculationQueries = array_map(fn (array $query): array => [
            'calculation' => str_contains($query['query'], 'inventory_history') ? 'warehouse/history' : (str_contains($query['query'], 'package_status') ? 'delivery/package/billing aggregates' : 'project selection'),
            'ms' => $query['time'],
        ], $operationsQueries);
        $queryIdentities = array_map(fn (array $query): string => hash('sha256', $query['query'].json_encode($query['bindings'])), $profileQueries);
        fwrite(STDOUT, PHP_EOL.json_encode(['progress_profile_fixture' => [
            'filters' => $profileFilters, 'seconds' => round($profileSeconds, 4), 'bytes' => strlen($profileResponse->getContent()),
            'total_sql_queries' => count($profileQueries), 'operations_queries' => count($operationsQueries),
            'duplicate_sql_queries' => count($queryIdentities) - count(array_unique($queryIdentities)), 'calculations' => $calculationQueries,
        ]]).PHP_EOL);
    }
    $startedAt = microtime(true);
    $page = jarvisRead('projects/delivery-progress', $token, ['compact' => 1])->assertOk()
        ->assertJsonCount(20, 'data.projects')->assertJsonPath('meta.pagination.per_page', 20)
        ->assertJsonPath('meta.pagination.total', 61)->assertJsonPath('meta.pagination.has_more', true)
        ->assertJsonPath('data.summary.projects_count', 20);
    $pageSeconds = microtime(true) - $startedAt;
    $startedAt = microtime(true);
    $single = jarvisRead('projects/delivery-progress', $token, ['project_id' => 62, 'compact' => 1])->assertOk()
        ->assertJsonCount(1, 'data.projects')->assertJsonPath('data.projects.0.project_id', 62)
        ->assertJsonPath('data.projects.0.total_deliveries_count', 100)
        ->assertJsonPath('data.projects.0.total_packages_count', 200);
    $singleSeconds = microtime(true) - $startedAt;
    $maximum = jarvisRead('projects/delivery-progress', $token, ['per_page' => 50, 'compact' => 1])->assertOk()->assertJsonCount(50, 'data.projects');
    expect(strlen($page->getContent()))->toBeLessThan(50000);
    expect(strlen($single->getContent()))->toBeLessThan(50000);
    expect(strlen($maximum->getContent()))->toBeLessThan(50000);
    jarvisRead('projects/delivery-progress', $token, ['page' => 4, 'compact' => 1])->assertOk()->assertJsonCount(1, 'data.projects')
        ->assertJsonPath('data.projects.0.project_id', 62)->assertJsonPath('meta.pagination.has_more', false);
    jarvisRead('projects/delivery-progress', $token, ['page' => 5, 'compact' => 1])->assertOk()->assertJsonCount(0, 'data.projects');

    $service = app(ProjectDeliveryProgressService::class);
    DB::enableQueryLog();
    $metrics = [];
    foreach ([1, 20, 50] as $perPage) {
        DB::flushQueryLog();
        $service->apiReport(['per_page' => $perPage, 'compact' => 1]);
        $queries = DB::getQueryLog();
        expect($queries)->toHaveCount(3);
        $metrics[$perPage] = ['operations_queries' => count($queries), 'slowest_query_ms' => max(array_column($queries, 'time'))];
    }
    DB::disableQueryLog();
    fwrite(STDOUT, PHP_EOL.json_encode(['paginated_progress_sqlite_fixture' => [
        'projects' => 62, 'deliveries' => 6003, 'page_seconds' => round($pageSeconds, 4), 'single_seconds' => round($singleSeconds, 4),
        'page_bytes' => strlen($page->getContent()), 'single_bytes' => strlen($single->getContent()), 'maximum_page_bytes' => strlen($maximum->getContent()),
        'queries' => $metrics,
    ]]).PHP_EOL);
});

it('returns structured progress errors and validates bounded pagination', function () {
    jarvisOperations();
    $token = jarvisReader()->createToken('JARVIS', ['jarvis:read'])->plainTextToken;
    jarvisRead('projects/delivery-progress', $token, ['project_id' => 999999, 'compact' => 1])->assertNotFound()
        ->assertJsonPath('success', false)->assertJsonPath('message', 'Project not found.');
    foreach (['per_page' => 51, 'page' => 0, 'compact' => 'invalid', 'lot_id' => 0] as $field => $value) {
        jarvisRead('projects/delivery-progress', $token, [$field => $value])->assertUnprocessable()->assertJsonValidationErrors($field);
    }
    jarvisRead('projects/delivery-progress', $token, ['status' => 'Delivered', 'active_only' => 0, 'lot_id' => 2])
        ->assertOk()->assertJsonCount(1, 'data.projects')->assertJsonPath('data.projects.0.project_id', 2);
    jarvisRead('projects/delivery-progress', $token, ['status' => 'Delivered', 'active_only' => 0, 'lot_id' => 1])
        ->assertOk()->assertJsonCount(0, 'data.projects');
});

it('returns only AI summary fields and skips warehouse and definition calculations in compact mode', function (array $filters, int $projectCount) {
    jarvisOperations();
    DB::table('projects')->where('project_id', 1)->update(['project_id' => 502698]);
    DB::table('deliveries')->where('project_id', 1)->update(['project_id' => 502698]);
    DB::table('item')->where('project_id', 1)->update(['project_id' => 502698]);
    $token = jarvisReader()->createToken('JARVIS', ['jarvis:read'])->plainTextToken;
    $full = jarvisRead('projects/delivery-progress', $token, ['project_id' => 502698])->assertOk();
    DB::enableQueryLog();
    DB::flushQueryLog();
    $response = jarvisRead('projects/delivery-progress', $token, ['compact' => 1, ...$filters])->assertOk()
        ->assertJsonCount($projectCount, 'data.projects')->assertJsonMissingPath('data.definitions')
        ->assertJsonMissingPath('meta.definitions')->assertJsonMissingPath('data.summary.warehouse_readiness')
        ->assertJsonMissingPath('data.summary.pipeline')->assertJsonMissingPath('data.summary.operational_pipeline');
    $queries = DB::getQueryLog();
    DB::disableQueryLog();
    foreach ($queries as $query) {
        expect($query['query'])->not->toContain('inventory_history')->not->toContain('package_content')->not->toContain('project_lots');
    }
    $fields = ['project_id', 'project_name', 'ref_no', 'project_status', 'start_date', 'end_date',
        'total_deliveries_count', 'completed_deliveries_count', 'pending_deliveries_count', 'total_packages_count',
        'delivered_packages_count', 'accepted_packages_count', 'remaining_packages_count',
        'delivery_progress_percent', 'billing_progress_percent', 'last_activity'];
    sort($fields);
    foreach ($response->json('data.projects') as $project) {
        $keys = array_keys($project);
        sort($keys);
        expect($keys)->toBe($fields);
        if ($project['project_id'] === 502698) {
            foreach ($project as $key => $value) {
                expect($value)->toBe($full->json('data.projects.0.'.$key));
            }
        }
    }
    expect(strlen($response->getContent()))->toBeLessThan(50000);
})->with([
    'compact only' => [[], 1],
    'project identifier' => [['project_id' => 502698], 1],
    'delivery year' => [['year' => 2026], 1],
    'active projects' => [['active_only' => 1], 1],
    'include inactive' => [['active_only' => 0], 2],
    'year with no deliveries' => [['year' => 2025], 0],
]);

it('matches full status rules in compact mode for sparse histories and exact shared receipts', function (array $filters) {
    jarvisOperations();
    DB::table('keystage')->insert(['keystage_id' => 10, 'lot_id' => 1]);
    DB::table('package')->insert(['package_id' => 4, 'keystage_id' => 10, 'lot_id' => 1]);
    DB::table('deliveries')->insert([
        ['delivery_id' => 4, 'project_id' => 1, 'dr_no' => '3502', 'lot_id' => 1, 'keystage_id' => 10, 'status' => 'pending', 'delivery_date' => '2026-10-02'],
        ['delivery_id' => 5, 'project_id' => 1, 'dr_no' => null, 'lot_id' => 1, 'keystage_id' => null, 'status' => 'accepted', 'delivery_date' => '2026-10-02'],
        ['delivery_id' => 6, 'project_id' => 1, 'dr_no' => '3502-x', 'lot_id' => 1, 'keystage_id' => null, 'status' => 'pending', 'delivery_date' => '2026-10-02'],
        ['delivery_id' => 7, 'project_id' => 1, 'dr_no' => '3502-X ', 'lot_id' => 1, 'keystage_id' => null, 'status' => 'pending', 'delivery_date' => '2026-10-02'],
        ['delivery_id' => 8, 'project_id' => 1, 'dr_no' => 'NO-ALLOCATIONS', 'lot_id' => null, 'keystage_id' => null, 'status' => 'accepted', 'delivery_date' => '2026-10-02'],
    ]);
    DB::table('package_status')->insert([
        ['delivery_id' => 1, 'package_id' => 1, 'status' => 'pending'],
        ['delivery_id' => 2, 'package_id' => 2, 'status' => null],
        ['delivery_id' => 4, 'package_id' => 4, 'status' => 'delivered'],
        ['delivery_id' => 4, 'package_id' => 1, 'status' => 'accepted'],
        ['delivery_id' => 6, 'package_id' => 1, 'status' => 'accepted'],
        ['delivery_id' => 6, 'package_id' => 1, 'status' => 'unexpected'],
    ]);
    DB::table('billing_grouped')->insert(['group_id' => 1, 'dr_no' => '3502-X ']);
    $service = app(ProjectDeliveryProgressService::class);
    $full = $service->report($filters);

    $compact = $service->report([...$filters, 'compact' => 1]);

    foreach ($compact['projects'] as $project) {
        $original = $full['projects']->firstWhere('project_id', $project['project_id']);
        foreach ($project as $key => $value) {
            expect($value)->toBe($original[$key]);
        }
    }
    foreach ($compact['summary'] as $key => $value) {
        if ($key !== 'billing_progress_percent') {
            expect($value)->toBe($full['summary'][$key]);
        }
    }
})->with([
    'all projects' => [['active_only' => 0]],
    'one project' => [['project_id' => 1]],
    'year' => [['year' => 2026]],
    'pending receipts' => [['delivery_status' => 'pending']],
    'mixed receipts' => [['delivery_status' => 'mixed']],
    'accepted receipts' => [['delivery_status' => 'accepted']],
    'location' => [['region' => 'Region I']],
    'lot' => [['lot_id' => 1]],
]);

it('preserves full delivery progress when compact is omitted or disabled', function () {
    jarvisOperations();
    $token = jarvisReader()->createToken('JARVIS', ['jarvis:read'])->plainTextToken;
    $expected = json_decode(json_encode(app(ProjectDeliveryProgressService::class)->report(['active_only' => 0])), true);
    foreach ([['active_only' => 0], ['active_only' => 0, 'compact' => 0]] as $filters) {
        $response = jarvisRead('projects/delivery-progress', $token, $filters)->assertOk()
            ->assertJsonCount(2, 'data.projects')->assertJsonMissingPath('meta.pagination');
        expect($response->json('data.projects'))->toBe($expected['projects']);
        expect($response->json('data.summary'))->toBe($expected['summary']);
        expect($response->json('data.definitions'))->toBe($expected['definitions']);
        expect($response->json('meta.definitions'))->toBe($expected['definitions']);
    }
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

it('reports delivery completion without allocations and keeps awarded projects active', function () {
    jarvisOperations();
    DB::table('projects')->where('project_id', 1)->update(['status' => 'Completed']);
    DB::table('deliveries')->where('project_id', 1)->update(['lot_id' => null, 'keystage_id' => null]);
    DB::table('grouping')->insert(['group_id' => 2, 'status' => 'for billing']);
    DB::table('billing_grouped')->insert(['group_id' => 2, 'dr_no' => '3502-X']);
    $token = jarvisReader()->createToken('JARVIS', ['jarvis:read'])->plainTextToken;

    jarvisRead('projects/delivery-progress', $token)
        ->assertOk()->assertJsonCount(1, 'data.projects')
        ->assertJsonPath('data.projects.0.progress_basis', 'delivery_receipts')
        ->assertJsonPath('data.projects.0.total_packages_count', 0)
        ->assertJsonPath('data.projects.0.completed_deliveries_count', 2)
        ->assertJsonPath('data.projects.0.delivery_progress_percent', 100)
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
    expect(count(DB::getQueryLog()))->toBe($firstQueryCount)->toBe(4);
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
        ->assertJsonPath('data.projects.0.delivery_progress_percent', 50);
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
    $tables = ['projects', 'deliveries', 'school', 'lot', 'package', 'package_status', 'package_content', 'item', 'inventory', 'inventory_history', 'billing_grouped', 'grouping', 'logistics_location'];
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
            $warehouseQuery = $service->warehouseSummaryQuery(['project_id' => 502698, 'year' => 2026, 'active_only' => 1]);
            file_put_contents($sqlPath, $service->recordsQuery(['project_id' => 502698, 'year' => 2026, 'active_only' => 1])->toRawSql().';'.PHP_EOL.$warehouseQuery->toRawSql().';'.PHP_EOL);
            file_put_contents($sqlPath.'.explain.json', json_encode([
                'scope' => 'Connection-local fixture tables without operational indexes; not a production index assessment.',
                'projects' => $mysql->select('EXPLAIN '.$query->toSql(), $query->getBindings()),
                'warehouse' => $mysql->select('EXPLAIN '.$warehouseQuery->toSql(), $warehouseQuery->getBindings()),
            ], JSON_PRETTY_PRINT));
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
        $api = jarvisRead('projects/delivery-progress', $token, ['active_only' => 0, 'compact' => 0]);
        $apiSeconds = microtime(true) - $startedAt;
        $apiQueryCount = count($mysql->getQueryLog());
        $mysql->disableQueryLog();
        $api->assertOk()->assertJsonCount(3, 'data.projects');
        foreach ([['compact' => 1, 'active_only' => 0, 'sort' => 'total_drs'], ['compact' => 1, 'project_id' => 1, 'year' => 2026]] as $compactFilters) {
            $compactResponse = jarvisRead('projects/delivery-progress', $token, $compactFilters)->assertOk()
                ->assertJsonMissingPath('meta.definitions')->assertJsonMissingPath('data.summary.warehouse_readiness');
            foreach ($compactResponse->json('data.projects') as $project) {
                $fullProject = collect($api->json('data.projects'))->firstWhere('project_id', $project['project_id']);
                expect($project['total_packages_count'])->toBe($fullProject['total_packages_count']);
                expect($project['delivery_progress_percent'])->toBe($fullProject['delivery_progress_percent']);
            }
        }
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
        foreach (['warehouse' => 1, 'dr' => 4, 'delivered' => 1, 'billing' => 2, 'stock-out' => 0] as $section => $count) {
            $this->getJson('/deliveries/monitoring/1/details?section='.$section)->assertOk()->assertJsonPath('records.total', $count);
        }
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
        ->assertJsonPath('data.projects.0.pipeline.delivered.completed', 1)
        ->assertJsonPath('data.projects.0.pipeline.delivered.total', 2)
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

it('shows distinct DR gaps between delivered billing stages in the monitoring flow', function () {
    jarvisOperations();
    DB::table('inventory_history')->insert([
        ['inventory_id' => 1, 'item_id' => 1, 'warehouse_id' => 1, 'old_qty' => 10, 'new_qty' => 9, 'change_type' => 'stock_out', 'batch_no' => 'DR-3502'],
        ['inventory_id' => 1, 'item_id' => 1, 'warehouse_id' => 1, 'old_qty' => 9, 'new_qty' => 8, 'change_type' => 'stock_out', 'batch_no' => 'DR-3502-REPLAY'],
    ]);
    DB::table('inventory_history')->insert([
        'inventory_id' => null, 'item_id' => 1, 'warehouse_id' => 1, 'old_qty' => null, 'new_qty' => null,
        'change_type' => 'stock_out', 'batch_no' => 'DR-3502-X', 'project_id' => 1, 'delivery_id' => 2,
        'package_id' => 1, 'dr_no' => '3502-X', 'quantity' => 0,
    ]);
    DB::table('delivery_billing_records')->insert(['delivery_id' => 2, 'status' => 'for_billing']);
    $user = jarvisReader();
    $token = $user->createToken('JARVIS', ['jarvis:read'])->plainTextToken;

    jarvisRead('projects/delivery-progress', $token, ['project_id' => 1])
        ->assertOk()
        ->assertJsonPath('data.projects.0.pipeline.stock_out.completed', 2)
        ->assertJsonPath('data.projects.0.pipeline.stock_out.pending_count', 1)
        ->assertJsonPath('data.projects.0.pipeline.stock_out.pending_total', 2)
        ->assertJsonPath('data.projects.0.pipeline.dr.remaining_count', 0)
        ->assertJsonPath('data.projects.0.pipeline.dr.remaining_total', 2)
        ->assertJsonPath('data.projects.0.pipeline.delivered.completed', 1)
        ->assertJsonPath('data.projects.0.pipeline.delivered.pending_count', 0)
        ->assertJsonPath('data.projects.0.pipeline.delivered.pending_total', 1)
        ->assertJsonPath('data.projects.0.pipeline.billing.completed', 1)
        ->assertJsonPath('data.projects.0.pipeline.billing.pending_count', 1)
        ->assertJsonPath('data.projects.0.pipeline.billing.pending_total', 1);

    $this->withoutVite();
    $this->actingAs($user)->withSession(['company_id' => $user->companies()->first()->company_id]);
    $this->get('/deliveries/monitoring?project_id=1')->assertOk()
        ->assertSee('1 ')
        ->assertSee('/ 2 DRs')
        ->assertSee('0 ')
        ->assertSee('/ 1 DRs')
        ->assertSee('/ 2 DRs')
        ->assertDontSee('Pending 1 / 1 DRs')
        ->assertSeeInOrder(['Stock Out', '1 ', 'Delivered', '0 ', 'Billing']);
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
        ->assertJsonPath('data.projects.0.operational_pipeline.stock_out.completed', 1)
        ->assertJsonPath('data.projects.0.operational_pipeline.stock_out.total', 2)
        ->assertJsonPath('data.projects.0.operational_pipeline.stock_out.percent', 50)
        ->assertJsonPath('data.projects.0.overall_progress', 75)
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
        ->assertJsonPath('data.projects.0.operational_pipeline.stock_out.completed', 0)
        ->assertJsonPath('data.projects.0.operational_pipeline.stock_out.total', 2)
        ->assertJsonPath('data.projects.0.overall_progress', 62.5);
});

it('calculates operational progress for a year-filtered report while keeping inventory project-wide', function () {
    jarvisOperations();
    DB::table('delivery_billing_records')->insert(['delivery_id' => 1, 'status' => 'for_billing']);
    $user = jarvisReader();
    $token = $user->createToken('JARVIS', ['jarvis:read'])->plainTextToken;

    jarvisRead('projects/delivery-progress', $token, ['project_id' => 1, 'year' => 2026])
        ->assertOk()->assertJsonPath('data.projects.0.warehouse_readiness.available', 10)
        ->assertJsonPath('data.projects.0.warehouse_readiness.required', 15)
        ->assertJsonPath('data.projects.0.warehouse_readiness.percent', 66.67)
        ->assertJsonPath('data.projects.0.pipeline.dr.total', 2)
        ->assertJsonPath('data.projects.0.overall_progress', 25)
        ->assertJsonPath('data.summary.overall_progress', 25);

    $this->withoutVite();
    $this->actingAs($user)->withSession(['company_id' => $user->companies()->first()->company_id]);
    $this->get('/deliveries/monitoring?project_id=1&year=2026')->assertOk()
        ->assertSee('25.0%')
        ->assertSee('Filter progress')
        ->assertDontSee('Active Projects')
        ->assertDontSee('Warehouse Stock')
        ->assertDontSee('Delivery-level Billed')
        ->assertDontSee('Overall progress is unavailable because');
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

it('serves the Operations page immediately and loads AJAX filters using the same report as JARVIS', function () {
    jarvisOperations();
    $user = jarvisReader(true, true, false);
    $this->withoutVite();
    $this->actingAs($user)->withSession(['company_id' => $user->companies()->first()->company_id]);

    $this->get('/deliveries/monitoring')->assertOk()->assertSee('Project Delivery Monitoring')
        ->assertSee('Delivery Monitoring')->assertSee('Loading operational progress…')
        ->assertSee('id="monitoring-project-count">Loading…', false)
        ->assertSee('id="monitoring-project-trigger"', false)->assertSee('role="listbox"', false)
        ->assertSee('Search projects by name or code...', false)
        ->assertDontSee('data-detail-section')->assertDontSee('deliveryMonitoringLocations')
        ->assertSee('Operational Progress')->assertSee('Stock Out')->assertSee('Last Activity')
        ->assertDontSee('DR link missing');
    $this->get('/deliveries/monitoring?project_id=1')->assertOk()->assertSee('name="project_id" value="1"', false);
    $this->getJson('/deliveries/monitoring?project_id=1')->assertOk()
        ->assertJsonPath('projects.0.delivery_progress_percent', 50)
        ->assertJsonPath('summary.total_packages_count', 4)->assertJsonStructure(['summary_html', 'projects_html']);
    $token = $user->createToken('JARVIS', ['jarvis:read'])->plainTextToken;
    $user->assignRole(Role::findOrCreate('Administrator', 'web'));
    jarvisRead('projects/delivery-progress', $token, ['project_id' => 1])
        ->assertJsonPath('data.projects.0.delivery_progress_percent', 50);
    $dashboard = $this->getJson('/deliveries/monitoring?search=REF-001&sort=progress&direction=desc');
    $api = jarvisRead('projects/delivery-progress', $token, ['search' => 'REF-001', 'sort' => 'progress', 'direction' => 'desc', 'compact' => 0]);
    expect($dashboard->json('summary'))->toBe($api->json('data.summary'));
    expect($dashboard->json('projects.0.delivery_progress_percent'))->toBe($api->json('data.projects.0.delivery_progress_percent'));
    expect($dashboard->json('projects.0.operational_pipeline'))->toBe($api->json('data.projects.0.operational_pipeline'));
    $this->get('/deliveries/monitoring?project_id=2&active_only=1')->assertOk()
        ->assertSee('Loading operational progress…');
});

it('tracks lot billing by active cycle and retains posted cycle history', function () {
    jarvisOperations();
    DB::table('lot')->where('lot_id', 1)->update(['contract_price' => '1000.00']);
    DB::table('lot')->where('lot_id', 2)->update(['contract_price' => '0.00']);
    DB::table('lot')->insert([
        'lot_id' => 3,
        'project_id' => 1,
        'lot_name' => 'Lot 3',
        'contract_no' => 'CONTRACT-3',
        'contract_price' => '229401493.02',
    ]);
    DB::table('deliveries')->insert([
        ['delivery_id' => 4, 'project_id' => 1, 'school_id' => '001', 'lot_id' => 1, 'dr_no' => '3504', 'status' => 'delivered'],
        ['delivery_id' => 5, 'project_id' => 1, 'school_id' => '001', 'lot_id' => 1, 'dr_no' => '3505', 'status' => 'delivered'],
        ['delivery_id' => 6, 'project_id' => 1, 'school_id' => '001', 'lot_id' => 1, 'dr_no' => '3506', 'status' => 'delivered'],
        ['delivery_id' => 7, 'project_id' => 1, 'school_id' => '001', 'lot_id' => 1, 'dr_no' => '3507', 'status' => 'delivered'],
        ['delivery_id' => 8, 'project_id' => 1, 'school_id' => '001', 'lot_id' => 1, 'dr_no' => '3502', 'status' => 'delivered'],
    ]);
    DB::table('billing_batches')->insert([
        ['id' => 1, 'batch_ref' => 'BILL-OLD', 'status' => 'billed', 'confirmed_at' => '2026-01-01 00:00:00', 'created_at' => '2025-12-30 00:00:00'],
        ['id' => 2, 'batch_ref' => 'BILL-ACTIVE', 'status' => 'under_billing_review', 'confirmed_at' => '2026-10-01 00:00:00', 'created_at' => '2026-09-30 00:00:00'],
        ['id' => 3, 'batch_ref' => 'BILL-ZERO-PRICE', 'status' => 'billed', 'confirmed_at' => '2026-10-02 00:00:00', 'created_at' => '2026-10-01 00:00:00'],
    ]);
    DB::table('invoices')->insert([
        ['invoice_id' => 1, 'project_id' => 1, 'invoice_no' => 'INV-OLD', 'amount' => '400.00'],
        ['invoice_id' => 2, 'project_id' => 1, 'invoice_no' => 'INV-ACTIVE-1', 'amount' => '500.00'],
        ['invoice_id' => 3, 'project_id' => 2, 'invoice_no' => 'INV-ZERO', 'amount' => '100.00'],
    ]);
    DB::table('delivery_billing_records')->insert([
        ['delivery_id' => 1, 'status' => 'billed', 'invoice_id' => 1, 'billing_batch_id' => 1, 'billing_amount' => '400.00', 'confirmed_billed_at' => '2026-01-01 00:00:00'],
        ['delivery_id' => 2, 'status' => 'for_billing', 'invoice_id' => 2, 'billing_batch_id' => 2, 'billing_amount' => '300.00', 'confirmed_billed_at' => null],
        ['delivery_id' => 4, 'status' => 'billed', 'invoice_id' => 2, 'billing_batch_id' => 2, 'billing_amount' => '200.00', 'confirmed_billed_at' => '2026-10-03 00:00:00'],
        ['delivery_id' => 3, 'status' => 'billed', 'invoice_id' => 3, 'billing_batch_id' => 3, 'billing_amount' => '100.00', 'confirmed_billed_at' => '2026-10-02 00:00:00'],
        ['delivery_id' => 7, 'status' => 'on_hold', 'invoice_id' => null, 'billing_batch_id' => null, 'billing_amount' => null, 'confirmed_billed_at' => null],
    ]);
    $user = jarvisReader(true, true, false);
    $this->withoutVite();
    $this->actingAs($user)->withSession(['company_id' => $user->companies()->first()->company_id]);

    $billingResponse = $this->getJson('/deliveries/monitoring/1/billing')->assertOk()
        ->assertJsonPath('lot_billing_progress.0.lot_id', 1);
    $activeCycle = $billingResponse->json('lot_billing_progress.0');

    expect($activeCycle['contract_price'])->toBe('1000.00')
        ->and($activeCycle['current_cycle']['amount_billed'])->toBe('200.00')
        ->and($activeCycle['current_cycle']['percent_billed'])->toBe(20)
        ->and($activeCycle['current_cycle']['not_billed_amount'])->toBe('800.00')
        ->and($activeCycle['current_cycle']['not_billed_percent'])->toBe(80)
        ->and($activeCycle['history'])->toHaveCount(1)
        ->and($activeCycle['history'][0]['label'])->toBe('BILL-OLD')
        ->and($activeCycle['history'][0]['amount_billed'])->toBe('400.00')
        ->and($activeCycle['history'][0]['not_billed_amount'])->toBe('0.00')
        ->and($activeCycle['history'][0]['not_billed_percent'])->toBe(0)
        ->and(collect($activeCycle['billing_status_breakdown'])->keyBy('status')->get('billed'))
        ->toMatchArray(['amount' => '1000.00', 'dr_count' => 2])
        ->and(collect($activeCycle['billing_status_breakdown'])->keyBy('status')->get('for_billing'))
        ->toMatchArray(['amount' => '250.00', 'dr_count' => 1])
        ->and(collect($activeCycle['billing_status_breakdown'])->keyBy('status')->get('on_hold'))
        ->toMatchArray(['amount' => '250.00', 'dr_count' => 1])
        ->and(collect($activeCycle['billing_status_breakdown'])->keyBy('status')->get('pending_billing'))
        ->toMatchArray(['amount' => '500.00', 'dr_count' => 2]);

    $monitoringPage = $this->get('/deliveries/monitoring?project_id=1&active_only=0')->assertOk()
        ->assertSee('data-lazy-lot-billing', false)
        ->assertSee(route('deliveries.monitoring.billing', ['project' => 1]), false);

    expect($billingResponse->json('html'))
        ->toContain('Billing by Lot')
        ->toContain('BILL-OLD')
        ->toContain('data-lot-billing-progress="3"')
        ->toContain('lg:grid-cols-3')
        ->toContain('Current Cycle Amount Billed')
        ->toContain('Billing Status Breakdown')
        ->toContain('data-billing-status="billed"')
        ->toContain('&#8369;1,000.00')
        ->toContain('&#8369;229,401,493.02')
        ->toContain('2 DRs')
        ->toContain('20.00%');

    $this->get('/deliveries/monitoring')->assertOk()->assertSee('data-lazy-lot-billing', false);

    DB::table('delivery_billing_records')->where('delivery_id', 7)->delete();
    DB::table('delivery_billing_records')->where('delivery_id', 2)->update([
        'status' => 'billed',
        'billing_amount' => '300.00',
        'confirmed_billed_at' => '2026-10-04 00:00:00',
    ]);
    DB::table('billing_batches')->where('id', 2)->update(['status' => 'billed']);

    $completedCycles = $this->getJson('/deliveries/monitoring/1/billing')->assertOk()
        ->json('lot_billing_progress.0');

    expect($completedCycles['current_cycle']['amount_billed'])->toBe('0.00')
        ->and($completedCycles['current_cycle']['percent_billed'])->toBe(0)
        ->and($completedCycles['current_cycle']['not_billed_amount'])->toBe('0.00')
        ->and($completedCycles['current_cycle']['not_billed_percent'])->toBe(0)
        ->and($completedCycles['history'])->toHaveCount(2)
        ->and($completedCycles['history'][0]['label'])->toBe('BILL-ACTIVE')
        ->and($completedCycles['history'][0]['amount_billed'])->toBe('500.00')
        ->and($completedCycles['history'][0]['percent_billed'])->toBe(50)
        ->and($completedCycles['history'][0]['not_billed_amount'])->toBe('0.00');

    DB::table('billing_batches')->insert([
        ['id' => 4, 'batch_ref' => 'BILL-NEW', 'status' => 'under_billing_review', 'confirmed_at' => '2026-10-05 00:00:00', 'created_at' => '2026-10-05 00:00:00'],
    ]);
    DB::table('delivery_billing_records')->insert([
        ['delivery_id' => 5, 'status' => 'billed', 'invoice_id' => 2, 'billing_batch_id' => 4, 'billing_amount' => '1300.00', 'confirmed_billed_at' => '2026-10-06 00:00:00'],
        ['delivery_id' => 6, 'status' => 'for_billing', 'invoice_id' => 2, 'billing_batch_id' => 4, 'billing_amount' => '900.00', 'confirmed_billed_at' => null],
    ]);

    $newCycle = $this->getJson('/deliveries/monitoring/1/billing')->assertOk()
        ->json('lot_billing_progress.0');

    expect($newCycle['current_cycle']['amount_billed'])->toBe('1300.00')
        ->and($newCycle['current_cycle']['percent_billed'])->toBe(100)
        ->and($newCycle['current_cycle']['not_billed_amount'])->toBe('0.00')
        ->and($newCycle['history'])->toHaveCount(2);

    $zeroPriceLot = $this->getJson('/deliveries/monitoring/2/billing')->assertOk()
        ->json('lot_billing_progress.0');

    expect($zeroPriceLot['contract_price'])->toBe('0.00')
        ->and($zeroPriceLot['history'][0]['percent_billed'])->toBeNull();

    $this->getJson('/deliveries/monitoring/2/billing')
        ->assertOk()
        ->assertJsonPath('lot_billing_progress.0.contract_price', '0.00');
});

it('syncs eligible billing DRs through stock out and delivery exactly once', function () {
    jarvisOperations();
    DB::table('inventory')->where('inventory_id', 1)->update(['qty' => 6]);
    DB::table('delivery_billing_records')->insert([
        ['delivery_id' => 1, 'status' => 'billed', 'billing_amount' => '500.00'],
    ]);
    DB::table('deliveries')->insert([
        ['delivery_id' => 4, 'project_id' => 1, 'school_id' => '001', 'lot_id' => 1, 'dr_no' => 'CANCELLED-DR', 'status' => 'cancelled'],
    ]);
    DB::table('delivery_billing_records')->insert([
        ['delivery_id' => 4, 'status' => 'billed', 'billing_amount' => '100.00'],
    ]);
    $user = jarvisReader(true, true, true);
    $this->actingAs($user)->withSession(['company_id' => $user->companies()->first()->company_id]);

    $this->get('/deliveries/monitoring')->assertOk()
        ->assertSee('Sync Billing')
        ->assertSee('data-sync-billing="1"', false);

    $firstSync = $this->postJson('/deliveries/monitoring/1/sync-billing')
        ->assertOk()
        ->assertJsonPath('synced_dr_count', 1);

    expect(DB::table('inventory')->where('inventory_id', 1)->value('qty'))->toBe(0)
        ->and(DB::table('package_status')->where('delivery_id', 1)->where('package_id', 1)->orderByDesc('package_status_id')->value('status'))->toBe('delivered')
        ->and(DB::table('package_status')->where('delivery_id', 1)->where('package_id', 2)->orderByDesc('package_status_id')->value('status'))->toBe('delivered')
        ->and(DB::table('package_status')->where('delivery_id', 4)->count())->toBe(0)
        ->and(DB::table('inventory_history')->where('project_id', 1)->where('delivery_id', 1)->where('dr_no', '3502')->where('quantity', 6)->value('change_type'))->toBe('stock_out')
        ->and(DB::table('inventory_history')->where('project_id', 1)->where('delivery_id', 1)->where('quantity', 6)->whereNotNull('changed_at')->exists())->toBeTrue();

    $progress = app(ProjectDeliveryProgressService::class)->report(['project_id' => 1, 'active_only' => 0])['projects']->first();
    expect($progress['stock_out_drs_count'])->toBe(1)
        ->and($progress['completed_deliveries_count'])->toBe(2)
        ->and($progress['for_billing_deliveries_count'])->toBe(0)
        ->and($progress['billed_deliveries_count'])->toBe(2);

    $secondSync = $this->postJson('/deliveries/monitoring/1/sync-billing')
        ->assertOk()
        ->assertJsonPath('synced_dr_count', 0)
        ->assertJsonPath('skipped_dr_count', 1);

    expect(DB::table('inventory')->where('inventory_id', 1)->value('qty'))->toBe(0)
        ->and(DB::table('inventory_history')->where('batch_no', 'like', 'BILLING-SYNC-%')->count())->toBe(2)
        ->and(DB::table('package_status')->where('delivery_id', 1)->count())->toBe(4)
        ->and($secondSync->json('message'))->toContain('No eligible billing DRs needed syncing');
});

it('rolls back billing sync when inventory cannot cover a DR', function () {
    jarvisOperations();
    DB::table('inventory')->where('inventory_id', 1)->update(['qty' => 5]);
    DB::table('delivery_billing_records')->insert([
        ['delivery_id' => 1, 'status' => 'for_billing', 'billing_amount' => '500.00'],
    ]);
    $user = jarvisReader(true, true, true);
    $this->actingAs($user)->withSession(['company_id' => $user->companies()->first()->company_id]);

    $failedSync = $this->postJson('/deliveries/monitoring/1/sync-billing')->assertUnprocessable();

    expect($failedSync->json('message'))->toContain('Insufficient inventory')
        ->toContain('No changes were saved.');

    expect(DB::table('inventory')->where('inventory_id', 1)->value('qty'))->toBe(5)
        ->and(DB::table('package_status')->where('delivery_id', 1)->count())->toBe(1)
        ->and(DB::table('inventory_history')->where('batch_no', 'like', 'BILLING-SYNC-%')->count())->toBe(0)
        ->and(DB::table('delivery_history')->count())->toBe(0);
});

it('forbids users outside the delivery monitoring roles from syncing billing', function () {
    jarvisOperations();
    $user = User::factory()->create(['username' => fake()->unique()->userName(), 'role' => 'Guest']);
    $user->assignRole(Role::findOrCreate('Guest', 'web'));
    $company = Company::create(['name' => 'MMC', 'code' => 'MMC', 'is_active' => true]);
    $user->companies()->attach($company);
    $this->actingAs($user)->withSession(['company_id' => $company->company_id]);

    $this->postJson('/deliveries/monitoring/1/sync-billing')->assertForbidden();
});

it('renders long project names in a searchable monitoring filter without losing the selected project', function () {
    jarvisOperations();
    $projectName = '2026 School Nutrition and Learning Materials Distribution Program - Northern Division';
    DB::table('projects')->where('project_id', 1)->update(['project_name' => $projectName]);
    $user = jarvisReader(true, true, false);
    $this->withoutVite();
    $this->actingAs($user)->withSession(['company_id' => $user->companies()->first()->company_id]);

    $this->get('/deliveries/monitoring?project_id=1')->assertOk()
        ->assertSee($projectName)
        ->assertSee('id="monitoring-project-trigger"', false)
        ->assertSee('name="project_id" value="1"', false)
        ->assertSee('role="listbox"', false)
        ->assertSee('whitespace-normal break-words', false);
});

it('shows every project status by default and keeps the optional active filter explicit', function () {
    jarvisOperations();
    DB::table('projects')->where('project_id', 1)->update(['project_name' => 'SME 1', 'status' => 'Completed']);
    DB::table('projects')->where('project_id', 2)->update(['project_name' => 'SME 2']);
    $user = jarvisReader(true, true, false);
    $this->withoutVite();
    $this->actingAs($user)->withSession(['company_id' => $user->companies()->first()->company_id]);

    $defaultPage = $this->get('/deliveries/monitoring')->assertOk()->assertSee('SME 1')->assertSee('SME 2');
    $activePage = $this->get('/deliveries/monitoring?active_only=1')->assertOk()->assertSee('SME 1');
    $defaultReport = $this->getJson('/deliveries/monitoring')->assertOk();
    $activeReport = $this->getJson('/deliveries/monitoring?active_only=1')->assertOk();
    $selectedSme2 = $this->getJson('/deliveries/monitoring?project_id=2&active_only=0&year=2025')->assertOk();

    expect($defaultPage->viewData('filters')['active_only'])->toBe(0)
        ->and(preg_match('/<input(?=[^>]*id="monitoring-active")(?=[^>]*\\schecked(?:\\s|=|>))[^>]*>/s', $defaultPage->getContent()))->toBe(0)
        ->and(preg_match('/<input(?=[^>]*id="monitoring-active")(?=[^>]*\\schecked(?:\\s|=|>))[^>]*>/s', $activePage->getContent()))->toBe(1)
        ->and($defaultPage->viewData('report')['projects']->pluck('project_name')->all())->toContain('SME 2')
        ->and($activePage->viewData('report')['projects']->pluck('project_name')->all())->not->toContain('SME 2')
        ->and($defaultReport->json('projects'))->toHaveCount(2)
        ->and($activeReport->json('projects'))->toHaveCount(1)
        ->and($activeReport->json('projects.0.project_name'))->toBe('SME 1')
        ->and($selectedSme2->json('projects'))->toHaveCount(1)
        ->and($selectedSme2->json('projects.0'))->toMatchArray(['project_name' => 'SME 2', 'project_status' => 'Delivered', 'is_active' => false, 'total_deliveries_count' => 1]);

    $details = app(ProjectDeliveryProgressService::class);
    expect($details->detailRecords(['project_id' => 2, 'active_only' => 0, 'year' => 2025], 'dr', 10, 1)->total())->toBe(1)
        ->and($details->detailRecords(['project_id' => 2, 'active_only' => 0, 'year' => 2025], 'warehouse', 10, 1)->total())->toBe(1)
        ->and($details->detailRecords(['project_id' => 2, 'active_only' => 0, 'year' => 2025], 'billing', 10, 1)->total())->toBe(0);
});

it('keeps an explicitly selected active project when no delivery matches the date or status filter', function () {
    jarvisOperations();
    DB::table('projects')->insert(['project_id' => 3, 'project_name' => 'Implementation without receipts', 'status' => 'Implementation']);
    $service = app(ProjectDeliveryProgressService::class);

    $statusQuery = $service->recordsQuery(['project_id' => 1, 'active_only' => 1, 'delivery_status' => 'cancelled']);
    $statusSql = strtolower($statusQuery->toSql());
    $statusReport = $service->report(['project_id' => 1, 'active_only' => 1, 'delivery_status' => 'cancelled']);
    $yearReport = $service->report(['project_id' => 1, 'active_only' => 1, 'year' => 2025]);
    $locationReport = $service->report(['project_id' => 1, 'active_only' => 1, 'region' => 'Region II']);
    $emptyProjectReport = $service->report(['project_id' => 3, 'active_only' => 1, 'delivery_status' => 'pending']);
    $inactiveIncludedReport = $service->report(['project_id' => 2, 'active_only' => 0, 'delivery_status' => 'accepted']);
    $inactiveExcludedReport = $service->report(['project_id' => 2, 'active_only' => 1]);
    $unselectedStatusReport = $service->report(['active_only' => 0, 'delivery_status' => 'pending']);

    expect($statusReport['projects'])->toHaveCount(1)
        ->and($statusReport['projects']->first())->toMatchArray(['project_id' => 1, 'total_deliveries_count' => 0])
        ->and($yearReport['projects'])->toHaveCount(1)
        ->and($yearReport['projects']->first()['total_deliveries_count'])->toBe(0)
        ->and($locationReport['projects'])->toHaveCount(1)
        ->and($locationReport['projects']->first()['total_deliveries_count'])->toBe(0)
        ->and($emptyProjectReport['projects'])->toHaveCount(1)
        ->and($emptyProjectReport['projects']->first())->toMatchArray(['project_id' => 3, 'progress_basis' => 'no_delivery_receipts'])
        ->and($inactiveIncludedReport['projects'])->toHaveCount(1)
        ->and($inactiveIncludedReport['projects']->first()['project_id'])->toBe(2)
        ->and($unselectedStatusReport['projects']->pluck('project_id')->all())->toBe([2])
        ->and($inactiveExcludedReport['projects'])->toBeEmpty()
        ->and($inactiveExcludedReport['empty_reason'])->toBe('inactive_project')
        ->and(preg_match('/delivery_stats.{0,32}project_id.{0,32}is not null/', $statusSql))->toBe(0)
        ->and($statusQuery->getBindings())->toContain(1)
        ->and($statusQuery->getBindings())->toContain('cancelled');
});

it('classifies active projects consistently across the supported project statuses', function () {
    jarvisOperations();
    DB::table('projects')->insert([
        ['project_id' => 3, 'project_name' => 'Pending Project', 'status' => 'Pending'],
        ['project_id' => 4, 'project_name' => 'Implementation Project', 'status' => 'Implementation'],
        ['project_id' => 5, 'project_name' => 'Completed Project', 'status' => 'Completed'],
        ['project_id' => 6, 'project_name' => 'Unspecified Project', 'status' => null],
        ['project_id' => 7, 'project_name' => 'Whitespace Delivered Project', 'status' => ' delivered '],
        ['project_id' => 8, 'project_name' => 'For Billing Project', 'status' => 'For Billing'],
        ['project_id' => 9, 'project_name' => 'For Collection Project', 'status' => 'For Collection'],
        ['project_id' => 10, 'project_name' => 'Collected Project', 'status' => 'Collected'],
        ['project_id' => 11, 'project_name' => 'Cancelled Project', 'status' => 'Cancelled'],
        ['project_id' => 12, 'project_name' => 'Dropped Project', 'status' => 'Dropped'],
        ['project_id' => 13, 'project_name' => 'Mixed Case Delivered Project', 'status' => 'dElIvErEd'],
    ]);
    $service = app(ProjectDeliveryProgressService::class);

    $activeReport = $service->report(['active_only' => 1]);
    $allProjectsReport = $service->report(['active_only' => 0]);

    expect($activeReport['projects']->pluck('project_id')->all())->toBe([1, 3, 4, 5, 6])
        ->and($activeReport['projects']->pluck('is_active')->unique()->all())->toBe([true])
        ->and($allProjectsReport['projects']->pluck('project_id')->all())->toBe(range(1, 13))
        ->and($allProjectsReport['projects']->where('is_active', false)->pluck('project_id')->all())->toBe([2, 7, 8, 9, 10, 11, 12, 13]);
});

it('shows selected active projects with their operational data', function () {
    jarvisOperations();
    DB::table('projects')->where('project_id', 1)->update(['project_name' => 'Selected active project']);
    $service = app(ProjectDeliveryProgressService::class);

    $activeReport = $service->report(['active_only' => 1, 'project_id' => 1]);
    $allProjectsReport = $service->report(['active_only' => 0, 'project_id' => 1]);
    $project = $activeReport['projects']->first();
    $deliveryDetails = $service->detailRecords(['project_id' => 1, 'active_only' => 1], 'dr', 10, 1);
    $inventoryDetails = $service->detailRecords(['project_id' => 1, 'active_only' => 1], 'warehouse', 10, 1);
    $billingDetails = $service->detailRecords(['project_id' => 1, 'active_only' => 1], 'billing', 10, 1);

    expect($activeReport['projects'])->toHaveCount(1)
        ->and($allProjectsReport['projects'])->toHaveCount(1)
        ->and($project)->toMatchArray(['project_name' => 'Selected active project', 'project_status' => 'Ongoing', 'is_active' => true])
        ->and($project['total_deliveries_count'])->toBe(2)
        ->and($project['total_packages_count'])->toBeGreaterThan(0)
        ->and($project['billing_groups_count'])->toBe(1)
        ->and($project['warehouse_readiness']['required'])->toBeGreaterThan(0)
        ->and($deliveryDetails->total())->toBe(2)
        ->and($inventoryDetails->total())->toBe(1)
        ->and($billingDetails->total())->toBe(1);
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

it('keeps monitoring memory bounded for a project with many inventory items', function () {
    jarvisOperations();
    foreach (['projects', 'deliveries', 'lot', 'item'] as $table) {
        DB::table($table)->where('project_id', 1)->update(['project_id' => 502698]);
    }
    for ($batch = 0; $batch < 6; $batch++) {
        $items = [];
        for ($offset = 0; $offset < 500; $offset++) {
            $items[] = ['item_id' => 100 + $batch * 500 + $offset, 'project_id' => 502698, 'item_name' => 'Detail-only item '.($batch * 500 + $offset), 'unit' => 'pcs'];
        }
        DB::table('item')->insert($items);
    }
    $user = jarvisReader();
    $this->withoutVite();
    $this->actingAs($user)->withSession(['company_id' => $user->companies()->first()->company_id]);
    DB::flushQueryLog();
    DB::enableQueryLog();
    memory_reset_peak_usage();
    $before = memory_get_usage(true);
    $started = microtime(true);
    $page = $this->get('/deliveries/monitoring?year=2026&project_id=502698&active_only=1&direction=asc');
    $seconds = microtime(true) - $started;
    $peak = memory_get_peak_usage(true);
    $queries = count(DB::getQueryLog());
    DB::disableQueryLog();
    $page->assertOk()->assertSee('Science Kits')->assertDontSee('Detail-only item')->assertSee('data-lazy-details')
        ->assertDontSee('data-detail-section')->assertDontSee('deliveryMonitoringLocations');
    expect(strlen($page->getContent()))->toBeLessThan(150000);
    expect($peak)->toBeLessThan(128 * 1048576);
    $viewReport = $page->viewData('report');
    $viewLocations = $page->viewData('locations');
    fwrite(STDOUT, PHP_EOL.json_encode(['monitoring_3001_item_fixture' => ['seconds' => round($seconds, 4), 'usage_before_mb' => $before / 1048576, 'usage_after_mb' => memory_get_usage(true) / 1048576, 'peak_mb' => $peak / 1048576, 'queries' => $queries, 'html_bytes' => strlen($page->getContent()), 'report_json_bytes' => strlen(json_encode($viewReport)), 'project_rows' => $viewReport['projects']->count(), 'locations_json_bytes' => strlen(json_encode($viewLocations)), 'location_rows' => $viewLocations->count()]]).PHP_EOL);
    $token = $user->createToken('JARVIS', ['jarvis:read'])->plainTextToken;
    memory_reset_peak_usage();
    $started = microtime(true);
    jarvisRead('projects/delivery-progress', $token, ['project_id' => 502698, 'year' => 2026])
        ->assertOk()->assertJsonCount(1, 'data.projects')->assertJsonCount(0, 'data.projects.0.warehouse_readiness.items')
        ->assertJsonPath('data.projects.0.warehouse_readiness.required', 15)
        ->assertJsonPath('data.projects.0.warehouse_readiness.covered', 10);
    fwrite(STDOUT, PHP_EOL.json_encode(['project_502698_api_fixture' => ['seconds' => round(microtime(true) - $started, 4), 'peak_mb' => memory_get_peak_usage(true) / 1048576]]).PHP_EOL);
    expect(app(ProjectDeliveryProgressService::class)->warehouseSummaryQuery(['project_id' => 502698])->get())->toHaveCount(1);
    memory_reset_peak_usage();
    $started = microtime(true);
    $this->getJson('/deliveries/monitoring/502698/details?section=warehouse')->assertOk()
        ->assertJsonCount(25, 'records.data')->assertJsonPath('records.per_page', 25)
        ->assertJsonPath('records.total', 3001);
    fwrite(STDOUT, PHP_EOL.json_encode(['project_502698_details_fixture' => ['seconds' => round(microtime(true) - $started, 4), 'peak_mb' => memory_get_peak_usage(true) / 1048576]]).PHP_EOL);
    foreach ([25, 50, 100] as $perPage) {
        $this->getJson('/deliveries/monitoring/502698/details?section=warehouse&per_page='.$perPage.'&page=2')
            ->assertOk()->assertJsonCount($perPage, 'records.data')->assertJsonPath('records.total', 3001)
            ->assertJsonPath('records.current_page', 2)->assertJsonPath('records.per_page', $perPage);
    }
    if ($previewPath = getenv('JARVIS_LAZY_PREVIEW_PATH')) {
        file_put_contents($previewPath.'.html', $page->getContent());
        $responses = [];
        foreach ([25, 50, 100] as $size) {
            foreach ([1, 2] as $detailPage) {
                $responses['warehouse-'.$size.'-'.$detailPage] = $this->getJson('/deliveries/monitoring/502698/details?section=warehouse&per_page='.$size.'&page='.$detailPage)->assertOk()->json();
            }
        }
        $responses['dr-25-1'] = $this->getJson('/deliveries/monitoring/502698/details?section=dr')->assertOk()->json();
        file_put_contents($previewPath.'.json', json_encode($responses));
    }
    memory_reset_peak_usage();
    $started = microtime(true);
    $this->get('/deliveries/monitoring?project_id=2&active_only=0')->assertOk()->assertSee('Furniture');
    fwrite(STDOUT, PHP_EOL.json_encode(['smaller_project_fixture' => ['seconds' => round(microtime(true) - $started, 4), 'peak_mb' => memory_get_peak_usage(true) / 1048576]]).PHP_EOL);
});

it('summarizes twenty thousand delivery receipts without loading detail records', function () {
    jarvisOperations();
    for ($batch = 0; $batch < 40; $batch++) {
        $deliveries = [];
        for ($offset = 0; $offset < 500; $offset++) {
            $deliveries[] = ['delivery_id' => 100 + $batch * 500 + $offset, 'project_id' => 1, 'lot_id' => 1, 'dr_no' => 'SCALE-'.($batch * 500 + $offset), 'delivery_date' => '2026-10-01', 'package_qty' => 1, 'status' => 'pending'];
        }
        DB::table('deliveries')->insert($deliveries);
    }
    memory_reset_peak_usage();
    $started = microtime(true);
    $report = app(ProjectDeliveryProgressService::class)->report(['project_id' => 1, 'year' => 2026]);
    $seconds = microtime(true) - $started;
    $peak = memory_get_peak_usage(true);

    expect($report['projects'])->toHaveCount(1);
    expect($report['projects']->first()['total_deliveries_count'])->toBe(20002);
    expect($report['projects']->first()['total_package_allocations_count'])->toBe(40004);
    expect($report['projects']->first()['warehouse_readiness']['required'])->toBe(100015.0);
    expect($report['projects']->first()['warehouse_readiness']['items'])->toBe([]);
    expect($peak)->toBeLessThan(128 * 1048576);
    fwrite(STDOUT, PHP_EOL.json_encode(['large_delivery_sqlite_fixture' => ['delivery_rows' => 20002, 'allocation_rows' => 40004, 'summary_rows' => 1, 'seconds' => round($seconds, 4), 'peak_mb' => $peak / 1048576]]).PHP_EOL);
});

it('loads filtered monitoring detail sections with exact DR matching and lifetime Stock Out', function () {
    jarvisOperations();
    DB::table('inventory_history')->insert(['item_id' => 1, 'change_type' => 'stock_out', 'old_qty' => 10, 'new_qty' => 7, 'changed_at' => '2025-01-01']);
    $user = jarvisReader();
    $this->actingAs($user)->withSession(['company_id' => $user->companies()->first()->company_id]);

    $this->getJson('/deliveries/monitoring/1/details?section=dr&year=2026')->assertOk()->assertJsonPath('records.total', 2);
    $this->getJson('/deliveries/monitoring/1/details?section=dr&delivery_status=mixed')->assertOk()->assertJsonPath('records.total', 2);
    $this->getJson('/deliveries/monitoring/1/details?section=dr&region=Region%20II')->assertOk()->assertJsonPath('records.total', 0);
    $this->getJson('/deliveries/monitoring/1/details?section=delivered&year=2026')->assertOk()->assertJsonPath('records.total', 1);
    $this->getJson('/deliveries/monitoring/1/details?section=delivered&delivery_status=pending')->assertOk()->assertJsonPath('records.total', 0);
    $this->getJson('/deliveries/monitoring/1/details?section=billing')->assertOk()->assertJsonPath('records.total', 1)->assertJsonPath('records.data.0.dr_no', '3502');
    $this->getJson('/deliveries/monitoring/1/details?section=stock-out&year=2026')->assertOk()->assertJsonPath('records.total', 1)->assertJsonPath('records.data.0.stock_out', 3);
    $this->getJson('/deliveries/monitoring/2/details?section=warehouse&active_only=0&project_id=1')->assertOk()->assertJsonPath('records.total', 1)->assertJsonPath('records.data.0.item_name', 'Desk');
    $this->getJson('/deliveries/monitoring/999/details?section=warehouse')->assertNotFound();
    $this->getJson('/deliveries/monitoring/2/details?section=warehouse')->assertNotFound();
});

it('loads dependent monitoring location choices without embedding the location hierarchy', function () {
    jarvisOperations();
    DB::table('school')->insert([
        ['school_id' => '003', 'region' => 'Region I', 'division' => 'East', 'municipality' => 'Town C'],
        ['school_id' => '004', 'region' => 'Region I', 'division' => 'North', 'municipality' => 'Town A'],
    ]);
    $user = jarvisReader();
    $this->actingAs($user)->withSession(['company_id' => $user->companies()->first()->company_id]);

    $this->getJson('/deliveries/monitoring/locations?level=region')->assertOk()->assertExactJson(['options' => ['Region I', 'Region II']]);
    $this->getJson('/deliveries/monitoring/locations?level=division&region=Region%20I')->assertOk()->assertExactJson(['options' => ['East', 'North']]);
    $this->getJson('/deliveries/monitoring/locations?level=municipality&region=Region%20I&division=North')->assertOk()->assertExactJson(['options' => ['Town A']]);
    $this->getJson('/deliveries/monitoring/locations?level=division')->assertOk()->assertExactJson(['options' => ['East', 'North', 'South']]);
    $this->getJson('/deliveries/monitoring/locations?level=municipality&region=Missing')->assertOk()->assertExactJson(['options' => []]);
    $this->getJson('/deliveries/monitoring/locations?level=invalid')->assertUnprocessable()->assertJsonValidationErrors('level');
    $this->getJson('/deliveries/monitoring/locations?level=division&region='.str_repeat('x', 256))->assertUnprocessable()->assertJsonValidationErrors('region');
});

it('keeps the initial location payload small with forty three thousand school location tuples', function () {
    jarvisOperations();
    for ($batch = 0; $batch < 86; $batch++) {
        $schools = [];
        for ($offset = 0; $offset < 500; $offset++) {
            $schools[] = ['school_id' => 'SCALE-'.($batch * 500 + $offset), 'region' => 'Region I', 'division' => 'North', 'municipality' => 'Town '.($batch * 500 + $offset)];
        }
        DB::table('school')->insert($schools);
    }
    $oldLocations = DB::table('school')->select(['region', 'division', 'municipality'])->distinct()->get();
    $oldSize = strlen(json_encode($oldLocations));
    $oldCount = $oldLocations->count();
    unset($oldLocations);
    $user = jarvisReader();
    $this->withoutVite();
    $this->actingAs($user)->withSession(['company_id' => $user->companies()->first()->company_id]);
    memory_reset_peak_usage();
    $started = microtime(true);
    $page = $this->get('/deliveries/monitoring?project_id=1');
    $seconds = microtime(true) - $started;
    $peak = memory_get_peak_usage(true);
    $size = strlen(json_encode($page->viewData('locations')));

    $page->assertOk()->assertDontSee('SCALE-')->assertDontSee('Town 42999')->assertDontSee('deliveryMonitoringLocations');
    expect($page->viewData('locations')->all())->toBe(['Region I', 'Region II']);
    expect($size)->toBeLessThan(100);
    expect($peak)->toBeLessThan(128 * 1048576);
    fwrite(STDOUT, PHP_EOL.json_encode(['location_scale_fixture' => ['previous_location_rows' => $oldCount, 'previous_json_bytes' => $oldSize, 'initial_location_rows' => 2, 'initial_json_bytes' => $size, 'page_seconds' => round($seconds, 4), 'peak_mb' => $peak / 1048576]]).PHP_EOL);
});

it('rejects invalid detail section and pagination inputs', function (array $parameters, string $field) {
    $user = jarvisReader();
    $this->actingAs($user)->withSession(['company_id' => $user->companies()->first()->company_id]);

    $this->getJson('/deliveries/monitoring/1/details?'.http_build_query($parameters))->assertUnprocessable()->assertJsonValidationErrors($field);
})->with([
    [[], 'section'],
    [['section' => 'unknown'], 'section'],
    [['section' => 'dr', 'per_page' => 10000], 'per_page'],
    [['section' => 'dr', 'page' => 0], 'page'],
]);

it('protects lazy monitoring details with the same login company and role boundary', function (string $condition) {
    if ($condition === 'guest') {
        $this->get('/deliveries/monitoring/1/details?section=dr')->assertRedirect('/login');
        $this->get('/deliveries/monitoring/locations?level=region')->assertRedirect('/login');

        return;
    }
    $user = jarvisReader($condition !== 'other company', true, false);
    if ($condition === 'wrong role') {
        $user->syncRoles(Role::findOrCreate('finance', 'web'));
    }
    $this->actingAs($user)->withSession(['company_id' => $condition === 'no selected company' ? null : $user->companies()->first()->company_id]);

    $this->getJson('/deliveries/monitoring/1/details?section=dr')->assertForbidden();
    $this->getJson('/deliveries/monitoring/locations?level=region')->assertForbidden();
})->with(['guest', 'wrong role', 'other company', 'no selected company']);

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

it('shows each repeated DR as its own project and school Logistics scope', function () {
    jarvisOperations();
    createJarvisLogisticsTables();
    DB::table('deliveries')->insert([
        'delivery_id' => 4,
        'project_id' => 2,
        'school_id' => '002',
        'lot_id' => 2,
        'dr_no' => '3502',
        'status' => 'pending',
        'delivery_date' => '2026-10-04',
        'package_qty' => 1,
    ]);
    DB::table('school')->insert(['school_id' => '003', 'school_name' => 'West School']);
    DB::table('deliveries')->insert([
        'delivery_id' => 5,
        'project_id' => 1,
        'school_id' => '003',
        'lot_id' => 1,
        'dr_no' => '3502',
        'status' => 'pending',
        'delivery_date' => '2026-10-04',
        'package_qty' => 1,
    ]);
    insertJarvisLogisticsTracking(1, '001', '3502', 'logistics_received', '2026-10-13 08:00:00');
    insertJarvisLogisticsTracking(2, '002', '3502', 'delivered', '2026-10-13 09:00:00');
    insertJarvisLogisticsTracking(1, '003', '3502', 'logistics_received', '2026-10-13 07:30:00');
    DB::table('logistics_webhook_events')->insert([
        ['integration_id' => 'logistics-test', 'external_event_id' => 'review-1', 'event_type' => 'delivery.delivered', 'dr_reference' => '3502', 'resolved_project_id' => 1, 'resolved_school_id' => '001', 'event_occurred_at' => '2026-10-13 08:00:00', 'processing_status' => 'processed', 'reconciliation_status' => 'ambiguous'],
        ['integration_id' => 'logistics-test', 'external_event_id' => 'review-2', 'event_type' => 'delivery.delivered', 'dr_reference' => '3502', 'resolved_project_id' => 1, 'resolved_school_id' => '001', 'event_occurred_at' => '2026-10-13 08:01:00', 'processing_status' => 'processed', 'reconciliation_status' => 'ambiguous'],
    ]);
    $user = jarvisReader();
    $this->actingAs($user)->withSession(['company_id' => $user->companies()->first()->company_id]);

    $response = $this->getJson('/deliveries/monitoring/logistics');

    $response->assertOk()
        ->assertJsonPath('pagination.total', 3)
        ->assertJsonPath('summary.logistics_received', 2)
        ->assertJsonPath('summary.logistics_delivered', 1)
        ->assertJsonPath('summary.pending_updates', 2)
        ->assertJsonPath('summary.reconciliation_required', 1);
    expect($response->json('html'))->toContain('Distinct verified DR scopes')
        ->and($response->json('html'))->not->toContain('DR# / integration')
        ->and($response->json('html'))->not->toContain('3502')
        ->and($response->json('html'))->not->toContain('Science Kits')
        ->and($response->json('html'))->not->toContain('West School');

    $this->getJson('/deliveries/monitoring/logistics?summary_only=1')
        ->assertOk()->assertJsonPath('summary.total_scoped_drs', 5)
        ->assertJsonMissingPath('pagination');
});

it('filters and paginates the Logistics section on the server', function () {
    jarvisOperations();
    createJarvisLogisticsTables();
    insertJarvisLogisticsTracking(1, '001', '3502', 'delivered', '2026-10-13 09:00:00');
    insertJarvisLogisticsTracking(1, '001', '3502-X', 'delivered', '2026-10-13 08:00:00');
    insertJarvisLogisticsTracking(2, '002', '3503', 'logistics_received', '2026-10-13 07:00:00');
    for ($index = 0; $index < 9; $index++) {
        insertJarvisLogisticsTracking(1, '001', 'PAG-'.str_pad((string) $index, 2, '0', STR_PAD_LEFT), 'delivered', '2026-10-12 08:00:00');
    }
    $user = jarvisReader();
    $this->actingAs($user)->withSession(['company_id' => $user->companies()->first()->company_id]);

    $this->getJson('/deliveries/monitoring/logistics?status=delivered&per_page=10&page=2')
        ->assertOk()
        ->assertJsonPath('pagination.total', 11)
        ->assertJsonPath('pagination.current_page', 2)
        ->assertJsonPath('pagination.per_page', 10)
        ->assertJsonPath('summary.logistics_delivered', 11)
        ->assertJsonPath('summary.pending_updates', 0);

    $this->getJson('/deliveries/monitoring/logistics?project_id=1&dr_reference=3502&school=Central&date_from=2026-10-13&date_to=2026-10-13')
        ->assertOk()
        ->assertJsonPath('pagination.total', 1)
        ->assertJsonPath('summary.logistics_delivered', 1);
});

it('shows a changed Logistics status on the next section refresh', function () {
    jarvisOperations();
    createJarvisLogisticsTables();
    $trackingId = insertJarvisLogisticsTracking(1, '001', '3502', 'logistics_received', '2026-10-13 08:00:00');
    $user = jarvisReader();
    $this->actingAs($user)->withSession(['company_id' => $user->companies()->first()->company_id]);

    $this->getJson('/deliveries/monitoring/logistics?project_id=1&dr_reference=3502')
        ->assertOk()->assertJsonPath('summary.logistics_received', 1);
    DB::table('logistics_dr_tracking')->where('id', $trackingId)->update([
        'logistics_status' => 'delivered',
        'event_occurred_at' => '2026-10-13 09:00:00',
    ]);

    $this->getJson('/deliveries/monitoring/logistics?project_id=1&dr_reference=3502')
        ->assertOk()
        ->assertJsonPath('summary.logistics_delivered', 1)
        ->assertJsonPath('summary.logistics_received', 0)
        ->assertDontSee('DR# / integration');
});

it('does not invent a Logistics status when a DR has no webhook tracking row', function () {
    jarvisOperations();
    $user = jarvisReader();
    $this->withoutVite();
    $this->actingAs($user)->withSession(['company_id' => $user->companies()->first()->company_id]);

    $this->get('/deliveries/monitoring')
        ->assertOk()
        ->assertSee('Pending Logistics updates')
        ->assertDontSee('DR# / integration')
        ->assertDontSee('No Logistics events match these filters')
        ->assertDontSee('Logistics Received</span>');
});

it('protects the Logistics feed with the same MMC company and dashboard role boundary', function (string $condition) {
    if ($condition === 'guest') {
        $this->get('/deliveries/monitoring/logistics')->assertRedirect('/login');

        return;
    }
    $user = jarvisReader($condition !== 'other company', true, false);
    if ($condition === 'wrong role') {
        $user->syncRoles(Role::findOrCreate('finance', 'web'));
    }
    $this->actingAs($user)->withSession(['company_id' => $condition === 'no selected company' ? null : $user->companies()->first()->company_id]);

    $this->getJson('/deliveries/monitoring/logistics')->assertForbidden();
})->with(['guest', 'wrong role', 'other company', 'no selected company']);

it('resolves formatted project codes to the canonical project record', function () {
    DB::table('projects')->insert(['project_id' => 3, 'project_name' => '(SME2) Mass Production, Supply, Delivery', 'project_code' => 'SME2', 'ref_no' => 'REF-SME2']);
    $intelligence = app(MmcProjectIntelligence::class);

    foreach (['SME 2', 'SME-2', 'sme2', '(SME2)'] as $alias) {
        $resolved = $intelligence->resolve($alias);
        expect($resolved['status'])->toBe('found')
            ->and($resolved['project']['project_id'])->toBe(3)
            ->and($resolved['project']['project_code'])->toBe('SME2');
    }
});

it('uses linked bidding codes but returns the implementation project id', function () {
    Schema::table('projects', function (Blueprint $table): void {
        $table->unsignedInteger('project_information_id')->nullable();
    });
    Schema::create('project_information', function (Blueprint $table): void {
        $table->increments('id');
        $table->unsignedInteger('project_id')->nullable();
        $table->string('project_code')->nullable();
        $table->string('reference_no')->nullable();
        $table->string('project_name')->nullable();
    });
    DB::table('projects')->insert(['project_id' => 3, 'project_name' => 'Mass Production Award', 'project_code' => null, 'ref_no' => null, 'project_information_id' => 22]);
    DB::table('project_information')->insert(['id' => 22, 'project_id' => 3, 'project_code' => 'BPC', 'project_name' => 'BPC School Project']);

    $resolved = app(MmcProjectIntelligence::class)->resolve('bpc');

    expect($resolved['status'])->toBe('found')
        ->and($resolved['project']['project_id'])->toBe(3)
        ->and($resolved['project']['bidding_id'])->toBe(22)
        ->and($resolved['project']['project_id'])->not->toBe($resolved['project']['bidding_id']);
});

it('returns ambiguous results instead of selecting between equally matching projects', function () {
    DB::table('projects')->insert([
        ['project_id' => 3, 'project_name' => 'SME2 Project One', 'project_code' => 'SME2'],
        ['project_id' => 4, 'project_name' => 'SME2 Project Two', 'project_code' => 'SME2'],
    ]);

    $resolved = app(MmcProjectIntelligence::class)->resolve('SME 2');

    expect($resolved['status'])->toBe('ambiguous')
        ->and($resolved['candidates'])->toHaveCount(2);
});

it('prioritizes an exact canonical project code over a similar title alias', function () {
    DB::table('projects')->insert([
        ['project_id' => 3, 'project_name' => '(SME2) historical reference', 'project_code' => 'OLD'],
        ['project_id' => 4, 'project_name' => 'Current project', 'project_code' => 'SME2'],
    ]);

    $resolved = app(MmcProjectIntelligence::class)->resolve('SME-2');

    expect($resolved['status'])->toBe('found')
        ->and($resolved['project']['project_id'])->toBe(4)
        ->and($resolved['project']['match_method'])->toBe('project_code');
});

it('returns not found when no authorized project matches the supplied reference', function () {
    $resolved = app(MmcProjectIntelligence::class)->resolve('NO-SUCH-PROJECT');

    expect($resolved['status'])->toBe('not_found')
        ->and($resolved['candidates'])->toBeEmpty();
});

it('uses dashboard receipt completion rules for project delivery analytics', function () {
    jarvisOperations();

    $result = app(MmcProjectIntelligence::class)->deliverySummary('SCI');

    expect($result['status'])->toBe('found')
        ->and($result['project']['project_id'])->toBe(1)
        ->and($result['period'])->toBe('all time')
        ->and($result['counting_unit'])->toContain('project + DR number')
        ->and($result['delivery_rows_count'])->toBe(2)
        ->and($result['total_receipts_count'])->toBe(2)
        ->and($result['delivered_receipts_count'])->toBe(1)
        ->and($result['unique_schools_with_delivered_or_accepted_rows'])->toBe(1);
});

it('distinguishes a project with no matching delivery rows and supports delivery date filters', function () {
    jarvisOperations();
    DB::table('projects')->insert(['project_id' => 3, 'project_name' => 'No Deliveries', 'project_code' => 'EMPTY']);

    $emptyProject = app(MmcProjectIntelligence::class)->deliverySummary('EMPTY');
    $filtered = app(MmcProjectIntelligence::class)->deliverySummary('SCI', null, ['date_from' => '2026-10-02', 'date_to' => '2026-10-02']);

    expect($emptyProject['status'])->toBe('found')
        ->and($emptyProject['has_delivery_records'])->toBeFalse()
        ->and($emptyProject['delivered_receipts_count'])->toBe(0)
        ->and($filtered['period'])->toBe(['date_from' => '2026-10-02', 'date_to' => '2026-10-02'])
        ->and($filtered['delivery_rows_count'])->toBe(1);
});

it('respects company ownership when the MMC project schema carries a company id', function () {
    Schema::table('projects', function (Blueprint $table): void {
        $table->unsignedInteger('company_id')->nullable();
    });
    DB::table('projects')->insert([
        ['project_id' => 3, 'project_name' => 'Authorized project', 'project_code' => 'SHARED', 'company_id' => 11],
        ['project_id' => 4, 'project_name' => 'Other company project', 'project_code' => 'SHARED', 'company_id' => 22],
    ]);

    $resolved = app(MmcProjectIntelligence::class)->resolve('SHARED', 11);

    expect($resolved['status'])->toBe('found')
        ->and($resolved['project']['project_id'])->toBe(3)
        ->and($resolved['project']['company_id'])->toBe(11);
});

it('reports delivery analytics as unavailable when the operational calculation fails', function () {
    DB::table('projects')->insert(['project_id' => 3, 'project_name' => 'Failure fixture', 'project_code' => 'FAIL']);
    $progress = Mockery::mock(ProjectDeliveryProgressService::class);
    $progress->shouldReceive('deliveryMetrics')->once()->andThrow(new RuntimeException('database unavailable'));
    app()->instance(ProjectDeliveryProgressService::class, $progress);

    $result = app(MmcProjectIntelligence::class)->deliverySummary('FAIL');

    expect($result['status'])->toBe('data_unavailable')
        ->and($result)->not->toHaveKey('exception');
});
