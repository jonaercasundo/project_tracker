<?php

use App\Console\Commands\SyncMmcPlatformMasterlist;
use App\Jobs\SyncMmcPlatformProject;
use App\Services\BillingDocumentStatusService;
use App\Services\MmcPlatformApiClient;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\MIWorkflowTestCase;

class MmcPlatformIntegrationTestCase extends MIWorkflowTestCase
{
    protected function migrateFreshUsing(): array
    {
        return ['--path' => [
            'database/migrations/0001_01_01_000000_create_users_table.php',
            'database/migrations/2026_06_15_081615_create_permission_tables.php',
            'database/migrations/2026_09_02_024221_create_companies_table.php',
            'database/migrations/2026_09_02_024448_create_company_user_table.php',
            'tests/Fixtures/migrations/2026_10_16_000001_create_legacy_projects_table.php',
            'database/migrations/2026_10_26_115437_add_description_to_roles_and_create_admin_activity_logs_table.php',
            'database/migrations/2026_11_06_054151_create_mmc_platform_settings_table.php',
            'database/migrations/2026_11_11_095147_create_mmc_platform_project_syncs_table.php',
            'database/migrations/2026_11_26_053259_create_mmc_platform_project_sync_pages_table.php',
            'database/migrations/2026_11_26_062033_add_tracker_project_id_to_mmc_platform_project_syncs_table.php',
            'database/migrations/2026_11_26_064055_create_mmc_platform_masterlist_records_table.php',
        ]];
    }
}

pest()->extend(MmcPlatformIntegrationTestCase::class)->in('MmcPlatformIntegrationTest.php');

function mmcPlatformUser(): void
{
    $user = test()->miUser('user', 'MMC');
    test()->signInMI($user);
}

function mmcTrackerProject(?int $companyId = null): int
{
    return (int) DB::table('projects')->insertGetId([
        'company_id' => $companyId ?? auth()->user()->currentCompany()->getKey(),
        'project_code' => 'MMC-TEST',
        'ref_no' => 'MMC-TEST-'.uniqid(),
        'project_name' => 'Local Tracker Project',
        'agency' => 'Department of Education',
        'contract_amount' => 0,
        'status' => 'Active',
        'ABC' => 0,
    ]);
}

it('shows the configuration screen in the MMC user workspace only', function () {
    mmcPlatformUser();
    mmcTrackerProject();
    $this->get(route('integrations.mmc-platform.index', ['tab' => 'integration']))
        ->assertOk()
        ->assertSee('Third Party API Key Management')
        ->assertSee('Operations / Integrations')
        ->assertSee('API Base URL')
        ->assertSee('Read-only API Key')
        ->assertSee('Test Connection')
        ->assertSee('Save Configuration')
        ->assertSee('showApiKey')
        ->assertDontSee('mmc_test_read_key');

    $this->get(route('integrations.mmc-platform.index', ['tab' => 'api-webhooks']))
        ->assertOk()
        ->assertSee('Data Preview')
        ->assertDontSee('data-live-update-status', false)
        ->assertDontSee('$refs.testConnection.click()', false)
        ->assertSee('HTTP status · requested endpoint')
        ->assertSee('max="2000"', false);
    $this->get(route('integrations.mmc-platform.index', ['tab' => 'api-webhooks']))
        ->assertOk()
        ->assertSee('Local Tracker Project');

    $administrator = $this->miUser('Administrator', 'MMC');
    $this->actingAs($administrator)->withSession(['company_id' => $administrator->companies()->sole()->getKey()]);
    $this->get(route('integrations.mmc-platform.index'))->assertForbidden();

    $miMember = $this->miUser('user', 'MI');
    $this->actingAs($miMember)->withSession(['company_id' => $miMember->companies()->sole()->getKey()]);
    $this->get(route('integrations.mmc-platform.index'))->assertForbidden();
});

it('limits connection checks without blocking the preview endpoint', function () {
    Http::fake(['*' => Http::response(['data' => []])]);
    mmcPlatformUser();

    $connectionCheck = route('integrations.mmc-platform.connection-check');
    for ($attempt = 0; $attempt < 5; $attempt++) {
        $this->getJson($connectionCheck)->assertOk();
    }

    $this->getJson($connectionCheck)->assertTooManyRequests();
    $this->postJson(route('integrations.mmc-platform.preview-data'))
        ->assertUnprocessable()
        ->assertJsonValidationErrors('program_id');
});

it('requires a Tracker project before queueing an MMC masterlist sync', function () {
    Queue::fake();
    mmcPlatformUser();

    $this->postJson(route('integrations.mmc-platform.sync'), [
        'program_id' => 'program-123',
    ])->assertUnprocessable()->assertJsonValidationErrors('tracker_project_id');

    Queue::assertNothingPushed();
});

it('rejects a Tracker project owned by another company', function () {
    Queue::fake();
    mmcPlatformUser();
    $foreignProjectId = mmcTrackerProject(999999);

    $this->postJson(route('integrations.mmc-platform.sync'), [
        'program_id' => 'program-123',
        'tracker_project_id' => $foreignProjectId,
    ])->assertUnprocessable()->assertJsonValidationErrors('tracker_project_id');

    Queue::assertNothingPushed();
});

it('stores the API key encrypted and never renders it back to the page', function () {
    mmcPlatformUser();

    $secret = 'mmc_test_read_key';
    $this->post(route('integrations.mmc-platform.store'), [
        'base_url' => 'https://platform.example.test/api/v1',
        'read_key' => $secret,
    ])->assertRedirect(route('integrations.mmc-platform.index', ['tab' => 'integration']));

    $encryptedKey = DB::table('mmc_platform_settings')->where('id', 1)->value('read_key');
    expect($encryptedKey)->not->toBe($secret)->not->toContain($secret);

    $this->get(route('integrations.mmc-platform.index'))
        ->assertOk()
        ->assertSee('Configured · hidden')
        ->assertDontSee($secret);
});

it('tests the programs endpoint with the read bearer key and one-record limit', function () {
    Http::fakeSequence()->push(['data' => [[
        'id' => 'program-123',
        'name' => 'Sample Program',
        'project_title' => 'School Kits',
        'fiscal_year' => 2026,
        'billing_number' => 'MUST_NOT_BE_SHOWN',
    ]]], 200);
    mmcPlatformUser();

    $this->post(route('integrations.mmc-platform.test-connection'), [
        'base_url' => 'https://platform.example.test/api/v1',
        'read_key' => 'mmc_test_read_key',
    ])
        ->assertRedirect()
        ->assertSessionHas('mmc_platform_test.status', 'success')
        ->assertSessionHas('mmc_platform_test.preview.name', 'Sample Program')
        ->assertSessionMissing('mmc_platform_test.preview.billing_number');

    Http::assertSentCount(1);
    Http::assertSent(fn (HttpRequest $request): bool => $request->url() === 'https://platform.example.test/api/v1/programs?limit=1'
        && $request->hasHeader('Authorization', 'Bearer mmc_test_read_key'));

    $this->get(route('integrations.mmc-platform.index'))
        ->assertOk()
        ->assertSee('Sample Program')
        ->assertDontSee('MUST_NOT_BE_SHOWN')
        ->assertDontSee('mmc_test_read_key');
});

it('reports an unavailable endpoint without exposing the bearer key', function () {
    Http::fakeSequence()->push('', 404);
    mmcPlatformUser();

    $this->post(route('integrations.mmc-platform.test-connection'), [
        'base_url' => 'https://platform.example.test/api/v1',
        'read_key' => 'mmc_test_read_key',
    ])->assertRedirect()->assertSessionHas('mmc_platform_test.status', 'failed');

    Http::assertSentCount(1);
    Http::assertSent(fn (HttpRequest $request): bool => $request->hasHeader('Authorization', 'Bearer mmc_test_read_key'));
});

it('returns a bounded read-only preview and redacts credential-like raw response fields', function () {
    mmcPlatformUser();
    DB::table('mmc_platform_settings')->insert([
        'id' => 1,
        'base_url' => 'https://platform.example.test/api/v1',
        'read_key' => Crypt::encryptString('mmc_test_read_key'),
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    Http::fake([
        'https://platform.example.test/api/v1/programs/program-123/master-list*' => Http::response(['data' => [
            ['delivery_id' => 'd-1', 'school_name' => 'North School', 'api_key' => 'must-hide'],
            ['delivery_id' => 'd-2', 'school_name' => 'South School'],
        ], 'total' => 2, 'has_more' => true]),
    ]);

    $this->postJson(route('integrations.mmc-platform.preview-data'), ['program_id' => 'program-123', 'program_name' => 'Sample Program', 'limit' => 1])
        ->assertOk()
        ->assertJsonPath('status', 'success')
        ->assertJsonPath('records.0.delivery_id', 'd-1')
        ->assertJsonPath('diagnostics.http_status', 200)
        ->assertJsonPath('diagnostics.program_name', 'Sample Program')
        ->assertJsonPath('diagnostics.program_id', 'program-123')
        ->assertJsonPath('diagnostics.parameters.0.page', 1)
        ->assertJsonPath('diagnostics.parameters.0.limit', 1)
        ->assertJsonPath('diagnostics.total', 2)
        ->assertJsonPath('diagnostics.data_count', 2)
        ->assertJsonPath('diagnostics.has_more', true)
        ->assertJsonPath('raw.data.0.api_key', '[REDACTED]');

    Http::assertSent(fn (HttpRequest $request): bool => $request->url() === 'https://platform.example.test/api/v1/programs/program-123/master-list?page=1&limit=1'
        && $request->hasHeader('Authorization', 'Bearer mmc_test_read_key'));
});

it('continues to the next documented page when the first page is empty but has more rows', function () {
    mmcPlatformUser();
    DB::table('mmc_platform_settings')->insert([
        'id' => 1,
        'base_url' => 'https://platform.example.test/api/v1',
        'read_key' => Crypt::encryptString('mmc_test_read_key'),
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    Http::fakeSequence()
        ->push(['data' => [], 'total' => 1, 'has_more' => true], 200)
        ->push(['data' => [['delivery_id' => 'd-1', 'school_name' => 'North School']], 'total' => 1, 'has_more' => false], 200);

    $this->postJson(route('integrations.mmc-platform.preview-data'), ['program_id' => 'program-123', 'program_name' => 'Sample Program', 'limit' => 1])
        ->assertOk()
        ->assertJsonPath('records.0.delivery_id', 'd-1')
        ->assertJsonPath('diagnostics.data_count', 1)
        ->assertJsonPath('diagnostics.parameters.0.page', 1)
        ->assertJsonPath('diagnostics.parameters.1.page', 2);

    Http::assertSent(fn (HttpRequest $request): bool => str_contains($request->url(), '/master-list?page=2&limit=1'));
});

it('reports a genuine zero total from the upstream response distinctly', function () {
    mmcPlatformUser();
    DB::table('mmc_platform_settings')->insert([
        'id' => 1,
        'base_url' => 'https://platform.example.test/api/v1',
        'read_key' => Crypt::encryptString('mmc_test_read_key'),
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    Http::fake([
        'https://platform.example.test/api/v1/programs/program-empty/master-list*' => Http::response(['data' => [], 'total' => 0, 'has_more' => false]),
    ]);

    $this->postJson(route('integrations.mmc-platform.preview-data'), ['program_id' => 'program-empty', 'program_name' => 'Empty Program', 'limit' => 25])
        ->assertOk()
        ->assertJsonPath('status', 'success')
        ->assertJsonPath('message', 'The MMC Platform returned total: 0 and an empty data array for this program. Ask the API owner to verify this exact program ID is populated and visible to the read-scoped key.')
        ->assertJsonPath('diagnostics.total', 0)
        ->assertJsonPath('diagnostics.data_count', 0)
        ->assertJsonPath('diagnostics.has_more', false);
});

it('rejects non-local HTTP URLs without making a request', function () {
    Http::preventStrayRequests();
    mmcPlatformUser();

    $this->post(route('integrations.mmc-platform.test-connection'), [
        'base_url' => 'http://platform.example.test/api/v1',
        'read_key' => 'mmc_test_read_key',
    ])->assertRedirect()->assertSessionHasErrors('base_url');

    Http::assertNothingSent();
});

it('queues project sync and returns the saved results only to its requester', function () {
    Queue::fake();
    config([
        'services.mmc_platform.base_url' => 'https://platform.example.test/api/v1',
        'services.mmc_platform.read_key' => 'mmc_test_read_key',
    ]);
    Http::fakeSequence()
        ->push(['data' => [['delivery_id' => 'delivery-1', 'school_id' => '1', 'grade_levels' => 'G1toG3, G4toG6']], 'has_more' => true])
        ->push(['data' => [['delivery_id' => 'delivery-2', 'school_id' => '2', 'grade_levels' => ['G4toG6'], 'lines' => [['item_id' => 'kit']]]], 'has_more' => false]);
    mmcPlatformUser();
    $trackerProjectId = mmcTrackerProject();

    $statusUrl = $this->postJson(route('integrations.mmc-platform.sync'), [
        'program_id' => 'program-123',
        'tracker_project_id' => $trackerProjectId,
        'program_info' => ['id' => 'program-123'],
    ])->assertAccepted()->assertJsonPath('status', 'pending')->json('status_url');

    Http::assertNothingSent();
    $this->getJson($statusUrl)->assertOk()->assertJsonPath('status', 'pending');
    Queue::assertPushed(SyncMmcPlatformProject::class, 1);
    $job = Queue::pushed(SyncMmcPlatformProject::class)->sole();
    expect($job->connection)->toBe('mmc_platform_sync');
    $job->handle(app(MmcPlatformApiClient::class));

    $this->getJson($statusUrl)
        ->assertOk()
        ->assertJsonPath('status', 'success')
        ->assertJsonPath('pages', 2)
        ->assertJsonPath('record_count', 2)
        ->assertJsonPath('entity_counts.schools', 2)
        ->assertJsonPath('entity_counts.keystages', 2)
        ->assertJsonPath('entity_counts.items', 1);
    $this->assertDatabaseHas('mmc_platform_project_syncs', ['program_id' => 'program-123', 'tracker_project_id' => $trackerProjectId, 'record_count' => 2]);
    $this->assertDatabaseCount('mmc_platform_project_sync_pages', 0);
    $this->assertDatabaseCount('mmc_platform_masterlist_records', 2);
    $this->assertDatabaseHas('mmc_platform_masterlist_records', ['delivery_id' => 'delivery-1', 'tracker_project_id' => $trackerProjectId]);

    mmcPlatformUser();
    $this->getJson($statusUrl)->assertNotFound();
});

it('syncs only new and changed rows and skips unchanged records on resync', function () {
    Queue::fake();
    config([
        'services.mmc_platform.base_url' => 'https://platform.example.test/api/v1',
        'services.mmc_platform.read_key' => 'mmc_test_read_key',
    ]);
    Http::fakeSequence()
        ->push(['data' => [
            ['delivery_id' => 'delivery-unchanged', 'school_id' => 'school-1', 'school_name' => 'Same Name'],
            ['delivery_id' => 'delivery-changed', 'school_id' => 'school-2', 'school_name' => 'Old Name'],
            ['delivery_id' => 'delivery-removed', 'school_id' => 'school-3', 'school_name' => 'Removed Name'],
        ], 'has_more' => false])
        ->push(['data' => [
            ['delivery_id' => 'delivery-unchanged', 'school_id' => 'school-1', 'school_name' => 'Same Name', 'returned_to_office_at' => '1900-01-01'],
            ['delivery_id' => 'delivery-changed', 'school_id' => 'school-2', 'school_name' => 'Updated Name', 'returned_to_office_at' => '1900-01-01'],
            ['delivery_id' => 'delivery-new', 'school_id' => 'school-4', 'school_name' => 'New Name'],
        ], 'has_more' => false]);
    mmcPlatformUser();
    $trackerProjectId = mmcTrackerProject();
    $syncUrl = route('integrations.mmc-platform.sync');
    $syncData = ['program_id' => 'program-123', 'tracker_project_id' => $trackerProjectId];

    $firstStatusUrl = $this->postJson($syncUrl, $syncData)->assertAccepted()->json('status_url');
    Queue::pushed(SyncMmcPlatformProject::class)->last()->handle(app(MmcPlatformApiClient::class));
    $this->getJson($firstStatusUrl)
        ->assertOk()
        ->assertJsonPath('created_count', 3)
        ->assertJsonPath('updated_count', 0)
        ->assertJsonPath('unchanged_count', 0);
    DB::table('mmc_platform_masterlist_records')
        ->where('delivery_id', 'delivery-unchanged')
        ->update(['delivered_at' => '2026-11-20', 'returned_to_office_at' => '2026-11-26']);
    DB::table('mmc_platform_masterlist_records')
        ->where('delivery_id', 'delivery-changed')
        ->update(['returned_to_office_at' => '2026-11-25']);
    $secondStatusUrl = $this->postJson($syncUrl, $syncData)->assertAccepted()->json('status_url');
    Queue::pushed(SyncMmcPlatformProject::class)->last()->handle(app(MmcPlatformApiClient::class));

    $this->getJson($secondStatusUrl)
        ->assertOk()
        ->assertJsonPath('created_count', 1)
        ->assertJsonPath('updated_count', 1)
        ->assertJsonPath('unchanged_count', 1)
        ->assertJsonPath('deleted_count', 1);
    $this->assertDatabaseCount('mmc_platform_project_syncs', 1);
    $this->assertDatabaseHas('mmc_platform_project_syncs', [
        'program_id' => 'program-123',
        'tracker_project_id' => $trackerProjectId,
        'record_count' => 3,
    ]);
    $this->assertDatabaseCount('mmc_platform_project_sync_pages', 0);
    $this->assertDatabaseCount('mmc_platform_masterlist_records', 3);
    $this->assertDatabaseHas('mmc_platform_masterlist_records', [
        'delivery_id' => 'delivery-changed',
        'school_name' => 'Updated Name',
        'returned_to_office_at' => '2026-11-25',
    ]);
    $this->assertDatabaseHas('mmc_platform_masterlist_records', ['delivery_id' => 'delivery-unchanged', 'delivered_at' => '2026-11-20']);
    $this->assertDatabaseHas('mmc_platform_masterlist_records', ['delivery_id' => 'delivery-unchanged', 'returned_to_office_at' => '2026-11-26']);
    $this->assertDatabaseHas('mmc_platform_masterlist_records', ['delivery_id' => 'delivery-new', 'school_name' => 'New Name']);
    $this->assertDatabaseMissing('mmc_platform_masterlist_records', ['delivery_id' => 'delivery-removed']);
});

it('queues a refresh for the selected saved masterlist program', function () {
    Queue::fake([SyncMmcPlatformProject::class]);
    mmcPlatformUser();
    $companyId = (int) auth()->user()->currentCompany()->getKey();
    $trackerProjectId = mmcTrackerProject();
    $syncedAt = now();
    DB::table('mmc_platform_project_syncs')->insert([
        'company_id' => $companyId,
        'program_id' => 'program-123',
        'program_name' => 'SME 2',
        'program_info' => json_encode(['id' => 'program-123', 'name' => 'SME 2'], JSON_THROW_ON_ERROR),
        'records' => '{}',
        'entity_counts' => '{}',
        'record_count' => 1,
        'synced_at' => $syncedAt,
        'created_at' => $syncedAt,
        'updated_at' => $syncedAt,
        'tracker_project_id' => $trackerProjectId,
    ]);

    $statusUrl = $this->postJson(route('integrations.mmc-platform.masterlist.sync'), [
        'program_id' => 'program-123',
    ])->assertAccepted()->assertJsonPath('status', 'pending')->json('status_url');

    $this->getJson($statusUrl)->assertOk()->assertJsonPath('status', 'pending');
    Queue::assertPushed(SyncMmcPlatformProject::class, 1);
});

it('queues nightly refreshes for linked masterlist programs only', function () {
    mmcPlatformUser();
    $companyId = (int) auth()->user()->currentCompany()->getKey();
    $trackerProjectId = mmcTrackerProject();
    $snapshot = [
        'company_id' => $companyId,
        'records' => '{}',
        'entity_counts' => '{}',
        'synced_at' => now(),
    ];
    DB::table('mmc_platform_project_syncs')->insert([
        [...$snapshot, 'program_id' => 'linked-first', 'tracker_project_id' => $trackerProjectId],
        [...$snapshot, 'program_id' => 'unlinked', 'tracker_project_id' => null],
        [...$snapshot, 'program_id' => 'linked-second', 'tracker_project_id' => $trackerProjectId],
    ]);
    Queue::fake([SyncMmcPlatformProject::class]);

    app(Kernel::class)->registerCommand(app(SyncMmcPlatformMasterlist::class));
    $this->artisan('mmc-platform:sync-masterlist')->assertSuccessful();

    $programIds = Queue::pushed(SyncMmcPlatformProject::class)
        ->map(fn (SyncMmcPlatformProject $job): string => (new ReflectionProperty($job, 'data'))->getValue($job)['program_id']);
    expect($programIds->all())->toBe(['linked-first', 'linked-second']);
});

it('sets Delivered At for records matching multiple School IDs in the selected program', function () {
    mmcPlatformUser();
    $companyId = (int) auth()->user()->currentCompany()->getKey();
    $trackerProjectId = mmcTrackerProject();
    $syncedAt = now();
    DB::table('mmc_platform_project_syncs')->insert([
        'company_id' => $companyId,
        'program_id' => 'program-123',
        'tracker_project_id' => $trackerProjectId,
        'program_name' => 'SME 2',
        'program_info' => '{}',
        'records' => '{}',
        'entity_counts' => '{}',
        'record_count' => 3,
        'synced_at' => $syncedAt,
        'created_at' => $syncedAt,
        'updated_at' => $syncedAt,
    ]);
    foreach ([
        ['delivery_id' => 'delivery-school-1-lot-1', 'source_program_id' => 'program-123', 'school_id' => '121700', 'returned_to_office_at' => '2026-11-20'],
        ['delivery_id' => 'delivery-school-1-lot-2', 'source_program_id' => 'program-123', 'school_id' => '121700'],
        ['delivery_id' => 'delivery-school-2', 'source_program_id' => 'program-123', 'school_id' => '121701'],
        ['delivery_id' => 'delivery-other-program', 'source_program_id' => 'program-456', 'school_id' => '121700'],
    ] as $record) {
        DB::table('mmc_platform_masterlist_records')->insert([
            ...$record,
            'company_id' => $companyId,
            'tracker_project_id' => $trackerProjectId,
            'created_at' => $syncedAt,
            'updated_at' => $syncedAt,
        ]);
    }

    $this->post(route('integrations.mmc-platform.masterlist.returned-to-office-at'), [
        'program_id' => 'program-123',
        'school_ids' => "121700,\n121701\nmissing-school",
        'returned_to_office_at' => '2026-11-26',
    ])->assertRedirect(route('integrations.mmc-platform.masterlist', ['program_id' => 'program-123']))
        ->assertSessionHas('returnedToOfficeAtUpdate', 'Set Returned to Office At to 2026-11-26 for 3 record(s). No records matched 1 School ID(s).');

    $this->assertDatabaseHas('mmc_platform_masterlist_records', ['delivery_id' => 'delivery-school-1-lot-1', 'returned_to_office_at' => '2026-11-26', 'delivered_at' => null]);
    $this->assertDatabaseHas('mmc_platform_masterlist_records', ['delivery_id' => 'delivery-school-1-lot-2', 'returned_to_office_at' => '2026-11-26', 'delivered_at' => null]);
    $this->assertDatabaseHas('mmc_platform_masterlist_records', ['delivery_id' => 'delivery-school-2', 'returned_to_office_at' => '2026-11-26', 'delivered_at' => null]);
    $this->assertDatabaseHas('mmc_platform_masterlist_records', ['delivery_id' => 'delivery-other-program', 'returned_to_office_at' => null]);
});

it('rejects an invalid Returned to Office At date before changing masterlist records', function () {
    mmcPlatformUser();
    $companyId = (int) auth()->user()->currentCompany()->getKey();
    $trackerProjectId = mmcTrackerProject();
    $syncedAt = now();
    DB::table('mmc_platform_project_syncs')->insert([
        'company_id' => $companyId,
        'program_id' => 'program-123',
        'tracker_project_id' => $trackerProjectId,
        'program_name' => 'SME 2',
        'program_info' => '{}',
        'records' => '{}',
        'entity_counts' => '{}',
        'record_count' => 1,
        'synced_at' => $syncedAt,
        'created_at' => $syncedAt,
        'updated_at' => $syncedAt,
    ]);
    DB::table('mmc_platform_masterlist_records')->insert([
        'company_id' => $companyId,
        'tracker_project_id' => $trackerProjectId,
        'source_program_id' => 'program-123',
        'delivery_id' => 'delivery-1',
        'school_id' => '121700',
        'created_at' => $syncedAt,
        'updated_at' => $syncedAt,
    ]);

    $this->from(route('integrations.mmc-platform.masterlist'))
        ->post(route('integrations.mmc-platform.masterlist.returned-to-office-at'), [
            'program_id' => 'program-123',
            'school_ids' => '121700',
            'returned_to_office_at' => 'not-a-date',
        ])
        ->assertSessionHasErrors('returned_to_office_at');

    $this->assertDatabaseHas('mmc_platform_masterlist_records', ['delivery_id' => 'delivery-1', 'returned_to_office_at' => null]);
});

it('counts schools as processed only when every delivery has Date Received and Received By', function () {
    $this->travelTo('2026-11-26 09:00:00');
    Http::preventStrayRequests();
    mmcPlatformUser();
    $companyId = (int) auth()->user()->currentCompany()->getKey();
    $trackerProjectId = mmcTrackerProject();
    $otherTrackerProjectId = mmcTrackerProject();
    $syncedAt = now();
    $records = [
        ['delivery_id' => 'school-a-lot-2', 'school_id' => 'school-a', 'lot_no' => '2', 'dr_no' => 'DR-A2', 'date_received' => '2026-11-20', 'received_by' => 'RECIPIENT A'],
        ['delivery_id' => 'school-a-lot-9', 'school_id' => 'school-a', 'lot_no' => 'LOT 9', 'dr_no' => 'DR-A9', 'date_received' => '2026-11-26', 'received_by' => 'RECIPIENT A'],
        ['delivery_id' => 'school-b-lot-11', 'school_id' => 'school-b', 'lot_no' => '11', 'dr_no' => 'DR-B11', 'date_received' => '2026-11-26', 'received_by' => null, 'delivered_at' => '2026-11-26'],
        ['delivery_id' => 'school-c-lot-2', 'school_id' => 'school-c', 'lot_no' => '2', 'dr_no' => 'DR-C2', 'date_received' => '2026-11-26', 'received_by' => 'RECIPIENT C'],
        ['delivery_id' => 'school-c-lot-11', 'school_id' => 'school-c', 'lot_no' => '11', 'dr_no' => 'DR-C11', 'date_received' => null, 'received_by' => 'RECIPIENT C', 'delivered_at' => '2026-11-26'],
        ['delivery_id' => 'excluded-school', 'school_id' => 'school-excluded', 'lot_no' => '2', 'dr_no' => 'DR-X', 'status' => 'cancelled', 'date_received' => null, 'received_by' => null, 'delivered_at' => '2026-11-26'],
        ['delivery_id' => 'other-project-school', 'tracker_project_id' => $otherTrackerProjectId, 'school_id' => 'school-other', 'lot_no' => '2', 'dr_no' => 'DR-OTHER', 'date_received' => null, 'received_by' => null],
    ];
    foreach ($records as $record) {
        DB::table('mmc_platform_masterlist_records')->insert([
            'company_id' => $companyId,
            'tracker_project_id' => $record['tracker_project_id'] ?? $trackerProjectId,
            'source_program_id' => 'program-123',
            'status' => $record['status'] ?? 'pending',
            'created_at' => $syncedAt,
            'updated_at' => $syncedAt,
            ...$record,
        ]);
    }

    $summary = app(BillingDocumentStatusService::class)->summarize($trackerProjectId);

    expect($summary)->toMatchArray([
        'total' => 3,
        'processed' => 1,
        'remaining' => 2,
        'processed_today' => 1,
        'source_error' => null,
    ]);
    Http::assertNothingSent();
});

it('displays saved MMC masterlist records from the selected company snapshot', function () {
    mmcPlatformUser();
    $companyId = auth()->user()->currentCompany()->getKey();
    $trackerProjectId = mmcTrackerProject();
    $syncedAt = now();
    DB::table('mmc_platform_project_syncs')->insert([
        'company_id' => $companyId,
        'program_id' => 'program-123',
        'tracker_project_id' => $trackerProjectId,
        'program_name' => 'SME 2',
        'program_info' => '{}',
        'records' => json_encode(['storage' => 'masterlist_table', 'pages' => 1]),
        'entity_counts' => json_encode(['schools' => 2]),
        'record_count' => 2,
        'synced_at' => $syncedAt,
        'created_at' => $syncedAt,
        'updated_at' => $syncedAt,
    ]);
    DB::table('mmc_platform_masterlist_records')->insert([
        'company_id' => $companyId,
        'tracker_project_id' => $trackerProjectId,
        'source_program_id' => 'program-123',
        'delivery_id' => 'delivery-100',
        'lot_id' => 'lot-2',
        'lot_no' => '2',
        'contract_no' => 'CON-1',
        'dr_no' => 'DR-100',
        'school_id' => '1001',
        'school_name' => 'Alpha Elementary',
        'region' => 'Region I',
        'grade_levels' => 'G1toG3',
        'value' => '1250.5',
        'status' => 'delivered',
        'sap_dr_no' => 'SAP-100',
        'contact_person' => 'Alex Example',
        'date_received' => '2026-11-26',
        'source_updated_at' => '2026-11-26 09:00:00',
        'created_at' => $syncedAt,
        'updated_at' => $syncedAt,
    ]);
    DB::table('mmc_platform_masterlist_records')->insert([
        'company_id' => $companyId,
        'tracker_project_id' => $trackerProjectId,
        'source_program_id' => 'program-123',
        'delivery_id' => 'delivery-101',
        'school_id' => '1002',
        'school_name' => 'Beta Elementary',
        'region' => 'Region II',
        'grade_levels' => 'G4toG6',
        'created_at' => $syncedAt,
        'updated_at' => $syncedAt,
    ]);
    $this->get(route('integrations.mmc-platform.masterlist'))
        ->assertOk()
        ->assertSee('MMC Masterlist')
        ->assertSee('Tracker project: Local Tracker Project')
        ->assertSee('Project ID')
        ->assertSee('aria-sort="ascending"', false)
        ->assertSee((string) $trackerProjectId)
        ->assertSee('Alpha Elementary')
        ->assertSee('Beta Elementary')
        ->assertSee('MMC ID: program-123')
        ->assertSee('DR No.')
        ->assertSee('SAP DR No.')
        ->assertSee('Updated At')
        ->assertSee('Refresh from MMC Platform')
        ->assertSee(route('integrations.mmc-platform.masterlist.sync'), false)
        ->assertSee('Bulk set Returned to Office At')
        ->assertSee('School IDs')
        ->assertSee('Returned to Office At date')
        ->assertSee(route('integrations.mmc-platform.masterlist.returned-to-office-at'), false)
        ->assertSee('DR-100')
        ->assertSee('CON-1')
        ->assertSee('SAP-100')
        ->assertDontSee('Delivery ID')
        ->assertDontSee('Lot ID')
        ->assertSee('Alex Example')
        ->assertSee('G1toG3')
        ->assertDontSee('View data')
        ->assertDontSee('Learning Kit');

    $this->get(route('integrations.mmc-platform.masterlist', ['search' => '1002']))
        ->assertOk()
        ->assertSee('Beta Elementary')
        ->assertDontSee('Alpha Elementary');

    $sortedRows = $this->get(route('integrations.mmc-platform.masterlist', [
        'sort_by' => 'school_name',
        'sort_direction' => 'desc',
    ]))->viewData('rows')->getCollection()->pluck('school_name')->all();

    expect($sortedRows)->toBe(['Beta Elementary', 'Alpha Elementary']);
});

it('preserves a saved project when a later sync page fails', function () {
    Queue::fake();
    config([
        'services.mmc_platform.base_url' => 'https://platform.example.test/api/v1',
        'services.mmc_platform.read_key' => 'mmc_test_read_key',
    ]);
    Http::fakeSequence()
        ->push(['data' => [['delivery_id' => 'delivery-old', 'school_id' => '1']], 'has_more' => false])
        ->push(['data' => [['delivery_id' => 'delivery-new-1', 'school_id' => '2'], ['delivery_id' => 'delivery-new-2', 'school_id' => '3']], 'has_more' => true])
        ->pushStatus(404);
    mmcPlatformUser();
    $trackerProjectId = mmcTrackerProject();

    $sync = route('integrations.mmc-platform.sync');
    $this->postJson($sync, ['program_id' => 'program-123', 'tracker_project_id' => $trackerProjectId])->assertAccepted();
    Queue::pushed(SyncMmcPlatformProject::class)->sole()->handle(app(MmcPlatformApiClient::class));

    $statusUrl = $this->postJson($sync, ['program_id' => 'program-123', 'tracker_project_id' => $trackerProjectId])->assertAccepted()->json('status_url');
    Queue::pushed(SyncMmcPlatformProject::class)->last()->handle(app(MmcPlatformApiClient::class));

    $this->getJson($statusUrl)->assertOk()->assertJsonPath('status', 'failed');
    $this->assertDatabaseCount('mmc_platform_project_syncs', 1);
    $this->assertDatabaseHas('mmc_platform_project_syncs', ['program_id' => 'program-123', 'record_count' => 1]);
    $this->assertDatabaseCount('mmc_platform_project_sync_pages', 0);
    $this->assertDatabaseCount('mmc_platform_masterlist_records', 1);
    $this->assertDatabaseHas('mmc_platform_masterlist_records', ['delivery_id' => 'delivery-old']);
});
