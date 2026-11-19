<?php

use App\Models\AiActivity;
use App\Models\AiProviderSetting;
use App\Models\User;
use App\Services\AiProviderConfiguration;
use App\Services\OpenAiResponsesClient;
use Database\Seeders\AiAssistantPermissionSeeder;
use Database\Seeders\AiSettingsPermissionRepairSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\MIWorkflowTestCase;

class AiConfigurationTestCase extends MIWorkflowTestCase
{
    protected function migrateFreshUsing(): array
    {
        $migrationPaths = parent::migrateFreshUsing();
        $migrationPaths['--path'][] = 'database/migrations/2026_10_26_083437_create_ai_assistant_tables.php';
        $migrationPaths['--path'][] = 'database/migrations/2026_10_26_085724_create_ai_provider_settings_tables.php';
        $migrationPaths['--path'][] = 'database/migrations/2026_11_13_063602_add_research_provider_to_ai_provider_settings.php';
        $migrationPaths['--path'][] = 'database/migrations/2026_10_26_095438_create_ai_quotation_workflow_tables.php';

        return $migrationPaths;
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(AiAssistantPermissionSeeder::class);
    }
}

pest()->extend(AiConfigurationTestCase::class)->in('AiConfigurationTest.php');

function aiAdmin(): User
{
    $user = test()->miUser('Administrator');
    test()->actingAs($user)->withSession(['company_id' => $user->companies()->sole()->getKey()]);

    return $user;
}

it('switches research providers while retaining independent encrypted keys and matching models', function () {
    aiAdmin();
    $this->postJson(route('admin.ai-configuration.store'), aiConfigurationPayload(['openai_api_key' => 'sk-openai-retained', 'research_provider' => 'anthropic', 'anthropic_api_key' => 'sk-ant-secret', 'anthropic_model' => 'claude-sonnet-5-5']))->assertOk()->assertDontSee('sk-ant-secret');

    $configuration = app(AiProviderConfiguration::class);
    expect($configuration->researchProvider())->toBe('anthropic');
    expect($configuration->researchApiKey())->toBe('sk-ant-secret');
    expect($configuration->researchModel())->toBe('claude-sonnet-5-5');
    expect(DB::table('ai_provider_settings')->value('anthropic_api_key'))->not->toBe('sk-ant-secret');
    expect(AiProviderSetting::find(1)->toArray())->not->toHaveKeys(['api_key', 'anthropic_api_key']);
    $page = $this->get(route('admin.ai-configuration.index'))->assertOk()->assertSee('Claude (Anthropic)')->assertDontSee('sk-ant-secret')->assertDontSee('sk-openai-retained');
    if ($path = getenv('AI_CONFIGURATION_SHELL_HTML')) {
        file_put_contents($path, $page->getContent());
    }

    $this->postJson(route('admin.ai-configuration.store'), aiConfigurationPayload(['research_provider' => 'openai']))->assertOk();
    expect($configuration->researchApiKey())->toBe('sk-openai-retained');
    expect($configuration->researchModel())->toBe('gpt-5.5');
    expect(AiProviderSetting::find(1)->anthropic_api_key)->toBe('sk-ant-secret');
    expect(DB::table('ai_provider_setting_audits')->pluck('metadata')->implode(' '))->not->toContain('sk-ant-secret')->not->toContain('sk-openai-retained');
});

it('tests Claude web search with its saved key instead of the OpenAI key', function () {
    aiAdmin();
    $this->postJson(route('admin.ai-configuration.store'), aiConfigurationPayload(['openai_api_key' => 'sk-openai', 'anthropic_api_key' => 'sk-claude', 'research_provider' => 'anthropic', 'anthropic_model' => 'claude-sonnet-5-5']))->assertOk();
    Http::preventStrayRequests();
    Http::fake(['https://api.anthropic.com/v1/messages' => Http::response(['stop_reason' => 'end_turn', 'content' => [['type' => 'web_search_tool_result', 'content' => [['type' => 'web_search_result', 'url' => 'https://platform.claude.com/docs']]]]])]);

    $this->postJson(route('admin.ai-configuration.test-connection'), ['provider' => 'anthropic', 'model' => 'claude-sonnet-5-5', 'web_search' => true])
        ->assertOk()->assertJsonPath('status', 'success')->assertDontSee('sk-claude');
    Http::assertSent(fn ($request): bool => $request->header('x-api-key')[0] === 'sk-claude' && ! $request->hasHeader('Authorization') && $request['model'] === 'claude-sonnet-5-5' && $request['tools'][0]['name'] === 'web_search');
    Http::assertSentCount(1);
});

function aiConfigurationPayload(array $overrides = []): array
{
    return array_replace([
        'model' => 'gpt-5.5',
        'enabled' => true,
        'per_user_requests_per_minute' => 7,
        'monthly_token_limit' => null,
    ], $overrides);
}

it('limits settings management to administrators with the explicit permission', function () {
    $admin = aiAdmin();
    expect($admin->can('ai.settings.manage'))->toBeTrue();
    $this->get(route('admin.ai-configuration.index'))->assertOk()->assertSee('AI Configuration');
    $this->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee('AI Configuration')
        ->assertSee(route('admin.ai-configuration.index'));

    Role::findByName('Administrator', 'web')->revokePermissionTo('ai.settings.manage');
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    $this->get(route('admin.ai-configuration.index'))->assertForbidden();
    $this->get(route('admin.dashboard'))->assertOk()->assertDontSee(route('admin.ai-configuration.index'));

    $regularUser = $this->miUser('user');
    expect($regularUser->can('ai.assistant.use'))->toBeTrue()
        ->and($regularUser->can('ai.settings.manage'))->toBeFalse();
    $this->actingAs($regularUser)->withSession(['company_id' => $regularUser->companies()->sole()->getKey()]);
    $this->get(route('admin.ai-configuration.index'))->assertForbidden();
    $this->postJson(route('admin.ai-configuration.store'), aiConfigurationPayload(['openai_api_key' => 'sk-secret']))->assertForbidden();
});

it('repairs only the web Administrator AI settings permission and is safe to rerun', function () {
    $this->miUser('Administrator');
    $administratorRole = Role::findByName('Administrator', 'web');
    $administratorRole->revokePermissionTo('ai.settings.manage');

    $otherRole = Role::findOrCreate('Operations', 'web');
    $otherPermission = Permission::findOrCreate('reports.view', 'web');
    $otherRole->givePermissionTo($otherPermission);

    app(AiSettingsPermissionRepairSeeder::class)->run();
    app(AiSettingsPermissionRepairSeeder::class)->run();

    expect($administratorRole->fresh()->hasPermissionTo('ai.settings.manage'))->toBeTrue()
        ->and($otherRole->fresh()->hasPermissionTo('reports.view'))->toBeTrue()
        ->and($otherRole->fresh()->hasPermissionTo('ai.settings.manage'))->toBeFalse();
});

it('allows the system-wide AI settings page without a selected company', function () {
    aiAdmin();

    $this->withSession(['company_id' => null])
        ->get(route('admin.ai-configuration.index'))
        ->assertOk();
});

it('encrypts credentials, masks them in responses, and supports replacement and removal', function () {
    $admin = aiAdmin();
    config(['services.openai.key' => null]);
    $firstKey = 'sk-proj-first-secret-key';
    $secondKey = 'sk-proj-second-secret-key';

    $this->postJson(route('admin.ai-configuration.store'), aiConfigurationPayload(['openai_api_key' => $firstKey, 'model' => 'gpt-5.4-mini']))
        ->assertOk()->assertDontSee($firstKey);
    $encrypted = DB::table('ai_provider_settings')->where('id', 1)->value('api_key');
    expect($encrypted)->not->toBe($firstKey)->not->toContain($firstKey);
    expect(AiProviderSetting::query()->findOrFail(1)->api_key)->toBe($firstKey);
    expect(AiProviderSetting::query()->findOrFail(1)->toArray())->not->toHaveKey('api_key');
    $this->get(route('admin.ai-configuration.index'))->assertOk()->assertDontSee($firstKey);
    expect(app(AiProviderConfiguration::class)->apiKey())->toBe($firstKey);

    $this->postJson(route('admin.ai-configuration.store'), aiConfigurationPayload(['openai_api_key' => $secondKey]))->assertOk();
    expect(app(AiProviderConfiguration::class)->apiKey())->toBe($secondKey);
    expect(DB::table('ai_provider_settings')->where('id', 1)->value('api_key'))->not->toBe($firstKey);

    config(['services.openai.key' => 'sk-env-fallback-after-removal']);
    $this->deleteJson(route('admin.ai-configuration.key.destroy'))->assertOk();
    expect(app(AiProviderConfiguration::class)->apiKey())->toBe('sk-env-fallback-after-removal');
    $this->assertDatabaseHas('ai_provider_setting_audits', ['action' => 'key_replaced']);
    $this->assertDatabaseHas('ai_provider_setting_audits', ['action' => 'key_removed']);
    $auditPayloads = DB::table('ai_provider_setting_audits')->pluck('metadata')->implode(' ');
    expect($auditPayloads)->not->toContain($firstKey)->not->toContain($secondKey);
    expect($admin->getKey())->toBeGreaterThan(0);
});

it('can test the environment fallback without copying it into the settings table', function () {
    aiAdmin();
    config(['services.openai.key' => 'sk-env-only-credential']);
    Http::fake(['https://api.openai.com/v1/responses' => Http::response(['id' => 'resp_env'], 200)]);

    $this->postJson(route('admin.ai-configuration.test-connection'), ['model' => 'gpt-5.5'])
        ->assertOk()->assertJsonPath('status', 'success')->assertDontSee('sk-env-only-credential');
    Http::assertSent(fn ($request): bool => $request->header('Authorization')[0] === 'Bearer sk-env-only-credential');
    $this->assertDatabaseCount('ai_provider_settings', 0);
    $auditPayload = DB::table('ai_provider_setting_audits')->where('action', 'connection_test')->value('metadata');
    expect($auditPayload)->not->toContain('sk-env-only-credential');
    expect($auditPayload)->toContain('environment');
});

it('uses the environment key only when a database credential is absent', function () {
    $this->miUser('Administrator');
    config(['services.openai.key' => 'sk-env-fallback']);
    $configuration = app(AiProviderConfiguration::class);
    expect($configuration->credentialSource())->toBe('environment')
        ->and($configuration->apiKey())->toBe('sk-env-fallback');

    $setting = AiProviderSetting::create(['id' => 1, 'provider' => 'openai', 'api_key' => 'sk-db-managed', 'model' => 'gpt-5.4', 'enabled' => true]);
    expect($setting->api_key)->toBe('sk-db-managed')
        ->and($configuration->credentialSource())->toBe('database')
        ->and($configuration->apiKey())->toBe('sk-db-managed');
});

it('tests a saved credential and records a sanitized success result', function () {
    aiAdmin();
    config(['services.openai.key' => null]);
    $key = 'sk-test-connection-secret';
    $this->postJson(route('admin.ai-configuration.store'), aiConfigurationPayload(['openai_api_key' => $key]))->assertOk();
    Http::fake(['https://api.openai.com/v1/responses' => Http::response(['id' => 'resp_test', 'output' => []], 200)]);

    $response = $this->postJson(route('admin.ai-configuration.test-connection'), ['model' => 'gpt-5.5']);
    $response->assertOk()->assertJsonPath('status', 'success')->assertJsonMissing(['api_key' => $key]);
    Http::assertSent(fn ($request): bool => $request->url() === 'https://api.openai.com/v1/responses'
        && $request->header('Authorization')[0] === 'Bearer '.$key
        && $request['store'] === false && $request['max_output_tokens'] === 10);
    $auditPayload = DB::table('ai_provider_setting_audits')->where('action', 'connection_test')->value('metadata');
    expect($auditPayload)->not->toContain($key)->and(json_decode($auditPayload, true)['status'])->toBe('success');
});

it('tests a submitted key without storing it and categorizes provider failures safely', function () {
    aiAdmin();
    config(['services.openai.key' => null]);
    $submittedKey = 'sk-one-time-key';
    Http::fake(['https://api.openai.com/v1/responses' => Http::response(['error' => ['code' => 'insufficient_quota', 'message' => 'sensitive provider response']], 429)]);

    $response = $this->postJson(route('admin.ai-configuration.test-connection'), ['openai_api_key' => $submittedKey, 'model' => 'gpt-5.5']);
    $response->assertUnprocessable()->assertJsonPath('status', 'quota_or_billing')->assertDontSee('sensitive provider response');
    $this->assertDatabaseCount('ai_provider_settings', 0);
    $auditPayload = DB::table('ai_provider_setting_audits')->where('action', 'connection_test')->value('metadata');
    expect($auditPayload)->not->toContain($submittedKey);
});

it('tests web search with the saved credential and records the capability without exposing the key', function (array $providerResponse, int $providerStatus, string $expectedStatus) {
    aiAdmin();
    $this->postJson(route('admin.ai-configuration.store'), aiConfigurationPayload(['openai_api_key' => 'sk-research-test-secret']))->assertOk();
    Http::preventStrayRequests();
    Http::fake(['https://api.openai.com/v1/responses' => Http::response($providerResponse, $providerStatus)]);

    $response = $this->postJson(route('admin.ai-configuration.test-connection'), ['model' => 'gpt-5.5', 'web_search' => true]);

    $response->assertStatus($expectedStatus === 'success' ? 200 : 422)->assertJsonPath('status', $expectedStatus)->assertDontSee('sk-research-test-secret');
    Http::assertSent(fn ($request): bool => $request->header('Authorization')[0] === 'Bearer sk-research-test-secret'
        && $request['tools'][0]['type'] === 'web_search' && $request['tool_choice'] === 'required' && $request['store'] === false);
    $metadata = DB::table('ai_provider_setting_audits')->where('action', 'connection_test')->value('metadata');
    expect($metadata)->not->toContain('sk-research-test-secret');
    expect(json_decode($metadata, true))->toMatchArray(['capability' => 'web_search', 'status' => $expectedStatus]);
})->with([
    'completed web search' => [['status' => 'completed', 'output' => [['type' => 'web_search_call', 'status' => 'completed']]], 200, 'success'],
    'accepted without searching' => [['status' => 'completed', 'output' => []], 200, 'web_search_unverified'],
    'unsupported tool' => [['error' => ['code' => 'unsupported_tool']], 400, 'web_search_unavailable'],
    'exhausted credits' => [['error' => ['code' => 'credit_balance_exhausted']], 429, 'quota_or_billing'],
]);

it('classifies authentication, permission, rate, quota, model, and outage test responses', function () {
    Http::fakeSequence('https://api.openai.com/v1/responses')
        ->push(['error' => ['code' => 'invalid_api_key']], 401)
        ->push(['error' => ['code' => 'permission_denied']], 403)
        ->push(['error' => ['code' => 'rate_limit_exceeded']], 429)
        ->push(['error' => ['code' => 'insufficient_quota']], 429)
        ->push(['error' => ['code' => 'model_not_found']], 400)
        ->push(['error' => ['type' => 'server_error']], 503);

    $client = app(OpenAiResponsesClient::class);
    $statuses = [];
    for ($attempt = 0; $attempt < 6; $attempt++) {
        $statuses[] = $client->testConnection('sk-never-logged', 'gpt-5.5')['status'];
    }

    expect($statuses)->toBe(['invalid_credentials', 'insufficient_permissions', 'rate_limited', 'quota_or_billing', 'model_unavailable', 'provider_outage']);
});

it('uses the database key and model for assistant requests and enforces the global switch and monthly cap', function () {
    $user = $this->miUser('user');
    $this->actingAs($user)->withSession(['company_id' => $user->companies()->sole()->getKey()]);
    config(['services.openai.key' => 'sk-env-should-not-win']);
    $admin = $this->miUser('Administrator');
    AiProviderSetting::create([
        'id' => 1,
        'provider' => 'openai',
        'api_key' => 'sk-db-preferred',
        'model' => 'gpt-5.4-mini',
        'enabled' => true,
        'per_user_requests_per_minute' => 7,
        'monthly_token_limit' => 100,
        'configured_by' => $admin->getKey(),
    ]);
    Http::fake(['https://api.openai.com/v1/responses' => Http::response(
        "data: {\"type\":\"response.output_text.delta\",\"delta\":\"ok\"}\n\n".
        "data: {\"type\":\"response.completed\",\"response\":{\"id\":\"resp_stored\",\"usage\":{\"input_tokens\":2,\"output_tokens\":1},\"output\":[]}}\n\n",
        200,
        ['Content-Type' => 'text/event-stream'],
    )]);
    $conversation = $this->postJson(route('assistant.conversations.store'))->assertCreated()->json('conversation');
    $stream = $this->post(route('assistant.messages.stream', $conversation['id']), ['message' => 'Hello']);
    $stream->assertOk();
    expect($stream->streamedContent())->toContain('event: done');
    Http::assertSent(fn ($request): bool => $request->header('Authorization')[0] === 'Bearer sk-db-preferred' && $request['model'] === 'gpt-5.4-mini');

    AiProviderSetting::query()->findOrFail(1)->update(['enabled' => false]);
    $this->get(route('assistant.workspace'))
        ->assertOk()
        ->assertSee('AI Assistant')
        ->assertSee('disabled', false);
    $this->getJson(route('assistant.conversations.index'))->assertServiceUnavailable();
    AiProviderSetting::query()->findOrFail(1)->update(['enabled' => true, 'monthly_token_limit' => 3]);
    AiActivity::create(['user_id' => $user->getKey(), 'company_id' => $user->companies()->sole()->getKey(), 'model' => 'gpt-5.4-mini', 'input_tokens' => 2, 'output_tokens' => 1, 'status' => 'completed']);
    $this->post(route('assistant.messages.stream', $conversation['id']), ['message' => 'Another one'])->assertStatus(429);
    $this->assertDatabaseCount('ai_messages', 2);
    expect(app(AiProviderConfiguration::class)->perUserRequestLimit())->toBe(7);
});

it('applies the configured per-user request limit across assistant endpoints', function () {
    $user = $this->miUser('user');
    $this->actingAs($user)->withSession(['company_id' => $user->companies()->sole()->getKey()]);
    AiProviderSetting::create(['id' => 1, 'provider' => 'openai', 'model' => 'gpt-5.5', 'enabled' => true, 'per_user_requests_per_minute' => 1]);

    $this->getJson(route('assistant.conversations.index'))->assertOk();
    $this->getJson(route('assistant.capabilities'))->assertTooManyRequests();
});
