<?php

use Illuminate\Http\Client\Request as HttpRequest;
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
        ]];
    }
}

pest()->extend(MmcPlatformIntegrationTestCase::class)->in('MmcPlatformIntegrationTest.php');

function mmcPlatformAdministrator(): void
{
    $administrator = test()->miUser('Administrator', 'MMC');
    test()->signInMI($administrator);
}

it('shows the MMC platform connection status to MMC users and administrators', function () {
    mmcPlatformAdministrator();

    $this->get(route('admin.integrations.mmc-platform.index'))
        ->assertOk()
        ->assertSee('MMC Platform API')
        ->assertSee('MMC_PLATFORM_API_BASE_URL')
        ->assertSee('MMC_PLATFORM_READ_KEY');

    $member = $this->miUser('user', 'MMC');
    $this->actingAs($member)->withSession(['company_id' => $member->companies()->sole()->getKey()]);
    $this->get(route('admin.integrations.mmc-platform.index'))
        ->assertOk()
        ->assertSee('MMC Platform API');

    $miMember = $this->miUser('user', 'MI');
    $this->actingAs($miMember)->withSession(['company_id' => $miMember->companies()->sole()->getKey()]);
    $this->get(route('admin.integrations.mmc-platform.index'))->assertForbidden();
});

it('tests the read-only programs endpoint with the bearer key', function () {
    config([
        'services.mmc_platform.base_url' => 'https://platform.example.test/api/v1',
        'services.mmc_platform.read_key' => 'mmc_test_read_key',
    ]);
    Http::fake(['platform.example.test/api/v1/programs*' => Http::response(['data' => []])]);
    mmcPlatformAdministrator();

    $this->post(route('admin.integrations.mmc-platform.test-connection'))
        ->assertRedirect()
        ->assertSessionHas('success');

    Http::assertSent(fn (HttpRequest $request): bool => $request->url() === 'https://platform.example.test/api/v1/programs?limit=1'
        && $request->hasHeader('Authorization', 'Bearer mmc_test_read_key'));
});

it('refuses non-loopback HTTP endpoints before sending the API key', function () {
    config([
        'services.mmc_platform.base_url' => 'http://platform.example.test/api/v1',
        'services.mmc_platform.read_key' => 'mmc_test_read_key',
    ]);
    mmcPlatformAdministrator();

    $this->get(route('admin.integrations.mmc-platform.index'))
        ->assertOk()
        ->assertSee('Use HTTPS before sending an API key over the network.');

    Http::assertNothingSent();
});
