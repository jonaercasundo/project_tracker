<?php

use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
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

    $administrator = $this->miUser('Administrator', 'MMC');
    $this->actingAs($administrator)->withSession(['company_id' => $administrator->companies()->sole()->getKey()]);
    $this->get(route('integrations.mmc-platform.index'))->assertForbidden();

    $miMember = $this->miUser('user', 'MI');
    $this->actingAs($miMember)->withSession(['company_id' => $miMember->companies()->sole()->getKey()]);
    $this->get(route('integrations.mmc-platform.index'))->assertForbidden();
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
        ]]),
    ]);

    $this->postJson(route('integrations.mmc-platform.preview-data'), ['program_id' => 'program-123', 'limit' => 1])
        ->assertOk()
        ->assertJsonPath('status', 'success')
        ->assertJsonPath('records.0.delivery_id', 'd-1')
        ->assertJsonPath('raw.data.0.api_key', '[REDACTED]');

    Http::assertSent(fn (HttpRequest $request): bool => $request->url() === 'https://platform.example.test/api/v1/programs/program-123/master-list?page=1&limit=1'
        && $request->hasHeader('Authorization', 'Bearer mmc_test_read_key'));
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
