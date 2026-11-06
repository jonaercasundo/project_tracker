<?php

use Illuminate\Http\Client\Request as HttpRequest;
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

function mmcPlatformAdministrator(): void
{
    $administrator = test()->miUser('Administrator', 'MMC');
    test()->signInMI($administrator);
}

it('restricts the configuration screen to MMC administrators', function () {
    mmcPlatformAdministrator();
    $this->get(route('admin.integrations.mmc-platform.index'))
        ->assertOk()
        ->assertSee('MMC Platform API')
        ->assertSee('API Base URL')
        ->assertSee('Read-only API Key')
        ->assertSee('Test Connection')
        ->assertSee('Save Configuration')
        ->assertSee('showApiKey')
        ->assertDontSee('mmc_test_read_key');

    $member = $this->miUser('user', 'MMC');
    $this->actingAs($member)->withSession(['company_id' => $member->companies()->sole()->getKey()]);
    $this->get(route('admin.integrations.mmc-platform.index'))->assertForbidden();
    $this->post(route('admin.integrations.mmc-platform.store'), [
        'base_url' => 'https://platform.example.test/api/v1',
        'read_key' => 'mmc_test_read_key',
    ])->assertForbidden();

    $miAdministrator = $this->miUser('Administrator', 'MI');
    $this->actingAs($miAdministrator)->withSession(['company_id' => $miAdministrator->companies()->sole()->getKey()]);
    $this->get(route('admin.integrations.mmc-platform.index'))->assertForbidden();
});

it('stores the API key encrypted and never renders it back to the page', function () {
    mmcPlatformAdministrator();

    $secret = 'mmc_test_read_key';
    $this->post(route('admin.integrations.mmc-platform.store'), [
        'base_url' => 'https://platform.example.test/api/v1',
        'read_key' => $secret,
    ])->assertRedirect(route('admin.integrations.mmc-platform.index'));

    $encryptedKey = DB::table('mmc_platform_settings')->where('id', 1)->value('read_key');
    expect($encryptedKey)->not->toBe($secret)->not->toContain($secret);

    $this->get(route('admin.integrations.mmc-platform.index'))
        ->assertOk()
        ->assertSee('API key configured')
        ->assertDontSee($secret);
});

it('probes the endpoint without credentials and previews only safe program fields', function () {
    Http::fakeSequence()
        ->push(['message' => 'Unauthenticated'], 401)
        ->push(['data' => [[
            'id' => 'program-123',
            'name' => 'Sample Program',
            'project_title' => 'School Kits',
            'fiscal_year' => 2026,
            'billing_number' => 'MUST_NOT_BE_SHOWN',
        ]]], 200);
    mmcPlatformAdministrator();

    $this->post(route('admin.integrations.mmc-platform.test-connection'), [
        'base_url' => 'https://platform.example.test/api/v1',
        'read_key' => 'mmc_test_read_key',
    ])
        ->assertRedirect()
        ->assertSessionHas('mmc_platform_test.status', 'success')
        ->assertSessionHas('mmc_platform_test.preview.name', 'Sample Program')
        ->assertSessionMissing('mmc_platform_test.preview.billing_number');

    Http::assertSentCount(2);
    Http::assertSent(fn (HttpRequest $request): bool => $request->url() === 'https://platform.example.test/api/v1/programs?limit=1'
        && ! $request->hasHeader('Authorization'));
    Http::assertSent(fn (HttpRequest $request): bool => $request->url() === 'https://platform.example.test/api/v1/programs?limit=1'
        && $request->hasHeader('Authorization', 'Bearer mmc_test_read_key'));

    $this->get(route('admin.integrations.mmc-platform.index'))
        ->assertOk()
        ->assertSee('Sample Program')
        ->assertDontSee('MUST_NOT_BE_SHOWN')
        ->assertDontSee('mmc_test_read_key');
});

it('does not send the key when the endpoint cannot be verified', function () {
    Http::fakeSequence()->push('', 302, ['Location' => 'https://other.example.test/']);
    mmcPlatformAdministrator();

    $this->post(route('admin.integrations.mmc-platform.test-connection'), [
        'base_url' => 'https://platform.example.test/api/v1',
        'read_key' => 'mmc_test_read_key',
    ])->assertRedirect()->assertSessionHas('mmc_platform_test.status', 'failed');

    Http::assertSentCount(1);
    Http::assertSent(fn (HttpRequest $request): bool => ! $request->hasHeader('Authorization'));
});

it('rejects non-local HTTP URLs without making a request', function () {
    Http::preventStrayRequests();
    mmcPlatformAdministrator();

    $this->post(route('admin.integrations.mmc-platform.test-connection'), [
        'base_url' => 'http://platform.example.test/api/v1',
        'read_key' => 'mmc_test_read_key',
    ])->assertRedirect()->assertSessionHasErrors('base_url');

    Http::assertNothingSent();
});
