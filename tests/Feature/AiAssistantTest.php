<?php

use App\Models\AiConversation;
use App\Models\BudgetRequest;
use App\Services\AiReadOnlyToolGateway;
use Database\Seeders\AiAssistantPermissionSeeder;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\MIWorkflowTestCase;

class AiAssistantTestCase extends MIWorkflowTestCase
{
    protected function migrateFreshUsing(): array
    {
        $migrationPaths = parent::migrateFreshUsing();
        $migrationPaths['--path'][] = 'database/migrations/2026_10_26_083437_create_ai_assistant_tables.php';
        $migrationPaths['--path'][] = 'database/migrations/2026_10_26_085724_create_ai_provider_settings_tables.php';

        return $migrationPaths;
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(AiAssistantPermissionSeeder::class);
    }
}

pest()->extend(AiAssistantTestCase::class)->in('AiAssistantTest.php');

it('requires authentication and an authorized company module', function () {
    $this->get(route('assistant.workspace'))->assertRedirect(route('login'));

    $user = $this->miUser('user');
    $this->signInMI($user);
    $this->get(route('assistant.workspace'))->assertOk()->assertSee('Project Tracker Assistant');

    $unsupported = $this->miUser('user', 'OTHER');
    $unsupported->syncRoles([]);
    $this->actingAs($unsupported)->withSession(['company_id' => $unsupported->companies()->sole()->getKey()])
        ->get(route('assistant.workspace'))->assertForbidden();
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
        ->toContain('search_mi_products', 'my_mi_budget_requests')->not->toContain('search_mmc_projects');
    expect(fn () => $gateway->call('search_mmc_projects', ['query' => 'secret'], $user, $user->currentCompany()))
        ->toThrow(HttpException::class);

    $other = $this->miUser('user');
    BudgetRequest::create(['employee_id' => $user->getKey(), 'company_id' => $user->companies()->sole()->getKey(), 'department' => 'Design', 'budget_total' => 250]);
    BudgetRequest::create(['employee_id' => $other->getKey(), 'company_id' => $other->companies()->sole()->getKey(), 'department' => 'Finance', 'budget_total' => 500]);
    $ownRequests = $gateway->call('my_mi_budget_requests', [], $user, $user->currentCompany());
    expect($ownRequests)->toHaveCount(1)->and($ownRequests[0]['department'])->toBe('Design');
});
