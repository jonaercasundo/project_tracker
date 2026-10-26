<?php

use App\Models\AiConversation;
use App\Models\BudgetRequest;
use App\Services\AiReadOnlyToolGateway;
use Database\Seeders\AiAssistantPermissionSeeder;
use Database\Seeders\AiBusinessDataPermissionSeeder;
use Database\Seeders\AiQuotationPermissionSeeder;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\MIWorkflowTestCase;

class AiAssistantTestCase extends MIWorkflowTestCase
{
    protected function migrateFreshUsing(): array
    {
        $migrationPaths = parent::migrateFreshUsing();
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
    expect($response->streamedContent())->toContain('event: delta', 'Hello from AI', 'event: done');

    $this->getJson(route('assistant.conversations.show', $conversation['id']))
        ->assertOk()->assertJsonPath('conversation.messages.0.role', 'user')
        ->assertJsonPath('conversation.messages.1.content', 'Hello from AI');
    $this->assertDatabaseHas('ai_activity_logs', ['user_id' => $user->getKey(), 'status' => 'completed', 'request_id' => 'req_123']);
    Http::assertSent(fn ($request): bool => $request->url() === 'https://api.openai.com/v1/responses'
        && $request['store'] === false && $request['stream'] === true
        && str_contains($request['instructions'], 'MI Product Designer'));
    expect(Http::recorded()->first()[0]['instructions'])->toContain('Materials and colors support searching');
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
    $readOnlyRole->givePermissionTo(['ai.assistant.use', 'mi.product-catalog.view']);
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
