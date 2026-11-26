<?php

use App\Jobs\SyncMmcPlatformProject;
use App\Services\MmcPlatformApiClient;
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
            'database/migrations/2026_10_26_115437_add_description_to_roles_and_create_admin_activity_logs_table.php',
            'database/migrations/2026_11_06_054151_create_mmc_platform_settings_table.php',
            'database/migrations/2026_11_11_095147_create_mmc_platform_project_syncs_table.php',
            'database/migrations/2026_11_26_053259_create_mmc_platform_project_sync_pages_table.php',
        ]];
    }
}

pest()->extend(MmcPlatformIntegrationTestCase::class)->in('MmcPlatformIntegrationTest.php');

function mmcPlatformUser(): void
{
    $user = test()->miUser('user', 'MMC');
    test()->signInMI($user);
}

it('shows the configuration screen in the MMC user workspace only', function () {
    mmcPlatformUser();
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
        ->push(['data' => [['school_id' => '1', 'grade_levels' => 'G1toG3, G4toG6']], 'has_more' => true])
        ->push(['data' => [['school_id' => '2', 'grade_levels' => ['G4toG6'], 'lines' => [['item_id' => 'kit']]]], 'has_more' => false]);
    mmcPlatformUser();

    $statusUrl = $this->postJson(route('integrations.mmc-platform.sync'), [
        'program_id' => 'program-123',
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
    $this->assertDatabaseHas('mmc_platform_project_syncs', ['program_id' => 'program-123', 'record_count' => 2]);
    $this->assertDatabaseCount('mmc_platform_project_sync_pages', 2);

    mmcPlatformUser();
    $this->getJson($statusUrl)->assertNotFound();
});

it('displays saved MMC masterlist records from the selected company snapshot', function () {
    mmcPlatformUser();
    $companyId = auth()->user()->currentCompany()->getKey();
    $syncedAt = now();
    DB::table('mmc_platform_project_syncs')->insert([
        'company_id' => $companyId,
        'program_id' => 'program-123',
        'program_name' => 'SME 2',
        'program_info' => '{}',
        'records' => json_encode(['storage' => 'pages', 'pages' => 1]),
        'entity_counts' => json_encode(['schools' => 2]),
        'record_count' => 2,
        'synced_at' => $syncedAt,
        'created_at' => $syncedAt,
        'updated_at' => $syncedAt,
    ]);
    $syncId = DB::table('mmc_platform_project_syncs')->where('program_id', 'program-123')->value('id');
    DB::table('mmc_platform_project_sync_pages')->insert([
        'sync_id' => $syncId,
        'page' => 1,
        'records' => json_encode([
            ['school_id' => '1001', 'school_name' => 'Alpha Elementary', 'region' => 'Region I', 'grade_levels' => 'G1toG3'],
            ['school_id' => '1002', 'school_name' => 'Beta Elementary', 'region' => 'Region II', 'grade_levels' => 'G4toG6'],
        ]),
    ]);

    $this->get(route('integrations.mmc-platform.masterlist'))
        ->assertOk()
        ->assertSee('MMC Masterlist')
        ->assertSee('Alpha Elementary')
        ->assertSee('Beta Elementary')
        ->assertSee('G1toG3')
        ->assertSee('View data');

    $this->get(route('integrations.mmc-platform.masterlist', ['search' => '1002']))
        ->assertOk()
        ->assertSee('Beta Elementary')
        ->assertDontSee('Alpha Elementary');
});

it('preserves a saved project when a later sync page fails', function () {
    Queue::fake();
    config([
        'services.mmc_platform.base_url' => 'https://platform.example.test/api/v1',
        'services.mmc_platform.read_key' => 'mmc_test_read_key',
    ]);
    Http::fakeSequence()
        ->push(['data' => [['school_id' => '1']], 'has_more' => false])
        ->push(['data' => [['school_id' => '2'], ['school_id' => '3']], 'has_more' => true])
        ->pushStatus(404);
    mmcPlatformUser();

    $sync = route('integrations.mmc-platform.sync');
    $this->postJson($sync, ['program_id' => 'program-123'])->assertAccepted();
    Queue::pushed(SyncMmcPlatformProject::class)->sole()->handle(app(MmcPlatformApiClient::class));

    $statusUrl = $this->postJson($sync, ['program_id' => 'program-123'])->assertAccepted()->json('status_url');
    Queue::pushed(SyncMmcPlatformProject::class)->last()->handle(app(MmcPlatformApiClient::class));

    $this->getJson($statusUrl)->assertOk()->assertJsonPath('status', 'failed');
    $this->assertDatabaseCount('mmc_platform_project_syncs', 1);
    $this->assertDatabaseHas('mmc_platform_project_syncs', ['program_id' => 'program-123', 'record_count' => 1]);
    $this->assertDatabaseCount('mmc_platform_project_sync_pages', 1);
    expect(json_decode(DB::table('mmc_platform_project_sync_pages')->value('records'), true))
        ->toBe([['school_id' => '1']]);
});
