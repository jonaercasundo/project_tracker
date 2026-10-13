<?php

use App\Jobs\ProcessLogisticsWebhookEvent;
use App\Models\Company;
use App\Models\LogisticsIntegration;
use App\Models\User;
use App\Services\LogisticsChannelService;
use App\Services\LogisticsDrResolver;
use App\Services\LogisticsDrTrackingService;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Illuminate\Testing\TestResponse;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class LogisticsChannelTestCase extends TestCase
{
    public function createApplication(): Application
    {
        $app = require Application::inferBasePath().'/bootstrap/app.php';
        $app->singleton(Kernel::class, LogisticsChannelConsoleKernel::class);
        $app->make(Kernel::class)->bootstrap();
        $app['config']->set('database.default', 'sqlite');
        $app['config']->set('database.connections.sqlite.database', ':memory:');
        $app['config']->set('session.driver', 'array');
        $app['config']->set('queue.default', 'database');
        $app['config']->set('queue.connections.logistics_webhooks.connection', 'sqlite');
        $app['config']->set('logistics-webhooks.keys', []);
        $app['config']->set('logistics-webhooks.integration_scopes', []);

        return $app;
    }

    protected function setUp(): void
    {
        parent::setUp();
        Artisan::call('migrate:fresh', [
            '--database' => 'sqlite',
            '--path' => [
                database_path('migrations/0001_01_01_000000_create_users_table.php'),
                database_path('migrations/0001_01_01_000002_create_jobs_table.php'),
                database_path('migrations/2026_06_15_081615_create_permission_tables.php'),
                database_path('migrations/2026_09_02_024221_create_companies_table.php'),
                database_path('migrations/2026_09_02_024448_create_company_user_table.php'),
                database_path('migrations/2026_10_13_062831_create_logistics_webhook_events_table.php'),
                database_path('migrations/2026_10_13_064633_add_dr_scope_reconciliation_to_logistics_webhook_events_table.php'),
                database_path('migrations/2026_10_13_064633_create_logistics_webhook_dr_mappings_table.php'),
                database_path('migrations/2026_10_13_070536_create_logistics_dr_tracking_table.php'),
                database_path('migrations/2026_10_13_082243_create_logistics_channel_management_tables.php'),
            ],
            '--realpath' => true,
            '--force' => true,
        ]);
        Schema::create('projects', function (Blueprint $table): void {
            $table->increments('project_id');
            $table->string('project_name')->nullable();
        });
        Schema::create('school', function (Blueprint $table): void {
            $table->string('school_id')->primary();
            $table->unsignedInteger('project_id');
            $table->string('school_name')->nullable();
        });
        Schema::create('deliveries', function (Blueprint $table): void {
            $table->increments('delivery_id');
            $table->unsignedInteger('project_id');
            $table->string('school_id');
            $table->string('dr_no', 100);
            $table->string('status')->default('pending');
            $table->unsignedInteger('keystage_id')->nullable();
            $table->unsignedInteger('lot_id')->nullable();
        });
        Schema::create('package', function (Blueprint $table): void {
            $table->increments('package_id');
            $table->unsignedInteger('keystage_id')->nullable();
            $table->unsignedInteger('lot_id')->nullable();
        });
        Schema::create('package_status', function (Blueprint $table): void {
            $table->increments('package_status_id');
            $table->unsignedInteger('delivery_id');
            $table->unsignedInteger('package_id');
            $table->string('status')->default('pending');
        });
        DB::table('projects')->insert([
            ['project_id' => 502698, 'project_name' => 'MMC Science Materials'],
            ['project_id' => 502699, 'project_name' => 'MMC Furniture'],
        ]);
        DB::table('school')->insert([
            ['school_id' => '134967', 'project_id' => 502698, 'school_name' => 'North School'],
            ['school_id' => '134969', 'project_id' => 502698, 'school_name' => 'Central School'],
            ['school_id' => '134968', 'project_id' => 502699, 'school_name' => 'South School'],
        ]);
        Queue::fake();
    }
}

class LogisticsChannelConsoleKernel extends Illuminate\Foundation\Console\Kernel
{
    protected function discoverCommands(): void {}
}

pest()->extend(LogisticsChannelTestCase::class)->in(__FILE__);

function logisticsChannelAccount(bool $userRole = true): array
{
    $roleName = $userRole ? 'user' : 'Administrator';
    $user = User::factory()->create(['username' => fake()->unique()->userName(), 'role' => $roleName]);
    $user->assignRole(Role::findOrCreate($roleName, 'web'));
    $company = Company::query()->firstOrCreate(['code' => 'MMC'], ['name' => 'MMC', 'is_active' => true]);
    $user->companies()->attach($company);

    return [$user, $company];
}

function logisticsChannelSignIn(LogisticsChannelTestCase $test, string $keyId, string $secret, array $payload): TestResponse
{
    $body = json_encode($payload, JSON_THROW_ON_ERROR);
    $timestamp = (string) time();

    return $test->call('POST', '/api/v1/logistics/webhooks/delivery-status', [], [], [], [
        'CONTENT_TYPE' => 'application/json',
        'HTTP_ACCEPT' => 'application/json',
        'HTTP_X_INTEGRATION_KEY_ID' => $keyId,
        'HTTP_X_WEBHOOK_TIMESTAMP' => $timestamp,
        'HTTP_X_WEBHOOK_SIGNATURE' => 'v1='.hash_hmac('sha256', $keyId."\n".$timestamp."\n".$body, $secret),
    ], $body);
}

function logisticsChannelIntegrationData(array $overrides = []): array
{
    return array_merge([
        'integration_id' => 'logistics-mmc-test',
        'name' => 'MMC Logistics Test',
        'webhook_url' => 'https://tracker.example.test/api/v1/logistics/webhooks/delivery-status',
        'project_ids' => [502698],
        'school_scopes' => ['502698:MTM0OTY3'],
        'enabled_event_types' => ['delivery.logistics_received', 'delivery.delivered'],
        'status' => 'active',
    ], $overrides);
}

function logisticsChannelPayload(array $overrides = []): array
{
    return array_merge([
        'event_id' => 'channel-event-001',
        'event_type' => 'delivery.logistics_received',
        'dr_reference' => '0035470',
        'occurred_at' => '2026-10-13T14:30:00+08:00',
        'sequence' => 1,
    ], $overrides);
}

it('shows the management page only to MMC users', function () {
    [$operator, $company] = logisticsChannelAccount();
    $this->withoutVite()->actingAs($operator)->withSession(['company_id' => $company->company_id]);

    $this->get('/logistics-channel?tab=overview')->assertOk()->assertSee('Logistics Channel')->assertSee('Queue');

    [$administrator, $administratorCompany] = logisticsChannelAccount(false);
    $this->actingAs($administrator)->withSession(['company_id' => $administratorCompany->company_id]);
    $this->get('/logistics-channel')->assertForbidden();
});

it('rolls back and reapplies only the additive management migration', function () {
    $migration = database_path('migrations/2026_10_13_082243_create_logistics_channel_management_tables.php');

    expect(Schema::hasTable('logistics_integrations'))->toBeTrue()
        ->and(Schema::hasTable('logistics_integration_credentials'))->toBeTrue();

    Artisan::call('migrate:rollback', [
        '--database' => 'sqlite',
        '--path' => [$migration],
        '--realpath' => true,
        '--force' => true,
    ]);
    expect(Schema::hasTable('logistics_integrations'))->toBeFalse()
        ->and(Schema::hasTable('logistics_webhook_events'))->toBeTrue();

    Artisan::call('migrate', [
        '--database' => 'sqlite',
        '--path' => [$migration],
        '--realpath' => true,
        '--force' => true,
    ]);
    expect(Schema::hasTable('logistics_integrations'))->toBeTrue()
        ->and(Schema::hasTable('logistics_integration_audit_logs'))->toBeTrue();
});

it('creates an integration with encrypted one-time credentials, exact scope, and audit record', function () {
    [$admin, $company] = logisticsChannelAccount();
    $this->actingAs($admin)->withSession(['company_id' => $company->company_id]);

    $response = $this->post('/logistics-channel/integrations', logisticsChannelIntegrationData());
    $response->assertOk()->assertSee('shown once')->assertSee('X-Integration-Key-Id');
    expect($response->headers->get('Cache-Control'))->toContain('no-store');
    $integration = LogisticsIntegration::query()->where('integration_id', 'logistics-mmc-test')->firstOrFail();
    $credential = $integration->credentials()->firstOrFail();
    $storedSecret = DB::table('logistics_integration_credentials')->where('id', $credential->id)->value('secret');
    expect($credential->secret)->toBeString()
        ->and($storedSecret)->not->toBe($credential->secret)
        ->and(DB::table('logistics_integration_projects')->where('integration_id', $integration->id)->pluck('project_id')->all())->toBe([502698])
        ->and(DB::table('logistics_integration_schools')->where('integration_id', $integration->id)->value('school_id'))->toBe('134967')
        ->and(DB::table('logistics_integration_audit_logs')->where('action', 'integration_created')->exists())->toBeTrue()
        ->and(json_encode(DB::table('logistics_integration_audit_logs')->first(), JSON_THROW_ON_ERROR))->not->toContain($credential->secret);

    $this->get('/logistics-channel?tab=integrations')->assertOk()->assertDontSee($credential->secret);
});

it('requires explicit permission before omitting school restrictions', function () {
    [$admin, $company] = logisticsChannelAccount();
    $this->actingAs($admin)->withSession(['company_id' => $company->company_id]);
    $data = logisticsChannelIntegrationData();
    unset($data['school_scopes']);

    $this->from('/logistics-channel?tab=integrations')->post('/logistics-channel/integrations', $data)->assertSessionHasErrors('school_scopes');
    expect(DB::table('logistics_integrations')->where('integration_id', 'logistics-mmc-test')->exists())->toBeFalse();

    $this->post('/logistics-channel/integrations', [...$data, 'all_schools' => '1'])->assertOk()->assertSee('shown once');
    $integrationId = DB::table('logistics_integrations')->where('integration_id', 'logistics-mmc-test')->value('id');
    expect(DB::table('logistics_integration_schools')->where('integration_id', $integrationId)->count())->toBe(0);
});

it('authenticates database credentials and refuses disabled integrations', function () {
    [$admin, $company] = logisticsChannelAccount();
    DB::table('deliveries')->insert([
        ['project_id' => 502698, 'school_id' => '134967', 'dr_no' => '0035470', 'status' => 'warehouse', 'keystage_id' => 64],
        ['project_id' => 502698, 'school_id' => '134967', 'dr_no' => '0035470', 'status' => 'warehouse', 'keystage_id' => 65],
        ['project_id' => 502698, 'school_id' => '134969', 'dr_no' => '0035470', 'status' => 'warehouse', 'keystage_id' => 66],
        ['project_id' => 502699, 'school_id' => '134968', 'dr_no' => '0035470', 'status' => 'warehouse', 'keystage_id' => 67],
    ]);
    $created = app(LogisticsChannelService::class)->create(logisticsChannelIntegrationData(), $admin->user_id, '127.0.0.1');
    $this->actingAs($admin)->withSession(['company_id' => $company->company_id]);

    logisticsChannelSignIn($this, $created['key_id'], $created['secret'], logisticsChannelPayload())->assertAccepted();
    $scope = app(LogisticsDrResolver::class)->resolve('logistics-mmc-test', '0035470');
    expect($scope['status'])->toBe('resolved')->and($scope['delivery_count'])->toBe(2);
    expect(DB::table('deliveries')->pluck('status')->unique()->all())->toBe(['warehouse']);
    $eventRowId = (int) DB::table('logistics_webhook_events')->where('external_event_id', 'channel-event-001')->value('id');
    (new ProcessLogisticsWebhookEvent($eventRowId))->handle(app(LogisticsDrResolver::class), app(LogisticsDrTrackingService::class));
    expect(DB::table('logistics_dr_tracking')->count())->toBe(1)
        ->and(DB::table('deliveries')->pluck('status')->unique()->all())->toBe(['warehouse']);

    LogisticsIntegration::query()->whereKey($created['integration']->id)->update(['status' => 'disabled']);
    logisticsChannelSignIn($this, $created['key_id'], $created['secret'], logisticsChannelPayload(['event_id' => 'channel-event-002']))->assertUnauthorized();
    config(['logistics-webhooks.keys' => ['logistics-mmc-test' => 'old-environment-key']]);
    logisticsChannelSignIn($this, 'logistics-mmc-test', 'old-environment-key', logisticsChannelPayload(['event_id' => 'channel-event-003']))->assertUnauthorized();
});

it('updates an integration without changing credentials and narrows its project and school scope', function () {
    [$admin, $company] = logisticsChannelAccount();
    $created = app(LogisticsChannelService::class)->create(logisticsChannelIntegrationData(), $admin->user_id, '127.0.0.1');
    DB::table('deliveries')->insert([
        ['project_id' => 502698, 'school_id' => '134967', 'dr_no' => '0035470', 'status' => 'warehouse'],
        ['project_id' => 502699, 'school_id' => '134968', 'dr_no' => '0035470', 'status' => 'warehouse'],
    ]);
    $this->actingAs($admin)->withSession(['company_id' => $company->company_id]);

    $this->patch(route('logistics-channel.integrations.update', $created['integration']), [
        'name' => 'MMC Logistics North',
        'webhook_url' => 'https://tracker.example.test/api/v1/logistics/webhooks/delivery-status',
        'project_ids' => [502699],
        'school_scopes' => ['502699:MTM0OTY4'],
        'enabled_event_types' => ['delivery.logistics_received'],
        'status' => 'active',
    ])->assertRedirect();

    expect($created['integration']->fresh()->name)->toBe('MMC Logistics North')
        ->and($created['integration']->credentials()->count())->toBe(1)
        ->and(app(LogisticsDrResolver::class)->resolve('logistics-mmc-test', '0035470')['project_id'])->toBe(502699)
        ->and(app(LogisticsDrResolver::class)->resolve('logistics-mmc-test', '0035470')['delivery_count'])->toBe(1)
        ->and($created['integration']->fresh()->enabled_event_types)->toBe(['delivery.logistics_received']);
    logisticsChannelSignIn($this, $created['key_id'], $created['secret'], logisticsChannelPayload([
        'event_type' => 'delivery.delivered',
        'event_id' => 'channel-disabled-event-type',
    ]))->assertUnprocessable();
});

it('adopts an environment integration without changing its key or project scope', function () {
    [$admin, $company] = logisticsChannelAccount();
    config(['logistics-webhooks.keys' => ['logistics-mmc-sme2' => 'existing-test-secret']]);
    config(['logistics-webhooks.integration_scopes' => ['logistics-mmc-sme2' => ['project_ids' => [502698], 'school_ids' => []]]]);
    DB::table('deliveries')->insert(['project_id' => 502698, 'school_id' => '134967', 'dr_no' => '0035470', 'status' => 'warehouse']);
    $this->actingAs($admin)->withSession(['company_id' => $company->company_id]);

    $this->post('/logistics-channel/integrations/adopt-environment', [
        'integration_id' => 'logistics-mmc-sme2',
        'name' => 'MMC Logistics Production',
    ])->assertRedirect(route('logistics-channel.index', ['tab' => 'integrations']))->assertSessionHas('status');

    $credential = DB::table('logistics_integration_credentials')->where('key_id', 'logistics-mmc-sme2')->first();
    expect(Crypt::decryptString($credential->secret))->toBe('existing-test-secret')
        ->and(DB::table('logistics_integration_projects')->where('integration_id', $credential->integration_id)->value('project_id'))->toBe(502698);
    logisticsChannelSignIn($this, 'logistics-mmc-sme2', 'existing-test-secret', logisticsChannelPayload())->assertAccepted();
    expect(DB::table('logistics_integration_audit_logs')->where('action', 'environment_integration_adopted')->exists())->toBeTrue();
});

it('rotates keys with overlap, revokes credentials, and never lists stored secrets', function () {
    [$admin, $company] = logisticsChannelAccount();
    $created = app(LogisticsChannelService::class)->create(logisticsChannelIntegrationData(), $admin->user_id, '127.0.0.1');
    $this->actingAs($admin)->withSession(['company_id' => $company->company_id]);

    $rotation = $this->post(route('logistics-channel.integrations.rotate', $created['integration']), ['overlap_days' => 3]);
    $rotation->assertOk()->assertSee('shown once');
    $newSecret = (string) $rotation->viewData('secret');
    $oldCredential = $created['integration']->credentials()->where('key_id', $created['key_id'])->firstOrFail();
    expect($oldCredential->fresh()->expires_at)->not->toBeNull();
    $this->get('/logistics-channel?tab=integrations')->assertOk()->assertDontSee($created['secret'])->assertDontSee($newSecret);

    $this->post(route('logistics-channel.integrations.credentials.revoke', [$created['integration'], $oldCredential]), [])->assertRedirect();
    expect($oldCredential->fresh()->status)->toBe('revoked')
        ->and(DB::table('logistics_integration_audit_logs')->where('action', 'credential_rotated')->exists())->toBeTrue()
        ->and(DB::table('logistics_integration_audit_logs')->where('action', 'credential_revoked')->exists())->toBeTrue();
    logisticsChannelSignIn($this, $created['key_id'], $created['secret'], logisticsChannelPayload())->assertUnauthorized();
});

it('filters event history without exposing payloads and retries only stored failed events', function () {
    [$admin, $company] = logisticsChannelAccount();
    $created = app(LogisticsChannelService::class)->create(logisticsChannelIntegrationData(), $admin->user_id, '127.0.0.1');
    DB::table('logistics_webhook_events')->insert([
        'integration_id' => 'logistics-mmc-test',
        'external_event_id' => 'failed-channel-event',
        'event_type' => 'delivery.logistics_received',
        'dr_reference' => '0035470',
        'payload' => json_encode(['secret_marker' => 'never-render-this'], JSON_THROW_ON_ERROR),
        'payload_hash' => hash('sha256', 'payload'),
        'event_occurred_at' => now(),
        'received_at' => now(),
        'processing_status' => 'failed',
        'processing_attempts' => 3,
        'resolution_status' => 'pending',
        'reconciliation_status' => 'pending',
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    $eventId = (int) DB::table('logistics_webhook_events')->value('id');
    $this->withoutVite()->actingAs($admin)->withSession(['company_id' => $company->company_id]);

    $this->get('/logistics-channel?tab=events&dr_reference=0035470&status=failed')
        ->assertOk()->assertSee('failed-channel-event')->assertDontSee('never-render-this')->assertDontSee($created['secret']);
    $this->post(route('logistics-channel.events.retry', $eventId))->assertRedirect();
    Queue::assertPushed(ProcessLogisticsWebhookEvent::class, fn (ProcessLogisticsWebhookEvent $job): bool => $job->eventRowId === $eventId);
    expect(DB::table('logistics_webhook_events')->where('id', $eventId)->value('processing_status'))->toBe('received')
        ->and(DB::table('logistics_integration_audit_logs')->where('action', 'event_retry_requested')->exists())->toBeTrue();
    $this->post(route('logistics-channel.events.retry', $eventId))->assertConflict();
});
