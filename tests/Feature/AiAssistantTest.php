<?php

use App\Models\AiConversation;
use App\Models\BudgetRequest;
use App\Models\MI_Liquidation;
use App\Models\MI_Product;
use App\Services\AiAssistantService;
use App\Services\AiCapabilityRegistry;
use App\Services\AiReadOnlyToolGateway;
use Database\Seeders\AiAssistantPermissionSeeder;
use Database\Seeders\AiBusinessDataPermissionSeeder;
use Database\Seeders\AiQuotationPermissionSeeder;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\MIWorkflowTestCase;

class AiAssistantTestCase extends MIWorkflowTestCase
{
    protected function migrateFreshUsing(): array
    {
        $migrationPaths = parent::migrateFreshUsing();
        $migrationPaths['--path'][] = 'database/migrations/2026_07_27_073520_create_m_i__products_table.php';
        $migrationPaths['--path'][] = 'database/migrations/2026_07_29_075636_create_categories_table.php';
        $migrationPaths['--path'][] = 'database/migrations/2026_07_29_075711_create_sub_categories_table.php';
        $migrationPaths['--path'][] = 'database/migrations/2026_07_29_075746_create_product_types_table.php';
        $migrationPaths['--path'][] = 'database/migrations/2026_07_29_075829_create_collections_table.php';
        $migrationPaths['--path'][] = 'database/migrations/2026_07_31_015928_update_mi_products_table_for_new_product_system.php';
        $migrationPaths['--path'][] = 'database/migrations/2026_08_17_000000_add_price_to_products_table.php';
        $migrationPaths['--path'][] = 'database/migrations/2026_07_31_020944_change_materials_and_color_to_json_in_mi_products_table.php';
        $migrationPaths['--path'][] = 'database/migrations/2026_10_06_040342_create_mi_product_images_table.php';
        $migrationPaths['--path'][] = 'database/migrations/2026_10_06_085134_add_unique_item_code_to_mi_products_table.php';
        $migrationPaths['--path'][] = 'database/migrations/2026_10_20_082229_add_country_of_production_to_mi_products_table.php';
        $migrationPaths['--path'][] = 'database/migrations/2026_10_26_083437_create_ai_assistant_tables.php';
        $migrationPaths['--path'][] = 'database/migrations/2026_10_26_085724_create_ai_provider_settings_tables.php';
        $migrationPaths['--path'][] = 'database/migrations/2026_10_26_095438_create_ai_quotation_workflow_tables.php';

        return $migrationPaths;
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(AiAssistantPermissionSeeder::class);
        $this->seed(AiBusinessDataPermissionSeeder::class);
        $this->seed(AiQuotationPermissionSeeder::class);
    }
}

pest()->extend(AiAssistantTestCase::class)->in('AiAssistantTest.php');

it('requires authentication and an authorized company module', function () {
    $this->get(route('assistant.workspace'))->assertRedirect(route('login'));

    $user = $this->miUser('user');
    $this->signInMI($user);
    $this->get(route('assistant.workspace'))->assertOk()->assertSee('AI Assistant')->assertSee('not_configured', false);

    $unsupported = $this->miUser('user', 'OTHER');
    $unsupported->syncRoles([]);
    $this->actingAs($unsupported)->withSession(['company_id' => $unsupported->companies()->sole()->getKey()])
        ->get(route('assistant.workspace'))->assertForbidden();
});

it('mounts the shared assistant component in each active authenticated application layout', function () {
    foreach ([
        'layouts/app.blade.php',
        'layouts/finance_app.blade.php',
        'layouts/project_app.blade.php',
        'components/mi_app.blade.php',
        'components/it_app.blade.php',
        'components/accounting_app.blade.php',
        'components/project_warehouse_app.blade.php',
        'entry/show.blade.php',
        'socialMediaScraper/tiktok.blade.php',
        'socialMediaScraper/pinterest.blade.php',
    ] as $layout) {
        expect(file_get_contents(resource_path('views/'.$layout)))->toContain('<x-ai-assistant');
    }

    foreach ([
        'components/mi-sidebar.blade.php',
        'layouts/project-sidebar.blade.php',
        'layouts/finance-sidebar.blade.php',
        'layouts/ppl-sidebar.blade.php',
        'components/accounting-sidebar.blade.php',
        'components/it-sidebar.blade.php',
        'components/project-warehouse-sidebar.blade.php',
    ] as $sidebar) {
        expect(file_get_contents(resource_path('views/'.$sidebar)))->toContain('<x-ai-assistant-nav-link');
    }
});

it('renders the assistant on an authenticated self-contained trend page', function () {
    $user = $this->miUser('user', 'MMC');
    $this->actingAs($user)->withSession(['company_id' => $user->companies()->sole()->getKey()]);
    config(['services.apify.token' => null]);

    $this->get(route('tiktok.trends'))
        ->assertOk()
        ->assertSee('AI Assistant')
        ->assertSee('data-ai-assistant', false)
        ->assertSee('sm:w-[min(760px,calc(100vw-3rem))]', false)
        ->assertSee('h-[min(92vh,820px)]', false)
        ->assertSee('md:grid-cols-[208px_minmax(0,1fr)]', false)
        ->assertSee('contextOpen', false);
});

it('shows an actionable workspace status when an authorized user has no active company selected', function () {
    $user = $this->miUser('user');
    $this->actingAs($user);

    $this->get(route('assistant.workspace'))
        ->assertOk()
        ->assertSee('data-ai-assistant', false)
        ->assertSee('select_company', false);
});

it('renders the guided full-screen workspace with responsive conversation and context panels', function () {
    $user = $this->miUser('user');
    $this->signInMI($user);
    config(['services.openai.key' => null]);

    $this->get(route('assistant.workspace'))
        ->assertOk()
        ->assertSee('Project Tracker AI')
        ->assertSee('Your conversations')
        ->assertSee('A good place to start')
        ->assertSee('Page context')
        ->assertSee('2xl:grid-cols-[260px_minmax(0,1fr)_284px]', false)
        ->assertSee('xl:hidden', false)
        ->assertSee('statusDetails()', false)
        ->assertSee('motion-reduce:transition-none', false);
});

it('adapts the launcher guidance and commerce actions to the MI designer page', function () {
    $user = $this->miUser('user');
    $this->signInMI($user);

    $this->getJson(route('assistant.capabilities', ['page_route' => 'mi_app.create']))
        ->assertOk()
        ->assertJsonPath('context.key', 'mi_designer')
        ->assertJsonPath('context.page.title', 'Create Product')
        ->assertJsonPath('context.suggestions.0', 'How do I create a product?');
    expect(file_get_contents(resource_path('js/ai-assistant.js')))->toContain('Search products', 'Check a price', 'How much is ROUND SIDE TABLE (M)?', 'Create a quotation', 'Explain this page');
});

it('can resume the assistant migration after its conversation table was left behind', function () {
    $migration = require database_path('migrations/2026_10_26_083437_create_ai_assistant_tables.php');
    $migration->down();
    Schema::create('ai_conversations', function ($table): void {
        $table->id();
        $table->unsignedBigInteger('user_id');
        $table->unsignedBigInteger('company_id');
        $table->string('title', 120)->default('New conversation');
        $table->timestamp('last_message_at')->nullable();
        $table->timestamps();
        $table->index(['user_id', 'company_id', 'updated_at']);
    });

    $migration->up();

    expect(Schema::hasTable('ai_conversations'))->toBeTrue()
        ->and(Schema::hasTable('ai_messages'))->toBeTrue()
        ->and(Schema::hasTable('ai_activity_logs'))->toBeTrue();
});

it('stores conversation messages and streams provider deltas without saving provider errors', function () {
    expect(Schema::hasColumn('ai_messages', 'metadata'))->toBeTrue();
    $user = $this->miUser('user');
    $this->signInMI($user);
    config(['services.openai.key' => 'test-key', 'services.openai.assistant_model' => 'gpt-test']);
    Http::fake(['https://api.openai.com/v1/responses' => Http::response(
        "data: {\"type\":\"response.output_text.delta\",\"delta\":\"Hello from AI\"}\n\n".
        "data: {\"type\":\"response.completed\",\"response\":{\"id\":\"resp_abc\",\"usage\":{\"input_tokens\":5,\"output_tokens\":3},\"output\":[]}}\n\n",
        200,
        ['Content-Type' => 'text/event-stream', 'x-request-id' => 'req_123'],
    )]);

    $conversation = $this->postJson(route('assistant.conversations.store'))->assertCreated()->json('conversation');
    $response = $this->post(route('assistant.messages.stream', $conversation['id']), ['message' => 'How do I create a product?', 'page_route' => 'mi_app.create']);
    $response->assertOk();
    expect($response->streamedContent())->toContain('event: status', 'Connecting…', 'Thinking…', 'event: delta', 'Hello from AI', 'event: done');

    $this->getJson(route('assistant.conversations.show', $conversation['id']))
        ->assertOk()->assertJsonPath('conversation.messages.0.role', 'user')
        ->assertJsonPath('conversation.messages.1.content', 'Hello from AI');
    $this->assertDatabaseHas('ai_activity_logs', ['user_id' => $user->getKey(), 'status' => 'completed', 'request_id' => 'req_123']);
    Http::assertSent(fn ($request): bool => $request->url() === 'https://api.openai.com/v1/responses'
        && $request['store'] === false && $request['stream'] === true
        && str_contains($request['instructions'], 'MI Product Designer'));
    expect(Http::recorded()->first()[0]['instructions'])->toContain('Materials and colors support searching');
});

it('reuses the saved user message when retrying a failed assistant stream', function () {
    $user = $this->miUser('user');
    $this->signInMI($user);
    config(['services.openai.key' => 'test-key', 'services.openai.assistant_model' => 'gpt-test']);
    Http::fakeSequence('https://api.openai.com/v1/responses')
        ->push("data: {\"type\":\"error\",\"error\":{\"message\":\"temporary\"}}\n\n", 200, ['Content-Type' => 'text/event-stream'])
        ->push("data: {\"type\":\"response.output_text.delta\",\"delta\":\"Recovered\"}\n\n".
            "data: {\"type\":\"response.completed\",\"response\":{\"usage\":{},\"output\":[]}}\n\n", 200, ['Content-Type' => 'text/event-stream']);
    $conversation = $this->postJson(route('assistant.conversations.store'))->assertCreated()->json('conversation');

    $this->post(route('assistant.messages.stream', $conversation['id']), ['message' => 'Please help'])
        ->assertOk()->streamedContent();
    $this->assertDatabaseCount('ai_messages', 1);
    $this->post(route('assistant.messages.stream', $conversation['id']), ['message' => 'Please help', 'retry' => true])
        ->assertOk()->streamedContent();

    $this->assertDatabaseCount('ai_messages', 2);
    $this->assertDatabaseHas('ai_messages', ['conversation_id' => $conversation['id'], 'role' => 'assistant', 'content' => 'Recovered']);
});

it('replays a completed answer on retry without calling OpenAI or duplicating messages', function () {
    $user = $this->miUser('user');
    $this->signInMI($user);
    config(['services.openai.key' => 'test-key', 'services.openai.assistant_model' => 'gpt-test']);
    Http::fake(['https://api.openai.com/v1/responses' => Http::response(
        "data: {\"type\":\"response.output_text.delta\",\"delta\":\"Saved answer\"}\n\n".
        "data: {\"type\":\"response.completed\",\"response\":{\"usage\":{},\"output\":[]}}\n\n",
        200,
        ['Content-Type' => 'text/event-stream'],
    )]);
    $conversation = $this->postJson(route('assistant.conversations.store'))->assertCreated()->json('conversation');

    $this->post(route('assistant.messages.stream', $conversation['id']), ['message' => 'Please help'])
        ->assertOk()->streamedContent();
    $retry = $this->post(route('assistant.messages.stream', $conversation['id']), ['message' => 'Please help', 'retry' => true])
        ->assertOk();

    expect($retry->streamedContent())->toContain('event: delta', 'Saved answer', 'event: done');
    expect(Http::recorded())->toHaveCount(1);
    $this->assertDatabaseCount('ai_messages', 2);
});

it('routes a Filipino product price question to the authorized catalog tool', function () {
    $user = $this->miUser('user');
    $this->signInMI($user);
    config(['services.openai.key' => 'test-key', 'services.openai.assistant_model' => 'gpt-test']);
    Http::fake(['https://api.openai.com/v1/responses' => Http::response(
        "data: {\"type\":\"response.output_text.delta\",\"delta\":\"Titingnan ko ang presyo.\"}\n\n".
        "data: {\"type\":\"response.completed\",\"response\":{\"usage\":{},\"output\":[]}}\n\n",
        200,
        ['Content-Type' => 'text/event-stream'],
    )]);
    $conversation = $this->postJson(route('assistant.conversations.store'))->assertCreated()->json('conversation');

    $response = $this->post(route('assistant.messages.stream', $conversation['id']), ['message' => 'Magkano ang Round Side Table (M)?'])
        ->assertOk();
    $response->streamedContent();

    Http::assertSent(fn ($request): bool => ($request['tool_choice'] ?? null) === ['type' => 'function', 'name' => 'lookup_mi_product_prices']);
});

it('limits liquidation answers to the signed-in employee’s own status records', function () {
    $user = $this->miUser('user');
    $user->givePermissionTo(Permission::findOrCreate('mi.liquidation.view', 'web'));
    $other = $this->miUser('user');
    $companyId = $user->companies()->sole()->getKey();
    MI_Liquidation::create(['title' => 'My travel advance', 'date_prepared' => '2026-10-01', 'exchange_rate' => 1, 'company_id' => $companyId, 'prepared_by' => $user->getKey(), 'status' => 'Pending']);
    MI_Liquidation::create(['title' => 'Private advance', 'date_prepared' => '2026-10-02', 'exchange_rate' => 1, 'company_id' => $companyId, 'prepared_by' => $other->getKey(), 'status' => 'Approved']);
    $this->signInMI($user);
    $gateway = app(AiReadOnlyToolGateway::class);

    expect(collect($gateway->tools($user, $user->currentCompany(), []))->pluck('name'))->toContain('my_mi_liquidations');
    expect($gateway->call('my_mi_liquidations', [], $user, $user->currentCompany()))
        ->toBe([['type' => 'ordinary', 'reference' => 'My travel advance', 'date' => '2026-10-01', 'status' => 'Pending']]);
});

it('isolates conversation reads and deletion by user and active company', function () {
    $owner = $this->miUser('user');
    $this->signInMI($owner);
    $conversation = AiConversation::create(['user_id' => $owner->getKey(), 'company_id' => $owner->companies()->sole()->getKey(), 'title' => 'Private']);
    $other = $this->miUser('user', 'MMC');

    $this->actingAs($other)->withSession(['company_id' => $other->companies()->sole()->getKey()])
        ->getJson(route('assistant.conversations.show', $conversation))->assertNotFound();
    $this->deleteJson(route('assistant.conversations.destroy', $conversation))->assertNotFound();
    $this->assertDatabaseHas('ai_conversations', ['id' => $conversation->getKey()]);
});

it('rejects invalid messages and refuses provider calls without configuration', function () {
    $user = $this->miUser('user');
    $this->signInMI($user);
    $conversation = AiConversation::create(['user_id' => $user->getKey(), 'company_id' => $user->companies()->sole()->getKey(), 'title' => 'New conversation']);

    $this->postJson(route('assistant.messages.stream', $conversation), ['message' => str_repeat('a', 4001)])
        ->assertUnprocessable()->assertJsonValidationErrors('message');
    $this->assertDatabaseCount('ai_messages', 0);
    $this->postJson(route('assistant.messages.stream', $conversation), ['message' => 'Hello'])->assertServiceUnavailable();
    $this->assertDatabaseCount('ai_messages', 0);
});

it('deletes expired conversations during the current user history listing', function () {
    $user = $this->miUser('user');
    $this->signInMI($user);
    $conversation = AiConversation::create(['user_id' => $user->getKey(), 'company_id' => $user->companies()->sole()->getKey(), 'title' => 'Expired']);
    $conversation->forceFill(['updated_at' => now()->subDays(91)])->save();

    $this->getJson(route('assistant.conversations.index'))->assertOk()->assertJsonCount(0, 'conversations');
    $this->assertDatabaseMissing('ai_conversations', ['id' => $conversation->getKey()]);
});

it('deletes an expired conversation when it is opened directly', function () {
    $user = $this->miUser('user');
    $this->signInMI($user);
    $conversation = AiConversation::create(['user_id' => $user->getKey(), 'company_id' => $user->companies()->sole()->getKey(), 'title' => 'Expired']);
    $conversation->forceFill(['updated_at' => now()->subDays(91)])->save();

    $this->getJson(route('assistant.conversations.show', $conversation))->assertNotFound();
    $this->assertDatabaseMissing('ai_conversations', ['id' => $conversation->getKey()]);
});

it('returns page metadata from the authorized registry and denies cross-company data tools', function () {
    $user = $this->miUser('user');
    $this->signInMI($user);
    $this->getJson(route('assistant.capabilities', ['page_route' => 'mi_app.create']))
        ->assertOk()->assertJsonPath('context.page.title', 'Create Product')
        ->assertJsonPath('context.fields.materials', 'Required; choose one or more materials, up to 100 values.');

    $gateway = app(AiReadOnlyToolGateway::class);
    expect(collect($gateway->tools($user, $user->currentCompany(), []))->pluck('name')->all())
        ->toContain('search_mi_products', 'lookup_mi_product_prices', 'mi_product_catalog_summary', 'mi_products_needing_cop', 'my_mi_budget_requests')
        ->not->toContain('search_mmc_projects');
    expect(fn () => $gateway->call('search_mmc_projects', ['query' => 'secret'], $user, $user->currentCompany()))
        ->toThrow(HttpException::class);

    $other = $this->miUser('user');
    BudgetRequest::create(['employee_id' => $user->getKey(), 'company_id' => $user->companies()->sole()->getKey(), 'department' => 'Design', 'budget_total' => 250]);
    BudgetRequest::create(['employee_id' => $other->getKey(), 'company_id' => $other->companies()->sole()->getKey(), 'department' => 'Finance', 'budget_total' => 500]);
    $ownRequests = $gateway->call('my_mi_budget_requests', [], $user, $user->currentCompany());
    expect($ownRequests)->toHaveCount(1)->and($ownRequests[0]['department'])->toBe('Design');
});

it('requires page-help and business-search AI permissions in addition to module access', function () {
    $user = $this->miUser('user');
    $this->signInMI($user);
    $company = $user->currentCompany();
    $registry = app(AiCapabilityRegistry::class);
    $gateway = app(AiReadOnlyToolGateway::class);

    expect($registry->allows('ai.page_help', $user, $company))->toBeTrue()
        ->and($registry->allows('mi.catalog.search', $user, $company))->toBeTrue();

    Role::findByName('user', 'web')->revokePermissionTo('ai.page-help.use');
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    $this->getJson(route('assistant.capabilities'))->assertForbidden();
    expect($registry->allows('ai.page_help', $user->fresh(), $company))->toBeFalse();

    Role::findByName('user', 'web')->revokePermissionTo('ai.business-search.use');
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    expect(collect($gateway->tools($user->fresh(), $company, []))->pluck('name'))->not->toContain('search_mi_products');
});

it('returns verified product specifications and links without leaking purchase costs', function () {
    $user = $this->miUser('user');
    $this->signInMI($user);
    $product = MI_Product::factory()->create([
        'item_name' => 'Spec Search Chair',
        'description' => 'Solid timber chair with a low back.',
        'product_height' => '82.00',
        'product_width' => '48.00',
        'product_length' => '52.00',
        'product_depth' => '49.00',
        'purchase_cost' => '999.00',
        'price' => '1999.00',
    ]);
    $result = app(AiReadOnlyToolGateway::class)->call('search_mi_products', ['query' => 'Spec Search Chair'], $user, $user->currentCompany());

    expect($result)->toHaveCount(1)
        ->and($result[0]['specifications']['description'])->toBe('Solid timber chair with a low back.')
        ->and($result[0]['specifications']['dimensions']['height'])->toBe('82.00')
        ->and($result[0]['url'])->toBe(route('mi_app.show', $product->getKey()))
        ->and(array_keys($result[0]))->not->toContain('price', 'purchase_cost');
});

it('keeps advertised callable capabilities aligned with the authorized tool gateway', function () {
    $user = $this->miUser('user');
    $this->signInMI($user);
    $company = $user->currentCompany();
    $registry = app(AiCapabilityRegistry::class);
    $gateway = app(AiReadOnlyToolGateway::class);
    $registeredToolNames = collect($registry->definitions())
        ->filter(fn (array $capability): bool => $registry->allows($capability['id'], $user, $company))
        ->flatMap(fn (array $capability): array => $capability['tool_names'])->unique()->values();
    $gatewayToolNames = collect($gateway->tools($user, $company, []))->pluck('name')->values();

    expect($registeredToolNames->diff($gatewayToolNames)->all())->toBeEmpty()
        ->and($gatewayToolNames->diff($registeredToolNames)->all())->toBeEmpty()
        ->and($registry->allowsTool('search_mi_products', $user, $company))->toBeTrue()
        ->and($registry->allowsTool('lookup_mi_product_prices', $user, $company))->toBeTrue()
        ->and($registry->allowsTool('search_mmc_projects', $user, $company))->toBeFalse()
        ->and($registry->allows('business.records.delete', $user, $company))->toBeFalse();
});

it('registers MMC project analytics only within an authorized active MMC workspace', function () {
    $authorized = $this->miUser('user', 'MMC');
    $this->actingAs($authorized)->withSession(['company_id' => $authorized->companies()->sole()->getKey()]);
    $gateway = app(AiReadOnlyToolGateway::class);
    $tools = collect($gateway->tools($authorized, $authorized->currentCompany(), []))->pluck('name');

    expect($tools)->toContain('get_mmc_project_delivery_summary');

    $wrongModuleUser = $this->miUser('user', 'MI');
    $this->actingAs($wrongModuleUser)->withSession(['company_id' => $wrongModuleUser->companies()->sole()->getKey()]);
    expect(collect($gateway->tools($wrongModuleUser, $wrongModuleUser->currentCompany(), []))->pluck('name'))
        ->not->toContain('get_mmc_project_delivery_summary');
    expect(fn () => $gateway->call('get_mmc_project_delivery_summary', ['project_references' => ['SME2'], 'date_from' => null, 'date_to' => null], $wrongModuleUser, $wrongModuleUser->currentCompany()))
        ->toThrow(HttpException::class);
});

it('routes English and Filipino MMC delivery questions through project analytics', function (string $question) {
    $assistant = app(AiAssistantService::class);
    $method = new ReflectionMethod($assistant, 'requiredReadTool');

    expect($method->invoke($assistant, [['role' => 'user', 'content' => $question]], [['name' => 'get_mmc_project_delivery_summary']], []))
        ->toBe('get_mmc_project_delivery_summary');
})->with([
    'How many delivered in SME 2?',
    'Ilan na delivered sa SME2?',
    'Compare SME1 and SME2 deliveries.',
    'How many schools were delivered in SME-2?',
]);

it('keeps MI catalog and price reads separate from quotation creation permission', function () {
    $user = $this->miUser('user');
    $this->signInMI($user);
    Role::findByName('user', 'web')->revokePermissionTo('mi.quotation.create');
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    $gateway = app(AiReadOnlyToolGateway::class);
    $company = $user->currentCompany();
    $toolNames = collect($gateway->tools($user, $company, ['key' => 'workspace']))->pluck('name')->all();

    expect($toolNames)->toContain('lookup_mi_product_prices')->and($user->can('mi.quotation.create'))->toBeFalse();

    $readOnlyRole = Role::findOrCreate('MI_Catalog_Read_Only', 'web');
    $readOnlyRole->givePermissionTo(['ai.assistant.use', 'ai.business-search.use', 'mi.product-catalog.view']);
    $readOnlyUser = $this->miUser('MI_Catalog_Read_Only');
    $this->actingAs($readOnlyUser)->withSession(['company_id' => $readOnlyUser->companies()->sole()->getKey()]);
    $readOnlyCompany = $readOnlyUser->currentCompany();
    $readOnlyTools = collect($gateway->tools($readOnlyUser, $readOnlyCompany, []))->pluck('name')->all();

    expect($readOnlyTools)->toContain('search_mi_products')->not->toContain('lookup_mi_product_prices');
    expect(fn () => $gateway->call('lookup_mi_product_prices', ['query' => 'table'], $readOnlyUser, $readOnlyCompany))
        ->toThrow(HttpException::class);
});

it('registers the MI accounting queue summary only for accounting-authorized users', function () {
    $user = $this->miUser('Accounting');
    $this->signInMI($user);
    $company = $user->currentCompany();
    $gateway = app(AiReadOnlyToolGateway::class);

    expect(collect($gateway->tools($user, $company, []))->pluck('name'))
        ->toContain('mi_accounting_queue_summary');

    $employee = $this->miUser('user');
    $this->signInMI($employee);
    expect(collect($gateway->tools($employee, $employee->currentCompany(), []))->pluck('name'))
        ->not->toContain('mi_accounting_queue_summary');
});

it('summarizes MI approvals only for authorized approvers and filters out their own budget requests', function () {
    $approver = $this->miUser('Executive');
    $this->signInMI($approver);
    $employee = $this->miUser('user');
    BudgetRequest::create([
        'employee_id' => $employee->getKey(),
        'company_id' => $approver->companies()->sole()->getKey(),
        'department' => 'Design',
        'budget_total' => 300,
        'status' => 'budget_requested',
    ]);
    $gateway = app(AiReadOnlyToolGateway::class);
    $summary = $gateway->call('mi_pending_approval_summary', [], $approver, $approver->currentCompany());

    expect($summary['pending_budget_approvals'])->toBe(1)
        ->and($summary['pending_travel_liquidations'])->toBe(0);

    $employeeCompany = $employee->currentCompany();
    expect(fn () => $gateway->call('mi_pending_approval_summary', [], $employee, $employeeCompany))
        ->toThrow(HttpException::class);
});
