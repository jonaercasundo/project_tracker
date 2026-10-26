<?php

use App\Models\AiConversation;
use App\Models\AiQuotation;
use App\Models\MI_Product;
use Database\Seeders\AiAssistantPermissionSeeder;
use Database\Seeders\AiQuotationPermissionSeeder;
use Illuminate\Support\Facades\Http;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\MIWorkflowTestCase;

class AiQuotationAssistantTestCase extends MIWorkflowTestCase
{
    protected function migrateFreshUsing(): array
    {
        $paths = parent::migrateFreshUsing();
        $paths['--path'] = [...$paths['--path'],
            'database/migrations/2026_07_27_073520_create_m_i__products_table.php',
            'database/migrations/2026_07_29_075636_create_categories_table.php',
            'database/migrations/2026_07_29_075711_create_sub_categories_table.php',
            'database/migrations/2026_07_29_075746_create_product_types_table.php',
            'database/migrations/2026_07_29_075829_create_collections_table.php',
            'database/migrations/2026_07_30_085237_create_mi_materials_table.php',
            'database/migrations/2026_10_26_075908_add_persistent_mi_product_attribute_options.php',
            'database/migrations/2026_07_31_015928_update_mi_products_table_for_new_product_system.php',
            'database/migrations/2026_07_31_020944_change_materials_and_color_to_json_in_mi_products_table.php',
            'database/migrations/2026_08_17_000000_add_price_to_products_table.php',
            'database/migrations/2026_10_06_040342_create_mi_product_images_table.php',
            'database/migrations/2026_10_06_085134_add_unique_item_code_to_mi_products_table.php',
            'database/migrations/2026_10_12_055813_create_mi_product_factory_costs_table.php',
            'database/migrations/2026_10_20_082229_add_country_of_production_to_mi_products_table.php',
            'database/migrations/2026_10_26_083437_create_ai_assistant_tables.php',
            'database/migrations/2026_10_26_085724_create_ai_provider_settings_tables.php',
            'database/migrations/2026_10_26_095438_create_ai_quotation_workflow_tables.php',
        ];

        return $paths;
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(AiAssistantPermissionSeeder::class);
        $this->seed(AiQuotationPermissionSeeder::class);
    }
}

pest()->extend(AiQuotationAssistantTestCase::class)->in('AiQuotationAssistantTest.php');

function aiQuotationConversation($user): AiConversation
{
    return AiConversation::query()->create([
        'user_id' => $user->getKey(),
        'company_id' => $user->companies()->sole()->getKey(),
        'title' => 'Product price question',
    ]);
}

it('shows verified selling prices only to users with quotation permission', function () {
    $product = MI_Product::factory()->create(['item_name' => 'ROUND SIDE TABLE (M)', 'sku' => 'FU-AC-XXX-TA-0393', 'price' => '1250.75', 'purchase_cost' => '12.34']);
    $user = $this->miUser();
    $this->signInMI($user);

    $this->getJson(route('assistant.quotations.products', ['query' => 'ROUND SIDE TABLE']))
        ->assertOk()->assertJsonPath('products.0.id', $product->getKey())->assertJsonPath('products.0.price', '1250.75')
        ->assertJsonPath('products.0.currency', 'PHP')->assertJsonMissingPath('products.0.purchase_cost');

    $limited = $this->miUser('finance');
    $this->actingAs($limited)->withSession(['company_id' => $limited->companies()->sole()->getKey()])
        ->getJson(route('assistant.quotations.products', ['query' => 'ROUND']))->assertForbidden();
});

it('streams a verified product price card from the authorized assistant tool and retains it with the conversation', function () {
    $product = MI_Product::factory()->create(['item_name' => 'ROUND SIDE TABLE (M)', 'sku' => 'MI-PRICE-01', 'price' => '1250.75']);
    $user = $this->miUser();
    $this->signInMI($user);
    config(['services.openai.key' => 'test-key', 'services.openai.assistant_model' => 'gpt-test']);
    $call = ['type' => 'function_call', 'name' => 'search_mi_products', 'arguments' => '{"query":"ROUND SIDE TABLE (M)"}', 'call_id' => 'call_price'];
    $firstResponse = 'data: '.json_encode(['type' => 'response.output_item.done', 'item' => $call])."\n\n".
        'data: '.json_encode(['type' => 'response.completed', 'response' => ['output' => [$call], 'usage' => ['input_tokens' => 10, 'output_tokens' => 2]]])."\n\n";
    $secondResponse = 'data: '.json_encode(['type' => 'response.output_text.delta', 'delta' => 'I found the product and its selling price. Would you like me to prepare a quotation?'])."\n\n".
        'data: '.json_encode(['type' => 'response.completed', 'response' => ['output' => [], 'usage' => ['input_tokens' => 12, 'output_tokens' => 18]]])."\n\n";
    $sequence = Http::sequence()
        ->push($firstResponse, 200, ['Content-Type' => 'text/event-stream'])
        ->push($secondResponse, 200, ['Content-Type' => 'text/event-stream']);
    Http::fake(['https://api.openai.com/v1/responses' => $sequence]);
    $conversation = $this->postJson(route('assistant.conversations.store'))->assertCreated()->json('conversation');

    $stream = $this->post(route('assistant.messages.stream', $conversation['id']), ['message' => 'How much is ROUND SIDE TABLE (M)?']);
    $stream->assertOk();
    expect($stream->streamedContent())->toContain('event: products', '1250.75', 'MI product selling price', 'event: done');
    $message = $this->getJson(route('assistant.conversations.show', $conversation['id']))->assertOk()->json('conversation.messages.1');
    expect($message['metadata']['products'][0]['id'])->toBe($product->getKey())
        ->and($message['metadata']['products'][0]['price'])->toBe('1250.75');
    $this->assertDatabaseHas('ai_quotation_audits', ['conversation_id' => $conversation['id'], 'action' => 'quotation_suggestion_shown']);
});

it('previews multiple products with database prices and confirms one idempotent draft', function () {
    $first = MI_Product::factory()->create(['item_name' => 'ROUND SIDE TABLE (M)', 'sku' => 'MI-001', 'price' => '1250.75']);
    $second = MI_Product::factory()->create(['item_name' => 'OAK STOOL', 'sku' => 'MI-002', 'price' => '250.10']);
    $user = $this->miUser();
    $this->signInMI($user);
    $conversation = aiQuotationConversation($user);

    $this->postJson(route('assistant.quotations.start', $conversation), ['product_id' => $first->getKey()])->assertCreated();
    $preview = $this->postJson(route('assistant.quotations.preview', $conversation), [
        'customer_name' => 'ABC Trading',
        'items' => [['id' => $first->getKey(), 'quantity' => 2], ['id' => $second->getKey(), 'quantity' => 3]],
    ])->assertOk()->assertJsonPath('preview.total', '3251.80')->json('preview');
    expect($preview['items'][0]['unit_price'])->toBe('1250.75');
    $this->assertDatabaseCount('ai_quotations', 0);

    $confirmation = $this->postJson(route('assistant.quotations.confirm', $conversation))->assertCreated()->json('quotation');
    $this->assertDatabaseCount('ai_quotations', 1);
    $this->assertDatabaseHas('ai_quotations', ['id' => $confirmation['id'], 'customer_name' => 'ABC Trading', 'total' => '3251.80', 'status' => 'draft']);
    $this->postJson(route('assistant.quotations.confirm', $conversation))->assertCreated()->assertJsonPath('quotation.id', $confirmation['id']);
    $this->assertDatabaseCount('ai_quotations', 1);
    $this->assertDatabaseHas('ai_quotation_audits', ['quotation_id' => $confirmation['id'], 'action' => 'draft_confirmed']);
    $this->get($confirmation['print_url'])->assertOk()->assertSee('ABC Trading')->assertSee('MI-001')->assertSee('₱');
});

it('requires valid prices and explicit preview before confirmation', function () {
    $product = MI_Product::factory()->create(['price' => null]);
    $user = $this->miUser();
    $this->signInMI($user);
    $conversation = aiQuotationConversation($user);
    $this->postJson(route('assistant.quotations.start', $conversation), ['product_id' => $product->getKey()])->assertCreated();

    $this->postJson(route('assistant.quotations.preview', $conversation), ['customer_name' => 'ABC', 'items' => [['id' => $product->getKey(), 'quantity' => 1]]])
        ->assertUnprocessable()->assertJsonPath('message', 'A selected product has no approved selling price.');
    $this->postJson(route('assistant.quotations.confirm', $conversation))->assertUnprocessable();
    $this->assertDatabaseCount('ai_quotations', 0);
});

it('rechecks changed prices and requires a second confirmation on the updated preview', function () {
    $product = MI_Product::factory()->create(['price' => '100.00']);
    $user = $this->miUser();
    $this->signInMI($user);
    $conversation = aiQuotationConversation($user);
    $this->postJson(route('assistant.quotations.start', $conversation), ['product_id' => $product->getKey()])->assertCreated();
    $this->postJson(route('assistant.quotations.preview', $conversation), ['customer_name' => 'ABC', 'items' => [['id' => $product->getKey(), 'quantity' => 2]]])->assertOk();
    $product->update(['price' => '125.00']);

    $this->postJson(route('assistant.quotations.confirm', $conversation))->assertStatus(409)->assertJsonPath('preview.total', '250.00');
    $this->assertDatabaseCount('ai_quotations', 0);
    $this->postJson(route('assistant.quotations.confirm', $conversation))->assertCreated();
    $this->assertDatabaseHas('ai_quotations', ['total' => '250.00']);
});

it('isolates quotation workflow and printable drafts by active user and company', function () {
    $product = MI_Product::factory()->create();
    $owner = $this->miUser();
    $this->signInMI($owner);
    $conversation = aiQuotationConversation($owner);
    $this->postJson(route('assistant.quotations.start', $conversation), ['product_id' => $product->getKey()])->assertCreated();
    $other = $this->miUser('user', 'MMC');
    $this->actingAs($other)->withSession(['company_id' => $other->companies()->sole()->getKey()])
        ->getJson(route('assistant.quotations.show', $conversation))->assertForbidden();
    $this->assertDatabaseCount('ai_quotations', 0);
    expect(AiQuotation::query()->count())->toBe(0);
});

it('hides saved price-bearing conversations after MI quotation permission is revoked', function () {
    $user = $this->miUser();
    $this->signInMI($user);
    $conversation = aiQuotationConversation($user);
    $conversation->messages()->create(['role' => 'assistant', 'content' => 'Selling price: PHP 100.00', 'metadata' => ['products' => [['id' => 1, 'price' => '100.00']]]]);
    Role::findByName('user', 'web')->revokePermissionTo('mi.quotation.create');
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    $this->getJson(route('assistant.conversations.show', $conversation))->assertForbidden();
    $this->post(route('assistant.messages.stream', $conversation), ['message' => 'Repeat the price'])->assertForbidden();
});
