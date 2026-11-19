<?php

use App\Models\AiProviderSetting;
use App\Models\MI_Product;
use App\Models\MI_Product_Factory_Cost;
use App\Models\MI_Product_Image;
use App\Services\PublicDnsResolver;
use App\Services\VerifiedListingImageResolver;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Tests\MIWorkflowTestCase;

pest()->extend(MIWorkflowTestCase::class);

beforeEach(function () {
    $this->mock(VerifiedListingImageResolver::class)->shouldReceive('resolve')->andReturn(['url' => null, 'status' => 'missing']);
    foreach ([
        '2026_07_27_073520_create_m_i__products_table.php',
        '2026_07_29_075636_create_categories_table.php',
        '2026_07_29_075711_create_sub_categories_table.php',
        '2026_07_29_075746_create_product_types_table.php',
        '2026_07_29_075829_create_collections_table.php',
        '2026_07_30_085237_create_mi_materials_table.php',
        '2026_10_26_075908_add_persistent_mi_product_attribute_options.php',
        '2026_07_31_015928_update_mi_products_table_for_new_product_system.php',
        '2026_07_31_020944_change_materials_and_color_to_json_in_mi_products_table.php',
        '2026_08_17_000000_add_price_to_products_table.php',
        '2026_10_06_040342_create_mi_product_images_table.php',
        '2026_11_13_031621_add_label_and_description_to_mi_product_images_table.php',
        '2026_11_13_032509_create_mi_product_image_labels_table.php',
        '2026_10_06_085134_add_unique_item_code_to_mi_products_table.php',
        '2026_10_12_055813_create_mi_product_factory_costs_table.php',
        '2026_11_13_093239_change_mi_factory_cost_currency_to_usd.php',
        '2026_11_03_093224_create_mi_product_price_comparisons_table.php',
        '2026_10_20_082229_add_country_of_production_to_mi_products_table.php',
        '2026_11_03_055655_create_mi_product_variants_table.php',
        '2026_11_03_092310_add_materials_and_colors_to_mi_product_variants_table.php',
    ] as $migration) {
        (require database_path('migrations/'.$migration))->up();
    }
});

/** @return array<string, mixed> */
function miProductEditPayload(MI_Product $product): array
{
    return array_merge($product->only(['item_name', 'category_id', 'sub_category_id', 'type_of_sample', 'materials']), [
        'country_of_production' => $product->country_of_production ?? 'Vietnam',
    ]);
}

it('keeps legacy factory costs editable without silently relabeling PHP amounts', function (?string $currency, string $expectedCurrency) {
    Schema::create('warehouse', function (Blueprint $table): void {
        $table->id('warehouse_id');
        $table->string('warehouse_name');
    });
    Storage::fake('public');
    $product = MI_Product::factory()->create(['product_file' => 'products/spec.pdf']);
    Storage::disk('public')->put('products/spec.pdf', 'saved file');
    $cost = MI_Product_Factory_Cost::factory()->for($product, 'product')->primary()->create([
        'currency' => 'PHP', 'factory_cost' => '123.45',
    ]);
    $this->signInMI($this->miUser());

    $response = $this->get(route('mi_app.edit', $product))->assertOk();
    $document = new DOMDocument;
    $document->loadHTML($response->getContent(), LIBXML_NOERROR | LIBXML_NOWARNING);
    $xpath = new DOMXPath($document);
    expect($xpath->evaluate('string(//select[@name="factory_costs[0][currency]"]/option[@selected]/@value)'))->toBe('PHP');
    $response->assertSee('I confirm this amount was entered in US dollars');

    $row = ['id' => $cost->id, 'factory_name' => $cost->factory_name, 'factory_cost' => '123.45', 'is_primary' => true];
    if ($currency !== null) {
        $row['currency'] = $currency;
    }
    $this->patch(route('mi_app.update', $product), [
        ...miProductEditPayload($product), 'item_name' => 'Unrelated edit', 'factory_costs' => [$row],
    ])->assertRedirect(route('mi_app.edit', $product))->assertSessionHasNoErrors();

    expect($cost->fresh()->only(['currency', 'factory_cost', 'is_primary']))
        ->toBe(['currency' => $expectedCurrency, 'factory_cost' => '123.45', 'is_primary' => true]);
    expect($product->fresh()->item_name)->toBe('Unrelated edit');
    expect($product->fresh()->product_file)->toBe('products/spec.pdf');
    Storage::disk('public')->assertExists('products/spec.pdf');
})->with([
    'keep recorded PHP' => ['PHP', 'PHP'],
    'currency omitted' => [null, 'PHP'],
    'explicitly confirm dollar amount' => ['USD', 'USD'],
]);

it('rejects changing a USD factory cost to PHP', function () {
    $product = MI_Product::factory()->create();
    $cost = MI_Product_Factory_Cost::factory()->for($product, 'product')->create(['currency' => 'USD']);
    $this->signInMI($this->miUser());

    $this->patchJson(route('mi_app.update', $product), [
        ...miProductEditPayload($product),
        'factory_costs' => [['id' => $cost->id, 'factory_name' => $cost->factory_name, 'factory_cost' => '123.45', 'currency' => 'PHP']],
    ])->assertUnprocessable()->assertJsonValidationErrors('factory_costs.0.currency');

    expect($cost->fresh()->currency)->toBe('USD');
    expect($cost->fresh()->factory_cost)->toBe($cost->factory_cost);
});

it('submits the saved factory currency from the edit form and preserves existing uploads', function (?string $storedCurrency) {
    Schema::create('warehouse', function (Blueprint $table): void {
        $table->id('warehouse_id');
        $table->string('warehouse_name');
    });
    Storage::fake('public');
    if ($storedCurrency === null) {
        Schema::table('mi_product_factory_costs', function (Blueprint $table): void {
            $table->enum('currency', ['PHP', 'USD'])->nullable()->default('USD')->change();
        });
    }
    $product = MI_Product::factory()->create(['product_file' => 'products/spec.pdf']);
    $cost = MI_Product_Factory_Cost::factory()->for($product, 'product')->create(['currency' => $storedCurrency]);
    $image = $product->images()->create(['image_type' => 'upload', 'image_path' => 'products/photo.jpg', 'is_primary' => true]);
    Storage::disk('public')->put('products/photo.jpg', 'saved image');
    Storage::disk('public')->put('products/spec.pdf', 'saved file');
    $this->signInMI($this->miUser());

    $response = $this->get(route('mi_app.edit', $product))->assertOk();
    preg_match('/name="factory_costs\[0\]\[currency\]" value="([^"]*)"/', $response->getContent(), $matches);
    expect($matches[1] ?? null)->toBe('USD');
    $response->assertSee('data-factory-currency-label>USD</span>', false);

    $this->patch(route('mi_app.update', $product), [
        ...miProductEditPayload($product),
        'item_name' => 'Updated without replacing uploads',
        'factory_costs' => [[
            'id' => $cost->id, 'factory_name' => $cost->factory_name,
            'factory_cost' => $cost->factory_cost, 'currency' => $matches[1],
        ]],
    ])->assertRedirect(route('mi_app.edit', $product))->assertSessionHasNoErrors();

    expect($product->fresh()->item_name)->toBe('Updated without replacing uploads');
    expect($cost->fresh()->currency)->toBe('USD');
    expect($image->fresh()->image_path)->toBe('products/photo.jpg');
    expect($product->fresh()->product_file)->toBe('products/spec.pdf');
    Storage::disk('public')->assertExists(['products/photo.jpg', 'products/spec.pdf']);
})->with(['recorded USD' => ['USD'], 'legacy missing currency' => [null]]);

it('retains submitted fields and saved uploads after factory currency validation fails', function (string $currency, string $message) {
    Schema::create('warehouse', function (Blueprint $table): void {
        $table->id('warehouse_id');
        $table->string('warehouse_name');
    });
    Storage::fake('public');
    $product = MI_Product::factory()->create(['product_file' => 'products/spec.pdf']);
    $cost = MI_Product_Factory_Cost::factory()->for($product, 'product')->create(['currency' => 'USD']);
    $image = $product->images()->create(['image_type' => 'upload', 'image_path' => 'products/photo.jpg', 'is_primary' => true]);
    Storage::disk('public')->put('products/photo.jpg', 'saved image');
    Storage::disk('public')->put('products/spec.pdf', 'saved file');
    $this->signInMI($this->miUser());

    $this->from(route('mi_app.edit', $product))->patch(route('mi_app.update', $product), [
        ...miProductEditPayload($product),
        'item_name' => 'Keep my unsaved name',
        'remove_image_ids' => [$image->id],
        'factory_costs_present' => '1',
        'factory_costs' => [[
            'id' => $cost->id, 'factory_name' => 'Keep my supplier',
            'factory_cost' => '123.45', 'currency' => $currency,
        ]],
    ])->assertRedirect(route('mi_app.edit', $product))
        ->assertSessionHasErrors(['factory_costs.0.currency' => $message])
        ->assertSessionHasInput('item_name', 'Keep my unsaved name');

    $this->get(route('mi_app.edit', $product))->assertOk()
        ->assertSee('Keep my unsaved name')->assertSee('Keep my supplier')->assertSee('123.45')
        ->assertSee($message)->assertSee('Existing uploaded images and files are kept')
        ->assertSee('name="factory_costs[0][currency]" value="'.$currency.'"', false);
    expect($cost->fresh()->currency)->toBe('USD');
    expect($product->fresh()->item_name)->toBe($product->item_name);
    expect($product->fresh()->product_file)->toBe('products/spec.pdf');
    $this->assertModelExists($image);
    Storage::disk('public')->assertExists(['products/photo.jpg', 'products/spec.pdf']);
})->with([
    'unsupported currency' => ['PHP', 'Select a supported factory cost currency: USD.'],
    'empty currency' => ['', 'Enter a currency for this factory cost.'],
]);

it('renders create media fields with saved and submitted custom image labels', function (array $oldInput) {
    DB::table('mi_product_image_labels')->insert(['label' => 'Detail View', 'created_at' => now(), 'updated_at' => now()]);
    $this->signInMI($this->miUser());

    $response = $this->withSession(['_old_input' => $oldInput])->get(route('mi_app.create'));

    $response->assertOk()->assertSee('Media & Images', false)
        ->assertSee('name="image_links[]"', false)
        ->assertSee('name="image_labels[]"', false)
        ->assertSee('name="image_descriptions[]"', false)
        ->assertSee('name="product_images[]"', false)
        ->assertSee('Detail View')->assertSee('Plate');
    if ($oldInput !== []) {
        $response->assertSee('https://example.com/detail.jpg')
            ->assertSee('value="Unsaved Angle" selected', false)
            ->assertSee('Keep this description');
    }
})->with([
    'fresh form' => [[]],
    'validation redisplay' => [[
        'image_links' => ['https://example.com/detail.jpg'],
        'image_labels' => ['Unsaved Angle'],
        'image_descriptions' => ['Keep this description'],
    ]],
]);

it('creates products with image labels descriptions and uploads and reuses the label', function (string $label) {
    Storage::fake('public');
    $fixture = MI_Product::factory()->create();
    $this->signInMI($this->miUser());

    $this->post(route('mi_app.store_1'), array_replace(miProductEditPayload($fixture), [
        'item_name' => 'Media regression product',
        'image_links' => ['https://example.com/detail.jpg'],
        'image_labels' => [$label],
        'image_descriptions' => ['Detail of the product'],
        'product_images' => [UploadedFile::fake()->image('detail.png')],
    ]))->assertRedirect(route('mi_app.index'))->assertSessionHasNoErrors();

    $product = MI_Product::where('item_name', 'Media regression product')->sole();
    $this->assertDatabaseHas('mi_product_images', [
        'product_id' => $product->getKey(), 'image_url' => 'https://example.com/detail.jpg',
        'label' => $label, 'description' => 'Detail of the product', 'is_primary' => true,
    ]);
    $upload = $product->images()->where('image_type', 'upload')->sole();
    Storage::disk('public')->assertExists($upload->image_path);
    expect($product->images()->count())->toBe(2);
    $this->assertDatabaseHas('mi_product_image_labels', ['label' => $label]);
    $this->get(route('mi_app.create'))->assertOk()->assertSee('value="'.$label.'"', false);
})->with(['standard' => 'Plate', 'custom' => 'Close-up Detail']);

it('displays saved image labels as selected options when editing a product', function () {
    Schema::create('warehouse', function (Blueprint $table): void {
        $table->id('warehouse_id');
        $table->string('warehouse_name');
    });
    DB::table('mi_product_image_labels')->insert(['label' => 'Detail View', 'created_at' => now(), 'updated_at' => now()]);
    $product = MI_Product::factory()->create();
    $product->images()->create(['image_type' => 'url', 'image_url' => 'https://example.com/detail.jpg', 'label' => 'Detail View', 'is_primary' => true]);
    $product->images()->create(['image_type' => 'url', 'image_url' => 'https://example.com/legacy.jpg', 'label' => 'Legacy Angle', 'is_primary' => false]);
    $this->signInMI($this->miUser());

    $this->get(route('mi_app.edit', $product))->assertOk()
        ->assertSee('value="Detail View" selected', false)
        ->assertSee('value="Legacy Angle" selected', false)
        ->assertSee('CAD Drawing')
        ->assertSee('Actual Photo');
});

it('renders inline thumbnails without changing saved media sources', function (string $type, string $source, string $thumbnail) {
    Schema::create('warehouse', function (Blueprint $table): void {
        $table->id('warehouse_id');
        $table->string('warehouse_name');
    });
    $product = MI_Product::factory()->create();
    $field = $type === 'upload' ? 'image_path' : 'image_url';
    $image = $product->images()->create(['image_type' => $type, $field => $source, 'is_primary' => true]);
    $this->signInMI($this->miUser());
    if ($type === 'upload') {
        $thumbnail = Storage::disk('public')->url($source);
    }

    $this->get(route('mi_app.edit', $product))->assertOk()
        ->assertSee('src="'.$thumbnail.'"', false)
        ->assertSee('data-image-preview', false)
        ->assertSee('Image unavailable.')
        ->assertSee('existing_image_labels['.$image->id.']', false)
        ->assertSee('name="image_order[]" value="'.$image->id.'"', false);
    expect($image->fresh()->getAttribute($field))->toBe($source);
})->with([
    'direct URL' => ['url', 'https://example.com/photo.jpg', 'https://example.com/photo.jpg'],
    'Drive sharing page' => ['url', 'https://drive.google.com/file/d/photo-123/view?usp=sharing', 'https://drive.google.com/thumbnail?id=photo-123&amp;sz=w1600'],
    'Drive open link' => ['url', 'https://drive.google.com/open?id=photo-123', 'https://drive.google.com/thumbnail?id=photo-123&amp;sz=w1600'],
    'uploaded image' => ['upload', 'products/photo.jpg', '/storage/products/photo.jpg'],
]);

it('renders product 139 with saved values custom attributes and image identities', function () {
    $product = MI_Product::factory()->create(['product_id' => 139, 'materials' => ['Custom timber'], 'color' => ['Custom color']]);
    $image = $product->images()->create(['image_type' => 'url', 'image_url' => 'https://example.com/original.jpg', 'is_primary' => true]);
    $this->signInMI($this->miUser());

    $this->get(route('mi_app.edit', $product))->assertOk()->assertSee('Edit Product')->assertSee($product->item_name)
        ->assertSee('Custom timber')->assertSee('Custom color')->assertSee('Existing product description')
        ->assertSee('https://example.com/original.jpg')->assertSee('data-image-id="'.$image->id.'"', false)
        ->assertSee('name="purchase_cost"', false)->assertSee('value="12.34"', false);
    expect($image->product->getKey())->toBe(139);
});

it('shows the authorized AI Assistant launcher on MI Product Masterlist and Create Product pages', function () {
    $user = $this->miUser();
    $user->givePermissionTo(Permission::findOrCreate('ai.assistant.use', 'web'));
    $this->signInMI($user);

    $this->get(route('mi_app.index'))
        ->assertOk()
        ->assertSee('AI Assistant')
        ->assertSee('data-ai-assistant', false);

    $this->get(route('mi_app.create'))
        ->assertOk()
        ->assertSee('AI Assistant')
        ->assertSee('data-ai-assistant', false)
        ->assertSee('Reference link (optional)')
        ->assertSee('id="item_name"', false)
        ->assertDontSee('comparison_product_name');
});

it('preserves omitted attributes every image identity and generated values during an ordinary save', function () {
    Storage::fake('public');
    $product = MI_Product::factory()->create(['sku' => 'HD-IN-COL-139', 'product_file' => 'product_images/legacy.pdf']);
    Storage::disk('public')->put('product_images/old.png', 'old image');
    $url = $product->images()->create(['image_type' => 'url', 'image_url' => 'https://example.com/old.jpg', 'is_primary' => true, 'sort_order' => 0]);
    $upload = $product->images()->create(['image_type' => 'upload', 'image_path' => 'product_images/old.png', 'is_primary' => false, 'sort_order' => 1]);
    $this->signInMI($this->miUser());

    $this->put(route('mi_app.update', $product), array_replace(miProductEditPayload($product), ['item_name' => 'Updated name', 'status' => 'Deleted', 'sku' => 'TAMPERED', 'product_id' => 999]))
        ->assertRedirect(route('mi_app.edit', $product))->assertSessionHas('success');

    $this->assertDatabaseHas('mi_products', ['product_id' => $product->getKey(), 'item_name' => 'Updated name', 'sku' => 'HD-IN-COL-139', 'status' => 'Active', 'description' => 'Existing product description', 'product_file' => 'product_images/legacy.pdf']);
    expect($product->fresh()->color)->toBe(['Natural']);
    expect($product->fresh()->purchase_cost)->toBe('12.34');
    expect($product->images()->pluck('id')->all())->toBe([$url->id, $upload->id]);
    $this->assertDatabaseCount('mi_products', 1);
    Storage::disk('public')->assertExists('product_images/old.png');
});

it('saves create fields through PATCH and keeps the current product code valid', function () {
    $product = MI_Product::factory()->create();
    $this->signInMI($this->miUser());
    $payload = array_replace(miProductEditPayload($product), [
        'item_code' => $product->item_code, 'description' => 'Revised description', 'type' => 'Outdoor',
        'purchase_cost' => '44.12', 'price' => null, 'product_height' => null, 'color' => ['Blue'],
    ]);

    $this->patch(route('mi_app.update', $product), $payload)->assertRedirect(route('mi_app.edit', $product))->assertSessionHasNoErrors();

    $this->assertDatabaseHas('mi_products', ['product_id' => $product->getKey(), 'description' => 'Revised description', 'type' => 'Outdoor', 'purchase_cost' => '44.12', 'price' => null, 'product_height' => null]);
    expect($product->fresh()->color)->toBe(['Blue']);
});

it('clears all colors when the edit form submits the empty hidden fallback', function () {
    Schema::create('warehouse', function (Blueprint $table): void {
        $table->id('warehouse_id');
        $table->string('warehouse_name');
    });
    $product = MI_Product::factory()->create(['color' => ['Custom color']]);
    $this->signInMI($this->miUser());

    $this->put(route('mi_app.update', $product), array_replace(miProductEditPayload($product), ['color' => '']))
        ->assertRedirect(route('mi_app.edit', $product))->assertSessionHasNoErrors();

    expect($product->fresh()->color)->toBe([]);
    $response = $this->get(route('mi_app.edit', $product));
    $document = new DOMDocument;
    $document->loadHTML($response->getContent(), LIBXML_NOERROR | LIBXML_NOWARNING);
    $xpath = new DOMXPath($document);
    expect($xpath->query('//select[@id="color"]')->length)->toBe(1);
    expect($xpath->query('//select[@id="color"]//option[@selected]')->length)->toBe(0);
});

it('rejects clearing all materials and redisplays the empty selection without changing saved attributes or images', function () {
    Schema::create('warehouse', function (Blueprint $table): void {
        $table->id('warehouse_id');
        $table->string('warehouse_name');
    });
    $product = MI_Product::factory()->create(['materials' => ['Custom timber'], 'color' => ['Custom color']]);
    $image = $product->images()->create(['image_type' => 'url', 'image_url' => 'https://example.com/original.jpg', 'is_primary' => true]);
    $this->signInMI($this->miUser());

    $this->from(route('mi_app.edit', $product))->put(route('mi_app.update', $product), array_replace(miProductEditPayload($product), [
        'item_name' => 'Rejected product name', 'materials' => '', 'color' => '', 'remove_image_ids' => [$image->id],
    ]))->assertRedirect(route('mi_app.edit', $product))
        ->assertSessionHasErrors(['materials' => 'The materials field is required when variants is not present.'])
        ->assertSessionHas('_old_input', fn (array $input): bool => array_key_exists('materials', $input) && $input['materials'] === null);

    $response = $this->get(route('mi_app.edit', $product))->assertSee('The materials field is required when variants is not present.');
    $document = new DOMDocument;
    $document->loadHTML($response->getContent(), LIBXML_NOERROR | LIBXML_NOWARNING);
    $xpath = new DOMXPath($document);
    expect($xpath->query('//select[@id="materials"]')->length)->toBe(1);
    expect($xpath->query('//select[@id="materials"]//option[@selected]')->length)->toBe(0);
    $this->assertDatabaseHas('mi_products', ['product_id' => $product->getKey(), 'item_name' => $product->item_name]);
    expect($product->fresh()->materials)->toBe(['Custom timber']);
    expect($product->fresh()->color)->toBe(['Custom color']);
    $this->assertDatabaseHas('mi_product_images', ['id' => $image->id, 'image_url' => 'https://example.com/original.jpg', 'is_primary' => true]);
});

it('adds a URL without duplicating existing or repeated links', function () {
    $product = MI_Product::factory()->create();
    $existing = $product->images()->create(['image_type' => 'url', 'image_url' => 'https://example.com/original.jpg', 'is_primary' => true]);
    $this->signInMI($this->miUser());

    $this->put(route('mi_app.update', $product), array_replace(miProductEditPayload($product), ['image_links' => [$existing->image_url, 'https://example.com/new.jpg', 'https://example.com/new.jpg', '']]))->assertSessionHasNoErrors();

    $this->assertModelExists($existing);
    expect($product->images()->count())->toBe(2);
    expect($product->images()->where('is_primary', true)->pluck('id')->all())->toBe([$existing->id]);
    $this->assertDatabaseHas('mi_product_images', ['product_id' => $product->getKey(), 'image_url' => 'https://example.com/new.jpg', 'sort_order' => 1]);
});

it('stores a new image using a unique filename and selects it as the first primary', function () {
    Storage::fake('public');
    $product = MI_Product::factory()->create();
    $this->signInMI($this->miUser());

    $this->put(route('mi_app.update', $product), array_replace(miProductEditPayload($product), ['product_images' => [UploadedFile::fake()->image('untrusted-name.png')]]))->assertSessionHasNoErrors();

    $image = $product->images()->sole();
    expect($image->image_path)->toStartWith('product_images/')->not->toContain('untrusted-name');
    expect($image->is_primary)->toBeTrue();
    Storage::disk('public')->assertExists($image->image_path);
});

it('changes primary and reorders existing images without creating records', function () {
    $product = MI_Product::factory()->create();
    $first = $product->images()->create(['image_type' => 'url', 'image_url' => 'https://example.com/one.jpg', 'is_primary' => true, 'sort_order' => 0]);
    $second = $product->images()->create(['image_type' => 'url', 'image_url' => 'https://example.com/two.jpg', 'is_primary' => true, 'sort_order' => 1]);
    $this->signInMI($this->miUser());

    $this->put(route('mi_app.update', $product), array_replace(miProductEditPayload($product), ['primary_image_id' => $second->id, 'image_order' => [$second->id, $first->id]]))->assertSessionHasNoErrors();

    expect($product->images()->where('is_primary', true)->pluck('id')->all())->toBe([$second->id]);
    expect($product->images()->pluck('id')->all())->toBe([$second->id, $first->id]);
    $this->assertDatabaseCount('mi_product_images', 2);
});

it('removes only the requested image and safely chooses another primary', function (string $kind) {
    Storage::fake('public');
    $product = MI_Product::factory()->create();
    Storage::disk('public')->put('product_images/remove.png', 'old image');
    $first = $product->images()->create(['image_type' => $kind, 'image_url' => $kind === 'url' ? 'https://example.com/one.jpg' : null, 'image_path' => $kind === 'upload' ? 'product_images/remove.png' : null, 'is_primary' => true, 'sort_order' => 0]);
    $second = $product->images()->create(['image_type' => 'url', 'image_url' => 'https://example.com/two.jpg', 'is_primary' => false, 'sort_order' => 1]);
    $this->signInMI($this->miUser());

    $this->put(route('mi_app.update', $product), array_replace(miProductEditPayload($product), ['remove_image_ids' => [$first->id]]))->assertSessionHasNoErrors();

    $this->assertModelMissing($first);
    expect($product->images()->where('is_primary', true)->pluck('id')->all())->toBe([$second->id]);
    if ($kind === 'upload') {
        Storage::disk('public')->assertMissing('product_images/remove.png');
    }
})->with(['URL' => 'url', 'upload' => 'upload']);

it('rejects another products image identifiers with 422 before any update', function (string $field) {
    $product = MI_Product::factory()->create();
    $other = MI_Product::factory()->create();
    $foreign = $other->images()->create(['image_type' => 'url', 'image_url' => 'https://example.com/foreign.jpg']);
    $this->signInMI($this->miUser());
    $value = $field === 'primary_image_id' ? $foreign->id : [$foreign->id];

    $this->putJson(route('mi_app.update', $product), array_replace(miProductEditPayload($product), ['item_name' => 'Unauthorized change', $field => $value]))
        ->assertUnprocessable()->assertJsonValidationErrors($field === 'primary_image_id' ? $field : $field.'.0');

    expect($product->fresh()->item_name)->toBe($product->item_name);
    $this->assertModelExists($foreign);
})->with(['remove' => 'remove_image_ids', 'primary' => 'primary_image_id', 'reorder' => 'image_order']);

it('rejects a duplicate product code and redisplays the submitted fields and inline errors', function () {
    $product = MI_Product::factory()->create();
    $other = MI_Product::factory()->create();
    $this->signInMI($this->miUser());

    $this->from(route('mi_app.edit', $product))->put(route('mi_app.update', $product), array_replace(miProductEditPayload($product), ['item_name' => 'Submitted name', 'item_code' => $other->item_code]))
        ->assertRedirect(route('mi_app.edit', $product))->assertSessionHasErrors(['item_code' => 'The item code has already been taken.'])->assertSessionHasInput('item_name', 'Submitted name');

    $this->get(route('mi_app.edit', $product))->assertSee('Submitted name')->assertSee('The item code has already been taken.')->assertSee('id="item_code_error"', false);
    expect($product->fresh()->item_code)->toBe($product->item_code);
});

it('rejects invalid upload content and oversized files with 422', function (string $kind) {
    Storage::fake('public');
    $product = MI_Product::factory()->create();
    $this->signInMI($this->miUser());
    $file = $kind === 'oversized' ? UploadedFile::fake()->image('large.png')->size(20481) : UploadedFile::fake()->create('payload.php', 1, 'text/plain');

    $this->putJson(route('mi_app.update', $product), array_replace(miProductEditPayload($product), ['product_images' => [$file]]))->assertUnprocessable()->assertJsonValidationErrors('product_images.0');

    expect($product->images()->count())->toBe(0);
    Storage::disk('public')->assertDirectoryEmpty('product_images');
})->with(['wrong MIME' => 'content', 'over 20 MB' => 'oversized']);

it('rejects unsafe image URLs malformed arrays and negative dimensions with 422', function (array $input, string $field) {
    $product = MI_Product::factory()->create();
    $this->signInMI($this->miUser());

    $this->putJson(route('mi_app.update', $product), array_replace(miProductEditPayload($product), $input))->assertUnprocessable()->assertJsonValidationErrors($field);

    expect($product->fresh()->item_name)->toBe($product->item_name);
})->with([
    'javascript URL' => [['image_links' => ['javascript:alert(1)']], 'image_links.0'],
    'FTP URL' => [['image_links' => ['ftp://example.com/image.jpg']], 'image_links.0'],
    'array instead of URL' => [['image_links' => [['bad']]], 'image_links.0'],
    'non array' => [['image_links' => 'https://example.com/image.jpg'], 'image_links'],
    'negative dimension' => [['product_width' => -1], 'product_width'],
]);

it('rejects inconsistent taxonomy with 422 and preserves the product', function () {
    $product = MI_Product::factory()->create();
    $other = MI_Product::factory()->create();
    $this->signInMI($this->miUser());

    $this->putJson(route('mi_app.update', $product), array_replace(miProductEditPayload($product), ['category_id' => $other->category_id]))
        ->assertUnprocessable()->assertJsonValidationErrors('sub_category_id');

    expect($product->fresh()->category_id)->toBe($product->category_id);
});

it('returns 403 for disallowed roles and company contexts on edit and update', function (string $role, string $company, string $method) {
    $product = MI_Product::factory()->create();
    $cost = MI_Product_Factory_Cost::factory()->for($product, 'product')->create(['factory_cost' => '75.00', 'is_primary' => true]);
    $this->signInMI($this->miUser($role, $company));
    $route = $method === 'get' ? 'mi_app.edit' : 'mi_app.update';

    $this->{$method}(route($route, $product), array_replace(miProductEditPayload($product), ['remove_factory_cost_ids' => [$cost->id]]))->assertForbidden();

    expect($product->fresh()->item_name)->toBe($product->item_name);
    expect($cost->fresh()->factory_cost)->toBe('75.00');
})->with([
    'accounting edit' => ['accounting', 'MI', 'get'], 'accounting update' => ['accounting', 'MI', 'put'],
    'finance edit' => ['finance', 'MI', 'get'], 'finance update' => ['finance', 'MI', 'patch'],
    'wrong company edit' => ['user', 'MMC', 'get'], 'wrong company update' => ['user', 'MMC', 'put'],
]);

it('redirects guests to login and returns 404 for missing products after authentication', function (string $method) {
    $route = $method === 'get' ? 'mi_app.edit' : 'mi_app.update';
    $this->{$method}(route($route, 139))->assertRedirect(route('login'));
    $this->signInMI($this->miUser());

    $this->{$method}(route($route, 139))->assertNotFound();
})->with(['edit' => 'get', 'update' => 'put']);

it('rolls back product image changes and cleans new uploads after a later image save failure', function () {
    Storage::fake('public');
    $product = MI_Product::factory()->create();
    Storage::disk('public')->put('product_images/retained.png', 'original');
    $image = $product->images()->create(['image_type' => 'upload', 'image_path' => 'product_images/retained.png', 'is_primary' => true]);
    $cost = MI_Product_Factory_Cost::factory()->for($product, 'product')->create(['factory_cost' => '75.00', 'is_primary' => true]);
    $this->signInMI($this->miUser());
    MI_Product_Image::creating(function (MI_Product_Image $image): void {
        if ($image->image_type === 'upload') {
            throw new RuntimeException('Internal image database failure');
        }
    });
    try {
        $this->from(route('mi_app.edit', $product))->put(route('mi_app.update', $product), array_replace(miProductEditPayload($product), [
            'item_name' => 'Attempted change', 'remove_image_ids' => [$image->id],
            'image_links' => ['https://example.com/new.jpg'], 'product_images' => [UploadedFile::fake()->image('new.png')],
            'remove_factory_cost_ids' => [$cost->id],
            'factory_costs' => [['factory_name' => 'Attempted new factory', 'factory_cost' => '99.95']],
        ]))->assertRedirect(route('mi_app.edit', $product))->assertSessionHasErrors(['error' => 'Unable to save the product. Your existing data has been preserved. Please try again.']);
    } finally {
        MI_Product_Image::flushEventListeners();
    }

    expect($product->fresh()->item_name)->toBe($product->item_name);
    expect($product->images()->pluck('id')->all())->toBe([$image->id]);
    expect($image->fresh()->is_primary)->toBeTrue();
    expect($product->factoryCosts()->pluck('id')->all())->toBe([$cost->id]);
    expect($cost->fresh()->factory_cost)->toBe('75.00');
    expect($cost->fresh()->is_primary)->toBeTrue();
    Storage::disk('public')->assertExists('product_images/retained.png');
    expect(Storage::disk('public')->allFiles('product_images'))->toBe(['product_images/retained.png']);
});

it('reports concurrent product code conflicts as validation errors and rolls back the update', function () {
    $product = MI_Product::factory()->create();
    $other = MI_Product::factory()->create();
    $this->signInMI($this->miUser());
    MI_Product::updating(function (MI_Product $updating) use ($other): void {
        DB::table('mi_products')->where('product_id', $other->getKey())->update(['item_code' => $updating->item_code]);
    });
    try {
        $this->putJson(route('mi_app.update', $product), array_replace(miProductEditPayload($product), ['item_code' => 'CONCURRENT-CODE', 'item_name' => 'Attempted change']))
            ->assertUnprocessable()->assertJsonValidationErrors('item_code');
    } finally {
        MI_Product::flushEventListeners();
    }

    expect($product->fresh()->item_code)->toBe($product->item_code);
    expect($product->fresh()->item_name)->toBe($product->item_name);
    expect($other->fresh()->item_code)->toBe($other->item_code);
});

it('preserves product fields and existing files when storage refuses a new upload', function () {
    Storage::fake('public');
    $originalDisk = Storage::disk('public');
    $originalDisk->put('product_images/keep.png', 'original');
    $product = MI_Product::factory()->create();
    $image = $product->images()->create(['image_type' => 'upload', 'image_path' => 'product_images/keep.png', 'is_primary' => true]);
    $this->signInMI($this->miUser());
    $failedDisk = Mockery::mock(FilesystemAdapter::class);
    $failedDisk->shouldReceive('putFileAs')->once()->andReturn(false);
    Storage::partialMock()->shouldReceive('disk')->with('public')->andReturn($failedDisk);

    $this->put(route('mi_app.update', $product), array_replace(miProductEditPayload($product), [
        'item_name' => 'Attempted change', 'remove_image_ids' => [$image->id], 'product_images' => [UploadedFile::fake()->image('new.png')],
    ]))->assertSessionHasErrors('error');

    expect($product->fresh()->item_name)->toBe($product->item_name);
    $this->assertModelExists($image);
    $originalDisk->assertExists('product_images/keep.png');
});

it('removes the last image without creating a replacement record', function () {
    $product = MI_Product::factory()->create();
    $image = $product->images()->create(['image_type' => 'url', 'image_url' => 'https://example.com/last.jpg', 'is_primary' => true]);
    $this->signInMI($this->miUser());

    $this->put(route('mi_app.update', $product), array_replace(miProductEditPayload($product), ['remove_image_ids' => [$image->id]]))->assertSessionHasNoErrors();

    expect($product->images()->count())->toBe(0);
});

it('rejects selecting a removed image as primary with 422 before updating records', function () {
    $product = MI_Product::factory()->create();
    $image = $product->images()->create(['image_type' => 'url', 'image_url' => 'https://example.com/last.jpg', 'is_primary' => true]);
    $this->signInMI($this->miUser());

    $this->putJson(route('mi_app.update', $product), array_replace(miProductEditPayload($product), ['remove_image_ids' => [$image->id], 'primary_image_id' => $image->id]))
        ->assertUnprocessable()->assertJsonValidationErrors('primary_image_id');

    $this->assertModelExists($image);
});

it('retains a removed upload file that is referenced by another product', function () {
    Storage::fake('public');
    Storage::disk('public')->put('product_images/shared.png', 'shared');
    $product = MI_Product::factory()->create();
    $other = MI_Product::factory()->create();
    $image = $product->images()->create(['image_type' => 'upload', 'image_path' => 'product_images/shared.png', 'is_primary' => true]);
    $foreign = $other->images()->create(['image_type' => 'upload', 'image_path' => 'product_images/shared.png', 'is_primary' => true]);
    $this->signInMI($this->miUser());

    $this->put(route('mi_app.update', $product), array_replace(miProductEditPayload($product), ['remove_image_ids' => [$image->id]]))->assertSessionHasNoErrors();

    $this->assertModelMissing($image);
    $this->assertModelExists($foreign);
    Storage::disk('public')->assertExists('product_images/shared.png');
});

it('escapes product names descriptions and custom values in the edit form', function () {
    $unsafe = '<script>alert("unsafe")</script>';
    $product = MI_Product::factory()->create(['item_name' => $unsafe, 'description' => $unsafe, 'materials' => [$unsafe]]);
    $this->signInMI($this->miUser());

    $this->get(route('mi_app.edit', $product))->assertSee($unsafe)->assertDontSee($unsafe, false);
});

it('allows the existing executive role variants to edit products', function (string $role) {
    $product = MI_Product::factory()->create();
    $this->signInMI($this->miUser($role));

    $this->put(route('mi_app.update', $product), array_replace(miProductEditPayload($product), ['item_name' => 'Executive update']))->assertSessionHasNoErrors();

    $this->assertDatabaseHas('mi_products', ['product_id' => $product->getKey(), 'item_name' => 'Executive update']);
})->with(['Executive', 'executive']);

it('creates a product with prices factory costs and media then loads its shared options and saves an edit', function () {
    Schema::create('warehouse', function (Blueprint $table): void {
        $table->id('warehouse_id');
        $table->string('warehouse_name');
    });
    Storage::fake('public');
    $fixture = MI_Product::factory()->create();
    $this->signInMI($this->miUser());
    $this->freezeTime();
    $payload = array_replace(miProductEditPayload($fixture), [
        'item_name' => 'New create fixture',
        'purchase_cost' => '42.25',
        'price' => '75.50',
        'factory_costs' => [[
            'factory_name' => 'Create Supplier',
            'factory_cost' => '35.75',
            'currency' => 'USD',
            'is_primary' => '1',
        ]],
        'image_links' => ['https://example.com/create.jpg'],
        'product_images' => [UploadedFile::fake()->image('created.png')],
    ]);

    $this->post(route('mi_app.store_1'), $payload)
        ->assertRedirect(route('mi_app.index'))
        ->assertSessionHasNoErrors()
        ->assertSessionHas('success', 'Product saved successfully!');

    $created = MI_Product::where('item_name', 'New create fixture')->sole();
    $createdFactoryCost = $created->factoryCosts()->sole();
    expect($created->purchase_cost)->toBe('42.25');
    expect($created->price)->toBe('75.50');
    expect($createdFactoryCost->factory_name)->toBe('Create Supplier');
    expect($createdFactoryCost->factory_cost)->toBe('35.75');
    expect($createdFactoryCost->is_primary)->toBeTrue();
    expect($created->sku)->not->toBeEmpty();
    expect($created->draft_number)->toBe('DR-'.now()->format('Y').'-'.str_pad((string) $created->getKey(), 4, '0', STR_PAD_LEFT));
    expect($created->images()->count())->toBe(2);
    expect($created->images()->where('is_primary', true)->count())->toBe(1);
    $this->get(route('mi_app.create'))->assertSee('Acacia Wood')->assertSee('Natural');
    $this->get(route('mi_app.create'))->assertSee('name="purchase_cost"', false)->assertSee('name="price"', false)
        ->assertSee('id="section_factory_costs"', false)->assertSee('data-factory-costs', false);
    $this->get(route('mi_app.edit', $created))->assertSee('Acacia Wood')->assertSee('Natural');
    $this->put(route('mi_app.update', $created), miProductEditPayload($created))->assertSessionHasNoErrors();
    expect($created->images()->count())->toBe(2);
});

it('keeps variant tag selections separate and persists shared options without duplicates', function () {
    Schema::create('warehouse', function (Blueprint $table): void {
        $table->id('warehouse_id');
        $table->string('warehouse_name');
    });
    (require database_path('migrations/2026_11_03_060144_create_mi_product_variant_inventory_tables.php'))->up();
    $product = MI_Product::factory()->create(['sku' => 'TAG-PRODUCT']);
    $first = $product->variants()->create([
        'sequence' => 1, 'sku' => 'TAG-PRODUCT-01', 'material' => 'Oak Wood', 'materials' => [' oak   wood '],
        'color' => 'Blue', 'colors' => [' blue '], 'purchase_price' => '12.50', 'product_length' => '50.00',
    ]);
    $second = $product->variants()->create([
        'sequence' => 2, 'sku' => 'TAG-PRODUCT-02', 'material' => 'Steel', 'materials' => ['Steel'],
        'color' => 'Red', 'colors' => ['Red'], 'selling_price' => '30.00',
    ]);
    $this->signInMI($this->miUser());

    $response = $this->get(route('mi_app.edit', $product))->assertOk();
    if ($prefix = getenv('MI_ATTRIBUTE_FIXTURE_PREFIX')) {
        file_put_contents($prefix.'-edit.html', $response->getContent());
        file_put_contents($prefix.'-create.html', $this->get(route('mi_app.create'))->assertOk()->getContent());
    }
    $this->patch(route('mi_app.update', $product), [
        ...miProductEditPayload($product), 'variants_present' => '1',
        'variants' => [
            ['id' => $first->getKey(), 'sequence' => 1, 'sku' => $first->sku, 'materials' => ['Oak Wood', ' Bamboo   Composite ', 'bamboo composite'], 'colors' => ['Sea Glass', 'sea glass'], 'purchase_price' => '12.50', 'product_length' => '50.00'],
            ['id' => $second->getKey(), 'sequence' => 2, 'sku' => $second->sku, 'materials' => ['Steel'], 'colors' => ['Red'], 'selling_price' => '30.00'],
        ],
    ])->assertSessionHasNoErrors();

    expect($first->fresh()->materials)->toBe(['Oak Wood', 'Bamboo Composite']);
    expect($first->fresh()->colors)->toBe(['Sea Glass']);
    expect($first->fresh()->only(['sku', 'purchase_price', 'product_length']))
        ->toBe(['sku' => 'TAG-PRODUCT-01', 'purchase_price' => '12.50', 'product_length' => '50.00']);
    expect($second->fresh()->only(['materials', 'colors', 'sku', 'selling_price']))
        ->toBe(['materials' => ['Steel'], 'colors' => ['Red'], 'sku' => 'TAG-PRODUCT-02', 'selling_price' => '30.00']);
    expect(DB::table('mi_materials')->where('normalized_name', 'bamboo composite')->count())->toBe(1);
    expect(DB::table('mi_colors')->where('normalized_name', 'sea glass')->count())->toBe(1);
    $this->get(route('mi_app.edit', $product))->assertSee('Bamboo Composite')->assertSee('Sea Glass');
});

it('persists normalized custom materials and colors for reuse across products and edits', function () {
    $fixture = MI_Product::factory()->create();
    $this->signInMI($this->miUser());
    $payload = array_replace(miProductEditPayload($fixture), [
        'item_name' => 'Custom option first product',
        'materials' => [' Bamboo   Composite ', "bAMboo\tcomposite", 'Oak Wood'],
        'color' => [' Sea   Glass ', "sea\tglass", 'Blue'],
    ]);

    $this->post(route('mi_app.store_1'), $payload)->assertRedirect(route('mi_app.index'))->assertSessionHasNoErrors();

    $product = MI_Product::where('item_name', 'Custom option first product')->sole();
    expect($product->materials)->toBe(['Bamboo Composite', 'Oak Wood']);
    expect($product->color)->toBe(['Sea Glass', 'Blue']);
    $material = DB::table('mi_materials')->where('normalized_name', 'bamboo composite')->sole();
    $color = DB::table('mi_colors')->where('normalized_name', 'sea glass')->sole();
    expect(DB::table('mi_materials')->where('normalized_name', 'bamboo composite')->count())->toBe(1);
    expect(DB::table('mi_colors')->where('normalized_name', 'sea glass')->count())->toBe(1);
    $this->get(route('mi_app.index'))->assertSee('Product saved successfully!');
    $this->get(route('mi_app.create'))->assertSee('Bamboo Composite')->assertSee('Sea Glass');

    $secondPayload = array_replace(miProductEditPayload($fixture), [
        'item_name' => 'Custom option second product',
        'materials' => ['BAMBOO COMPOSITE'],
        'color' => ['SEA GLASS'],
    ]);
    $this->post(route('mi_app.store_1'), $secondPayload)->assertSessionHasNoErrors();
    $second = MI_Product::where('item_name', 'Custom option second product')->sole();
    expect($second->materials)->toBe(['Bamboo Composite']);
    expect($second->color)->toBe(['Sea Glass']);

    $this->put(route('mi_app.update', $product), array_replace(miProductEditPayload($product), [
        'materials' => [' Oak Wood '], 'color' => [],
    ]))->assertSessionHasNoErrors();
    expect($product->fresh()->materials)->toBe(['Oak Wood']);
    expect($product->fresh()->color)->toBe([]);
    $this->assertDatabaseHas('mi_materials', ['id' => $material->id, 'material_name' => 'Bamboo Composite']);
    $this->assertDatabaseHas('mi_colors', ['id' => $color->id, 'color_name' => 'Sea Glass']);
});

it('enforces normalized material and color uniqueness in the database', function () {
    DB::table('mi_materials')->insert(['material_name' => 'Oak Wood', 'normalized_name' => 'oak wood', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()]);
    DB::table('mi_colors')->insert(['color_name' => 'Sea Glass', 'normalized_name' => 'sea glass', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()]);

    expect(fn () => DB::table('mi_materials')->insert(['material_name' => 'OAK WOOD', 'normalized_name' => 'oak wood', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()]))->toThrow(QueryException::class);
    expect(fn () => DB::table('mi_colors')->insert(['color_name' => 'SEA GLASS', 'normalized_name' => 'sea glass', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()]))->toThrow(QueryException::class);
});

it('rolls back new options when the product insert fails', function () {
    $fixture = MI_Product::factory()->create();
    $this->signInMI($this->miUser());
    DB::listen(static function (QueryExecuted $query): void {
        if (str_starts_with(strtolower($query->sql), 'insert into "mi_products"')) {
            throw new RuntimeException('Simulated product insert failure.');
        }
    });
    $payload = array_replace(miProductEditPayload($fixture), [
        'item_name' => 'Rolled back product',
        'materials' => ['Rolled Back Timber'],
        'color' => ['Rolled Back Blue'],
    ]);

    $this->from(route('mi_app.create'))->post(route('mi_app.store_1'), $payload)->assertRedirect(route('mi_app.create'))->assertSessionHasErrors('error');

    expect(MI_Product::where('item_name', 'Rolled back product')->exists())->toBeFalse();
    expect(DB::table('mi_materials')->where('normalized_name', 'rolled back timber')->exists())->toBeFalse();
    expect(DB::table('mi_colors')->where('normalized_name', 'rolled back blue')->exists())->toBeFalse();
});

it('rejects multiple primary factory costs when creating a product', function () {
    $fixture = MI_Product::factory()->create();
    $this->signInMI($this->miUser());
    $payload = array_replace(miProductEditPayload($fixture), [
        'item_name' => 'Rejected create fixture',
        'factory_costs' => [
            ['factory_name' => 'First Supplier', 'factory_cost' => '35.75', 'is_primary' => '1'],
            ['factory_name' => 'Second Supplier', 'factory_cost' => '36.25', 'is_primary' => '1'],
        ],
    ]);

    $this->from(route('mi_app.create'))->post(route('mi_app.store_1'), $payload)
        ->assertRedirect(route('mi_app.create'))
        ->assertSessionHasErrors('factory_costs');

    expect(MI_Product::where('item_name', 'Rejected create fixture')->exists())->toBeFalse();
});

it('stops the new index migration on duplicate legacy codes without altering product data', function () {
    $migration = require database_path('migrations/2026_10_06_085134_add_unique_item_code_to_mi_products_table.php');
    $migration->down();
    $first = MI_Product::factory()->create(['item_code' => 'LEGACY-DUPLICATE']);
    $second = MI_Product::factory()->create(['item_code' => 'LEGACY-DUPLICATE']);

    expect(fn () => $migration->up())->toThrow(RuntimeException::class, 'MI product codes contain duplicates.');

    expect($first->fresh()->item_code)->toBe('LEGACY-DUPLICATE');
    expect($second->fresh()->item_code)->toBe('LEGACY-DUPLICATE');
    $this->assertDatabaseCount('mi_products', 2);
});

it('shows the selected primary image first in the internal and scanned product galleries', function () {
    $product = MI_Product::factory()->create();
    $product->images()->create(['image_type' => 'url', 'image_url' => 'https://example.com/secondary.jpg', 'is_primary' => false, 'sort_order' => 0]);
    $product->images()->create(['image_type' => 'url', 'image_url' => 'https://example.com/primary.jpg', 'is_primary' => true, 'sort_order' => 1]);
    $this->signInMI($this->miUser());

    $internal = $this->get(route('mi_app.show', $product))->assertOk();
    $public = $this->get(route('mi_app.scan', ['code' => $product->getKey()]))->assertOk();

    expect($internal->getContent())->toMatch('/id="galleryPreview"\s+src="https:\/\/example\.com\/primary\.jpg"/');
    expect($public->getContent())->toMatch('/id="pdGalleryImg"\s+src="https:\/\/example\.com\/primary\.jpg"/');
});

it('creates products with a standard or custom country of production and requires a country name for other', function () {
    $fixture = MI_Product::factory()->create();
    $this->signInMI($this->miUser());
    $payload = array_replace(miProductEditPayload($fixture), ['item_name' => 'Custom COP product', 'country_of_production' => 'Other', 'custom_country' => 'Japan']);

    $this->post(route('mi_app.store_1'), $payload)->assertRedirect(route('mi_app.index'))->assertSessionHasNoErrors();
    $this->assertDatabaseHas('mi_products', ['item_name' => 'Custom COP product', 'country_of_production' => 'Japan']);

    $this->from(route('mi_app.create'))->post(route('mi_app.store_1'), array_replace($payload, ['item_name' => 'Missing custom COP', 'custom_country' => '']))
        ->assertRedirect(route('mi_app.create'))->assertSessionHasErrors('custom_country');
    expect(MI_Product::where('item_name', 'Missing custom COP')->exists())->toBeFalse();
    $missingCountryPayload = array_replace($payload, ['item_name' => 'Missing COP']);
    unset($missingCountryPayload['country_of_production'], $missingCountryPayload['custom_country']);
    $this->from(route('mi_app.create'))->post(route('mi_app.store_1'), $missingCountryPayload)
        ->assertRedirect(route('mi_app.create'))->assertSessionHasErrors('country_of_production');
    expect(MI_Product::where('item_name', 'Missing COP')->exists())->toBeFalse();
    $this->get(route('mi_app.create'))->assertSee('name="country_of_production" required', false)
        ->assertSee('value="Vietnam"', false)->assertSee('value="China"', false)->assertSee('value="Other"', false)
        ->assertSee('name="custom_country"', false);
});

it('requires a country on product updates and saves custom country names', function () {
    $product = MI_Product::factory()->create(['country_of_production' => 'Vietnam']);
    $this->signInMI($this->miUser());

    $this->from(route('mi_app.edit', $product))->put(route('mi_app.update', $product), array_replace(miProductEditPayload($product), [
        'item_name' => 'Rejected COP update', 'country_of_production' => 'Other', 'custom_country' => '',
    ]))->assertRedirect(route('mi_app.edit', $product))->assertSessionHasErrors('custom_country');
    $this->assertDatabaseHas('mi_products', ['product_id' => $product->getKey(), 'item_name' => $product->item_name, 'country_of_production' => 'Vietnam']);

    $this->put(route('mi_app.update', $product), array_replace(miProductEditPayload($product), ['country_of_production' => 'Other', 'custom_country' => 'Japan']))
        ->assertRedirect(route('mi_app.edit', $product))->assertSessionHasNoErrors();
    $this->assertDatabaseHas('mi_products', ['product_id' => $product->getKey(), 'country_of_production' => 'Japan']);
});

it('shows unset COP on legacy products and requires a country when they are next edited', function () {
    $product = MI_Product::factory()->create(['country_of_production' => null]);
    $this->signInMI($this->miUser());

    $this->get(route('mi_app.show', $product))->assertOk()->assertSee('COP')->assertSee('Not Set');
    $this->get(route('mi_app.edit', $product))->assertOk()->assertSee('COP')->assertSee('value=""', false);
    $legacyPayload = miProductEditPayload($product);
    unset($legacyPayload['country_of_production']);
    $this->from(route('mi_app.edit', $product))->put(route('mi_app.update', $product), $legacyPayload)
        ->assertRedirect(route('mi_app.edit', $product))->assertSessionHasErrors('country_of_production');
    $this->put(route('mi_app.update', $product), array_replace(miProductEditPayload($product), ['country_of_production' => 'China']))
        ->assertRedirect(route('mi_app.edit', $product))->assertSessionHasNoErrors();
    $this->assertDatabaseHas('mi_products', ['product_id' => $product->getKey(), 'country_of_production' => 'China']);
});

it('filters MI products by standard custom and unset COP while retaining other query filters', function () {
    MI_Product::factory()->create(['item_name' => 'Vietnam fixture', 'country_of_production' => 'Vietnam']);
    MI_Product::factory()->create(['item_name' => 'China fixture', 'country_of_production' => 'China']);
    MI_Product::factory()->create(['item_name' => 'Japan fixture', 'country_of_production' => 'Japan']);
    MI_Product::factory()->create(['item_name' => 'Unset fixture', 'country_of_production' => null]);
    $this->signInMI($this->miUser());

    $this->get(route('mi_app.index', ['country_of_production' => 'Japan', 'status' => 'Active']))
        ->assertOk()->assertSee('Japan fixture')->assertDontSee('Vietnam fixture')->assertDontSee('China fixture')
        ->assertSee('name="country_of_production"', false)->assertSee('value="Japan" selected', false);
    $this->get(route('mi_app.index', ['country_of_production' => '__not_set']))
        ->assertOk()->assertSee('Unset fixture')->assertDontSee('Japan fixture');
});

it('marks null empty and whitespace COP values with a warning and direct edit links', function () {
    $missingProducts = collect([null, '', '   '])->map(fn (?string $country, int $index): MI_Product => MI_Product::factory()->create([
        'item_name' => 'Missing COP '.$index,
        'country_of_production' => $country,
    ]));
    $completeProduct = MI_Product::factory()->create(['item_name' => 'Complete COP', 'country_of_production' => 'Japan']);
    $this->signInMI($this->miUser());

    $response = $this->get(route('mi_app.index'))->assertOk()->assertSee('Needs COP Update')->assertSee('Japan');
    foreach ($missingProducts as $product) {
        $response->assertSee(route('mi_app.edit', $product));
    }
    $response->assertSee(route('mi_app.edit', $completeProduct));
    expect(substr_count($response->getContent(), 'class="tx-cop-state missing"'))->toBe(3)
        ->and(substr_count($response->getContent(), 'class="tx-cop-state complete"'))->toBe(1);
});

it('filters all missing COP values and shows their count while keeping existing filters', function () {
    MI_Product::factory()->create(['item_name' => 'Unset COP', 'country_of_production' => null]);
    MI_Product::factory()->create(['item_name' => 'Empty COP', 'country_of_production' => '']);
    MI_Product::factory()->create(['item_name' => 'Whitespace COP', 'country_of_production' => '   ']);
    MI_Product::factory()->create(['item_name' => 'Known COP', 'country_of_production' => 'Vietnam']);
    $this->signInMI($this->miUser());

    $this->get(route('mi_app.index', ['missing_cop' => '1', 'status' => 'Active']))
        ->assertOk()->assertSee('Unset COP')->assertSee('Empty COP')->assertSee('Whitespace COP')->assertDontSee('Known COP')
        ->assertSee('3 products need COP updates')->assertSee('Missing COP (3)')
        ->assertSee('name="status"', false)->assertSee('name="country_of_production"', false);
});

it('replaces a missing COP warning with a green country indicator after save', function () {
    $product = MI_Product::factory()->create(['country_of_production' => null]);
    $this->signInMI($this->miUser());
    $this->get(route('mi_app.index'))->assertSee('Needs COP Update');

    $this->put(route('mi_app.update', $product), array_replace(miProductEditPayload($product), ['country_of_production' => 'China']))
        ->assertRedirect(route('mi_app.edit', $product))->assertSessionHasNoErrors();

    $this->assertDatabaseHas('mi_products', ['product_id' => $product->getKey(), 'country_of_production' => 'China']);
    $this->get(route('mi_app.index'))->assertDontSee('Needs COP Update')->assertSee('class="tx-cop-state complete"', false)->assertSee('China');
});

it('keeps the product list available while the additive COP migration is pending', function () {
    MI_Product::factory()->count(2)->create();
    Schema::table('mi_products', function (Blueprint $table): void {
        $table->dropColumn('country_of_production');
    });
    $this->signInMI($this->miUser());

    $this->get(route('mi_app.index'))
        ->assertOk()->assertSee('2 products need COP updates')->assertSee('Needs COP Update');
});

it('shows every factory and product prices on the detail page', function () {
    $product = MI_Product::factory()->create(['purchase_cost' => '88.25', 'price' => '120.50']);
    MI_Product_Factory_Cost::factory()->for($product, 'product')->create([
        'factory_name' => 'Other Factory',
        'factory_cost' => '61.11',
    ]);
    MI_Product_Factory_Cost::factory()->for($product, 'product')->primary()->create([
        'factory_name' => 'Selected Factory',
        'factory_cost' => '63.75',
    ]);
    $this->signInMI($this->miUser());

    $factoryQueries = 0;
    DB::listen(function (QueryExecuted $query) use (&$factoryQueries): void {
        if (str_contains($query->sql, 'mi_product_factory_costs')) {
            $factoryQueries++;
        }
    });

    $this->get(route('mi_app.show', $product))
        ->assertSee('Factory Information')
        ->assertSee('Selected Factory')
        ->assertSee('Other Factory')
        ->assertSee('63.75')
        ->assertSee('61.11')
        ->assertSee('Primary')
        ->assertSee('Country / Location')
        ->assertSee('Not recorded')
        ->assertSee('Purchase Price')
        ->assertSee('PHP 88.25')
        ->assertSee('Selling Price')
        ->assertSee('PHP 120.50')
        ->assertDontSee('Primary Factory Cost');

    expect($factoryQueries)->toBe(1);
});

it('shows factory information empty state when a product has no linked factories', function () {
    $product = MI_Product::factory()->create(['purchase_cost' => '88.25', 'price' => '120.50']);
    $this->signInMI($this->miUser());

    $this->get(route('mi_app.show', $product))
        ->assertOk()
        ->assertSee('Factory Information')
        ->assertSee('No factory information has been recorded for this product.')
        ->assertSee('Purchase Price')
        ->assertSee('PHP 88.25')
        ->assertSee('Selling Price')
        ->assertSee('PHP 120.50');
});

it('shows a single non-primary factory with its stored currency and cost', function () {
    $product = MI_Product::factory()->create();
    MI_Product_Factory_Cost::factory()->for($product, 'product')->create([
        'factory_name' => 'Single Supplier',
        'factory_cost' => '0.00',
        'currency' => 'USD',
    ]);
    $this->signInMI($this->miUser());

    $this->get(route('mi_app.show', $product))
        ->assertOk()
        ->assertSee('Single Supplier')
        ->assertSee('0.00')
        ->assertSee('USD')
        ->assertSee('1 factory')
        ->assertDontSee('Primary');
});

it('adds the first factory cost as primary without changing legacy cost or selling price', function (string $amount, string $storedAmount) {
    $product = MI_Product::factory()->create();
    $this->signInMI($this->miUser());

    $this->put(route('mi_app.update', $product), array_replace(miProductEditPayload($product), [
        'factory_costs' => [['factory_name' => 'First supplier', 'factory_cost' => $amount]],
    ]))->assertRedirect(route('mi_app.edit', $product))->assertSessionHasNoErrors()->assertSessionHas('success');

    $cost = $product->factoryCosts()->sole();
    expect($cost->factory_name)->toBe('First supplier');
    expect($cost->factory_cost)->toBe($storedAmount);
    expect($cost->is_primary)->toBeTrue();
    expect($cost->currency)->toBe('USD');
    expect($cost->effective_date)->toBeNull();
    expect($cost->remarks)->toBeNull();
    expect($cost->product->getKey())->toBe($product->getKey());
    expect($product->fresh()->purchase_cost)->toBe('12.34');
    expect($product->fresh()->price)->toBe('25.50');
})->with([
    'zero' => ['0', '0.00'],
    'one decimal place' => ['12.3', '12.30'],
    'large decimal amount' => ['999999999999.99', '999999999999.99'],
]);

it('saves multiple factory costs and reloads every editable value with the selected primary', function () {
    Schema::create('warehouse', function (Blueprint $table): void {
        $table->id('warehouse_id');
        $table->string('warehouse_name');
    });
    $product = MI_Product::factory()->create();
    $this->signInMI($this->miUser());

    $this->patch(route('mi_app.update', $product), array_replace(miProductEditPayload($product), [
        'factory_costs' => [
            ['factory_name' => 'Factory A', 'factory_cost' => '1250.00', 'currency' => 'USD', 'effective_date' => '2026-10-12', 'remarks' => 'Includes packing'],
            ['factory_name' => 'Factory B', 'factory_cost' => '1180.00', 'is_primary' => true],
            ['factory_name' => 'Factory C', 'factory_cost' => '1350.00'],
        ],
    ]))->assertRedirect(route('mi_app.edit', $product))->assertSessionHasNoErrors();

    $this->assertDatabaseCount('mi_product_factory_costs', 3);
    $this->assertDatabaseHas('mi_product_factory_costs', [
        'product_id' => $product->getKey(), 'factory_name' => 'Factory A', 'factory_cost' => '1250.00',
        'currency' => 'USD', 'remarks' => 'Includes packing', 'is_primary' => false,
    ]);
    expect($product->factoryCosts()->where('factory_name', 'Factory A')->sole()->effective_date->format('Y-m-d'))->toBe('2026-10-12');
    expect($product->factoryCosts()->where('is_primary', true)->pluck('factory_name')->all())->toBe(['Factory B']);
    $response = $this->get(route('mi_app.edit', $product))->assertSee('Factory Cost Management')
        ->assertSee('Includes packing')->assertSee('2026-10-12');
    foreach ($product->factoryCosts as $cost) {
        $response->assertSee($cost->factory_name)->assertSee('value="'.$cost->factory_cost.'"', false)
            ->assertSee('data-factory-cost-id="'.$cost->id.'"', false);
    }
});

it('updates a saved factory cost and switches primary while preserving omitted entries', function () {
    $product = MI_Product::factory()->create();
    $original = MI_Product_Factory_Cost::factory()->for($product, 'product')->primary()->create(['factory_cost' => '1250.00']);
    $selected = MI_Product_Factory_Cost::factory()->for($product, 'product')->create(['effective_date' => '2026-10-01', 'remarks' => 'Old remarks']);
    $this->signInMI($this->miUser());

    $this->patch(route('mi_app.update', $product), array_replace(miProductEditPayload($product), [
        'factory_costs' => [[
            'id' => $selected->id, 'factory_name' => 'Renamed supplier', 'factory_cost' => '1125.25',
            'effective_date' => null, 'remarks' => null, 'is_primary' => true,
        ]],
        'purchase_cost' => '40.00', 'price' => '95.00',
    ]))->assertRedirect(route('mi_app.edit', $product))->assertSessionHasNoErrors();

    $this->assertDatabaseCount('mi_product_factory_costs', 2);
    $this->assertDatabaseHas('mi_product_factory_costs', [
        'id' => $selected->id, 'factory_name' => 'Renamed supplier', 'factory_cost' => '1125.25',
        'effective_date' => null, 'remarks' => null, 'is_primary' => true,
    ]);
    expect($original->fresh()->factory_cost)->toBe('1250.00');
    expect($product->factoryCosts()->where('is_primary', true)->pluck('id')->all())->toBe([$selected->id]);
    expect($product->fresh()->purchase_cost)->toBe('40.00');
    expect($product->fresh()->price)->toBe('95.00');
});

it('preserves every saved factory cost when the request omits entries without explicitly removing them', function (array $input) {
    $product = MI_Product::factory()->create();
    $primary = MI_Product_Factory_Cost::factory()->for($product, 'product')->primary()->create();
    $secondary = MI_Product_Factory_Cost::factory()->for($product, 'product')->create();
    $this->signInMI($this->miUser());

    $this->put(route('mi_app.update', $product), array_replace(miProductEditPayload($product), $input))
        ->assertSessionHasNoErrors();

    $this->assertModelExists($primary);
    $this->assertModelExists($secondary);
    expect($product->factoryCosts()->count())->toBe(2);
    expect($primary->fresh()->is_primary)->toBeTrue();
})->with(['omitted section' => [[]], 'empty section' => [['factory_costs' => []]]]);

it('removes a saved primary factory cost and selects a remaining cost', function () {
    $product = MI_Product::factory()->create();
    $primary = MI_Product_Factory_Cost::factory()->for($product, 'product')->primary()->create();
    $remaining = MI_Product_Factory_Cost::factory()->for($product, 'product')->create();
    $this->signInMI($this->miUser());

    $this->put(route('mi_app.update', $product), array_replace(miProductEditPayload($product), [
        'remove_factory_cost_ids' => [$primary->id],
    ]))->assertSessionHasNoErrors();

    $this->assertModelMissing($primary);
    expect($product->factoryCosts()->where('is_primary', true)->pluck('id')->all())->toBe([$remaining->id]);
    expect($product->fresh()->purchase_cost)->toBe('12.34');
    expect($product->fresh()->price)->toBe('25.50');
});

it('removes the last factory cost without recreating it from the legacy purchase cost', function () {
    Schema::create('warehouse', function (Blueprint $table): void {
        $table->id('warehouse_id');
        $table->string('warehouse_name');
    });
    $product = MI_Product::factory()->create();
    $cost = MI_Product_Factory_Cost::factory()->for($product, 'product')->primary()->create();
    $this->signInMI($this->miUser());

    $this->put(route('mi_app.update', $product), array_replace(miProductEditPayload($product), [
        'remove_factory_cost_ids' => [$cost->id],
    ]))->assertSessionHasNoErrors();

    $this->assertDatabaseCount('mi_product_factory_costs', 0);
    $this->get(route('mi_app.edit', $product))->assertSee('Factory Cost Management');
    expect($product->factoryCosts()->count())->toBe(0);
    expect($product->fresh()->purchase_cost)->toBe('12.34');
});

it('rejects invalid factory costs with 422 before changing the product', function (mixed $amount) {
    $product = MI_Product::factory()->create();
    $this->signInMI($this->miUser());

    $this->putJson(route('mi_app.update', $product), array_replace(miProductEditPayload($product), [
        'item_name' => 'Rejected product name',
        'factory_costs' => [['factory_name' => 'Factory A', 'factory_cost' => $amount]],
    ]))->assertUnprocessable()->assertJsonValidationErrors('factory_costs.0.factory_cost');

    expect($product->fresh()->item_name)->toBe($product->item_name);
    $this->assertDatabaseCount('mi_product_factory_costs', 0);
})->with([
    'negative' => ['-0.01'], 'non-numeric' => ['not a price'], 'too precise' => ['1.001'],
    'overflow' => ['10000000000000.00'], 'scientific notation' => ['1e3'], 'formatted amount' => ['1,250.00'],
    'array' => [['12.50']], 'missing value' => [null],
]);

it('rejects malformed factory cost entries and unsupported metadata with 422', function (array $input, string $field) {
    $product = MI_Product::factory()->create();
    $this->signInMI($this->miUser());

    $this->putJson(route('mi_app.update', $product), array_replace(miProductEditPayload($product), $input))
        ->assertUnprocessable()->assertJsonValidationErrors($field);

    $this->assertDatabaseCount('mi_product_factory_costs', 0);
})->with([
    'non array list' => [['factory_costs' => 'Factory A'], 'factory_costs'],
    'non array entry' => [['factory_costs' => ['Factory A']], 'factory_costs.0'],
    'missing required fields' => [['factory_costs' => [[]]], 'factory_costs.0.factory_name'],
    'unsupported currency' => [['factory_costs' => [['factory_name' => 'Factory A', 'factory_cost' => '12.00', 'currency' => 'PHP']]], 'factory_costs.0.currency'],
    'invalid effective date' => [['factory_costs' => [['factory_name' => 'Factory A', 'factory_cost' => '12.00', 'effective_date' => '2026-02-30']]], 'factory_costs.0.effective_date'],
    'invalid primary indicator' => [['factory_costs' => [['factory_name' => 'Factory A', 'factory_cost' => '12.00', 'is_primary' => 'yes']]], 'factory_costs.0.is_primary'],
    'mass assignment key' => [['factory_costs' => [['factory_name' => 'Factory A', 'factory_cost' => '12.00', 'product_id' => 999]]], 'factory_costs.0'],
    'non array removals' => [['remove_factory_cost_ids' => '1'], 'remove_factory_cost_ids'],
]);

it('rejects another products factory cost identifiers with 422 before changing either product', function (string $operation) {
    $product = MI_Product::factory()->create();
    $other = MI_Product::factory()->create();
    $foreign = MI_Product_Factory_Cost::factory()->for($other, 'product')->primary()->create(['factory_cost' => '75.00']);
    $this->signInMI($this->miUser());
    $input = $operation === 'update'
        ? ['factory_costs' => [['id' => $foreign->id, 'factory_name' => 'Stolen supplier', 'factory_cost' => '1.00']]]
        : ['remove_factory_cost_ids' => [$foreign->id]];
    $field = $operation === 'update' ? 'factory_costs.0.id' : 'remove_factory_cost_ids.0';

    $this->putJson(route('mi_app.update', $product), array_replace(miProductEditPayload($product), ['item_name' => 'Unauthorized change'], $input))
        ->assertUnprocessable()->assertJsonValidationErrors($field);

    expect($product->fresh()->item_name)->toBe($product->item_name);
    expect($foreign->fresh()->product_id)->toBe($other->getKey());
    expect($foreign->fresh()->factory_cost)->toBe('75.00');
    expect($foreign->fresh()->is_primary)->toBeTrue();
    expect($product->factoryCosts()->count())->toBe(0);
})->with(['update', 'remove']);

it('rejects duplicate or conflicting saved factory cost identifiers with 422', function (string $operation) {
    $product = MI_Product::factory()->create();
    $cost = MI_Product_Factory_Cost::factory()->for($product, 'product')->primary()->create(['factory_cost' => '75.00']);
    $this->signInMI($this->miUser());
    $row = ['id' => $cost->id, 'factory_name' => 'Attempted change', 'factory_cost' => '1.00'];
    $input = match ($operation) {
        'duplicate update' => ['factory_costs' => [$row, $row]],
        'duplicate removal' => ['remove_factory_cost_ids' => [$cost->id, $cost->id]],
        'update and remove' => ['factory_costs' => [$row], 'remove_factory_cost_ids' => [$cost->id]],
    };
    $field = match ($operation) {
        'duplicate update' => 'factory_costs.0.id',
        'duplicate removal' => 'remove_factory_cost_ids.0',
        'update and remove' => 'remove_factory_cost_ids',
    };

    $this->putJson(route('mi_app.update', $product), array_replace(miProductEditPayload($product), $input))
        ->assertUnprocessable()->assertJsonValidationErrors($field);

    expect($cost->fresh()->factory_cost)->toBe('75.00');
    expect($cost->fresh()->is_primary)->toBeTrue();
    $this->assertDatabaseCount('mi_product_factory_costs', 1);
})->with(['duplicate update', 'duplicate removal', 'update and remove']);

it('rejects multiple submitted primary factory costs with 422', function () {
    $product = MI_Product::factory()->create();
    $this->signInMI($this->miUser());

    $this->putJson(route('mi_app.update', $product), array_replace(miProductEditPayload($product), [
        'factory_costs' => [
            ['factory_name' => 'Factory A', 'factory_cost' => '12.00', 'is_primary' => true],
            ['factory_name' => 'Factory B', 'factory_cost' => '15.00', 'is_primary' => true],
        ],
    ]))->assertUnprocessable()->assertJsonValidationErrors(['factory_costs' => 'Select only one primary factory cost.']);

    $this->assertDatabaseCount('mi_product_factory_costs', 0);
});

it('limits the final saved factory cost count including omitted existing entries', function () {
    $product = MI_Product::factory()->create();
    $primary = MI_Product_Factory_Cost::factory()->for($product, 'product')->primary()->create();
    MI_Product_Factory_Cost::factory()->for($product, 'product')->count(99)->create();
    $this->signInMI($this->miUser());

    $this->putJson(route('mi_app.update', $product), array_replace(miProductEditPayload($product), [
        'item_name' => 'Attempted change',
        'factory_costs' => [['factory_name' => 'One too many', 'factory_cost' => '12.00']],
    ]))->assertUnprocessable()->assertJsonValidationErrors(['factory_costs' => 'A product may have at most 100 factory costs.']);

    $this->assertDatabaseCount('mi_product_factory_costs', 100);
    expect($primary->fresh()->is_primary)->toBeTrue();
    expect($product->fresh()->item_name)->toBe($product->item_name);
});

it('allows replacing a factory cost at the row limit in the same save', function () {
    $product = MI_Product::factory()->create();
    $primary = MI_Product_Factory_Cost::factory()->for($product, 'product')->primary()->create();
    MI_Product_Factory_Cost::factory()->for($product, 'product')->count(99)->create();
    $this->signInMI($this->miUser());

    $this->put(route('mi_app.update', $product), array_replace(miProductEditPayload($product), [
        'remove_factory_cost_ids' => [$primary->id],
        'factory_costs' => [['factory_name' => 'Replacement', 'factory_cost' => '12.00', 'is_primary' => true]],
    ]))->assertSessionHasNoErrors();

    $this->assertModelMissing($primary);
    $this->assertDatabaseCount('mi_product_factory_costs', 100);
    expect($product->factoryCosts()->where('is_primary', true)->pluck('factory_name')->all())->toBe(['Replacement']);
});

it('redisplays submitted factory costs inline errors and pending removals after validation fails', function () {
    Schema::create('warehouse', function (Blueprint $table): void {
        $table->id('warehouse_id');
        $table->string('warehouse_name');
    });
    $product = MI_Product::factory()->create();
    $saved = MI_Product_Factory_Cost::factory()->for($product, 'product')->primary()->create();
    $this->signInMI($this->miUser());

    $this->from(route('mi_app.edit', $product))->put(route('mi_app.update', $product), array_replace(miProductEditPayload($product), [
        'remove_factory_cost_ids' => [$saved->id],
        'factory_costs' => [['factory_name' => 'Unsaved supplier', 'factory_cost' => '-12.00', 'remarks' => 'Keep these notes']],
    ]))->assertRedirect(route('mi_app.edit', $product))
        ->assertSessionHasErrors(['factory_costs.0.factory_cost' => 'Enter a non-negative amount with no more than two decimal places.'])
        ->assertSessionHasInput('factory_costs.0.factory_name', 'Unsaved supplier')
        ->assertSessionHasInput('remove_factory_cost_ids.0', $saved->id);

    $response = $this->get(route('mi_app.edit', $product))->assertSee('Unsaved supplier')->assertSee('Keep these notes')
        ->assertSee('value="-12.00"', false)->assertSee('Enter a non-negative amount with no more than two decimal places.');
    $document = new DOMDocument;
    $document->loadHTML($response->getContent(), LIBXML_NOERROR | LIBXML_NOWARNING);
    $xpath = new DOMXPath($document);
    expect($xpath->query('//input[@name="remove_factory_cost_ids[]"][@value="'.$saved->id.'"][@checked]')->length)->toBe(1);
    expect($saved->fresh()->is_primary)->toBeTrue();
    $this->assertDatabaseCount('mi_product_factory_costs', 1);
});

it('escapes saved factory names and remarks in the edit form', function () {
    $product = MI_Product::factory()->create();
    $unsafe = '<script>alert("factory")</script>';
    MI_Product_Factory_Cost::factory()->for($product, 'product')->primary()->create(['factory_name' => $unsafe, 'remarks' => $unsafe]);
    $this->signInMI($this->miUser());

    $this->get(route('mi_app.edit', $product))->assertSee($unsafe)->assertDontSee($unsafe, false);
});

it('enforces a single primary factory cost per product at the database boundary', function () {
    $product = MI_Product::factory()->create();
    $primary = MI_Product_Factory_Cost::factory()->for($product, 'product')->primary()->create();
    $secondary = MI_Product_Factory_Cost::factory()->for($product, 'product')->create();

    expect(fn () => DB::table('mi_product_factory_costs')->where('id', $secondary->id)->update(['is_primary' => true]))
        ->toThrow(QueryException::class);

    expect($product->factoryCosts()->where('is_primary', true)->pluck('id')->all())->toBe([$primary->id]);
    expect($secondary->fresh()->is_primary)->toBeFalse();
});

it('reports missing OpenAI web search credentials without returning invented results', function () {
    $this->signInMI($this->miUser());
    config()->set('services.openai.key', null);

    $this->postJson(route('mi_app.price-comparisons.search'), [
        'product_name' => 'Oak table', 'query' => 'round', 'max_results' => 5,
    ])->assertStatus(503)->assertJsonPath('results', [])->assertJsonPath('message', 'OpenAI web search is not configured. Add OPENAI_API_KEY or save an API key in AI Provider Settings.');
});

it('returns only OpenAI offer results whose product pages are cited', function () {
    $this->signInMI($this->miUser());
    config()->set('services.openai.key', 'test-key');
    Http::fake(['https://api.openai.com/v1/responses' => Http::response([
        'output' => [[
            'type' => 'message',
            'content' => [[
                'type' => 'output_text',
                'text' => json_encode(['results' => [
                    ['store_name' => 'Cited Shop', 'product_name' => 'Oak Table', 'photo_url' => null, 'price' => 100, 'currency' => 'USD', 'product_link' => 'https://shop.example/oak-table'],
                    ['store_name' => 'Uncited Shop', 'product_name' => 'Other Table', 'photo_url' => null, 'price' => 1, 'currency' => 'USD', 'product_link' => 'https://other.example/table'],
                ]]),
                'annotations' => [['type' => 'url_citation', 'url' => 'https://shop.example/oak-table', 'title' => 'Oak Table']],
            ]],
        ]],
    ], 200)]);

    $referenceUrl = 'https://shop.example/current-oak-table';
    $this->postJson(route('mi_app.price-comparisons.search'), ['product_name' => 'Oak table', 'description' => 'Solid oak pedestal base', 'query' => 'round dining', 'reference_url' => $referenceUrl, 'max_results' => 5])
        ->assertOk()->assertJsonCount(1, 'results')->assertJsonPath('results.0.store_name', 'Cited Shop')
        ->assertJsonPath('results.0.product_link', 'https://shop.example/oak-table');
    Http::assertSent(fn ($request): bool => $request['tools'][0]['type'] === 'web_search'
        && $request['tool_choice'] === 'required'
        && str_contains($request['input'][1]['content'], $referenceUrl)
        && str_contains($request['input'][1]['content'], 'Solid oak pedestal base')
        && str_contains($request['input'][1]['content'], 'round dining')
        && ! isset($request['text']['format']));
    $this->assertDatabaseCount('mi_product_price_comparisons', 0);
});

it('parses fenced search JSON using web search sources and enforces the requested result limit', function () {
    $this->signInMI($this->miUser());
    config()->set('services.openai.key', 'test-key');
    $offer = ['store_name' => 'Shop', 'product_name' => 'Oak table', 'photo_url' => 'https://shop.example/photo.jpg', 'price' => 120, 'currency' => 'USD', 'product_link' => 'https://shop.example/table'];
    Http::preventStrayRequests();
    Http::fake(['https://api.openai.com/v1/responses' => Http::response([
        'status' => 'completed',
        'output' => [
            ['type' => 'web_search_call', 'action' => ['sources' => [['type' => 'url', 'url' => $offer['product_link']]]]],
            ['type' => 'message', 'content' => [['type' => 'output_text', 'text' => "```json\n".json_encode(['results' => [$offer, $offer]])."\n```"]]],
        ],
    ])]);

    $this->postJson(route('mi_app.price-comparisons.search'), ['product_name' => 'Oak table', 'max_results' => 1])
        ->assertOk()->assertJsonCount(1, 'results')->assertJsonPath('results.0.photo_url', $offer['photo_url']);
    $this->assertDatabaseCount('mi_product_price_comparisons', 0);
    Http::assertSentCount(1);
});

it('distinguishes unusable provider responses from an empty offer search', function (array $provider, int $status) {
    $this->signInMI($this->miUser());
    config()->set('services.openai.key', 'test-key');
    Http::preventStrayRequests();
    Http::fake(['https://api.openai.com/v1/responses' => Http::response($provider)]);

    $this->postJson(route('mi_app.price-comparisons.search'), ['product_name' => 'Oak table', 'max_results' => 2])
        ->assertStatus($status)->assertJsonPath('results', []);
    Http::assertSentCount(1);
})->with([
    'invalid JSON' => [['output' => [['type' => 'message', 'content' => [['type' => 'output_text', 'text' => 'Not JSON']]]]], 502],
    'incomplete output' => [['status' => 'incomplete', 'output' => []], 502],
    'genuine empty result' => [['output' => [['type' => 'message', 'content' => [['type' => 'output_text', 'text' => '{"results":[]}']]]]], 200],
]);

it('reports exhausted API credits as a billing problem rather than a temporary rate limit', function () {
    $this->signInMI($this->miUser());
    config()->set('services.openai.key', 'test-key');
    Http::preventStrayRequests();
    Http::fake(['https://api.openai.com/v1/responses' => Http::response(['error' => ['code' => 'credit_balance_exhausted']], 429)]);

    $this->postJson(route('mi_app.price-comparisons.search'), ['product_name' => 'Oak table', 'max_results' => 2])
        ->assertStatus(502)->assertJsonPath('results', [])->assertJsonPath('setup_required', true)->assertSee('Add credits to the API project');
    Http::assertSentCount(1);
});

it('uses configured research credentials and returns distinct source-backed comparable listings without saving them', function () {
    (require database_path('migrations/2026_10_26_085724_create_ai_provider_settings_tables.php'))->up();
    AiProviderSetting::create(['id' => 1, 'provider' => 'openai', 'api_key' => 'sk-configured-research', 'model' => 'gpt-5.4', 'enabled' => true]);
    $this->signInMI($this->miUser());
    config()->set('services.openai.key', 'sk-unused-environment');
    $offer = ['store_name' => 'Oak Store', 'product_name' => 'Round oak table', 'price' => 500, 'currency' => 'USD', 'product_link' => 'https://store.example/oak-table', 'photo_url' => null, 'match_reason' => 'Same oak material and round shape; slightly wider.', 'price_evidence' => 'The listing shows a 100 cm oak table priced at USD 500.'];
    Http::preventStrayRequests();
    Http::fake(['https://api.openai.com/v1/responses' => Http::response(['output' => [
        ['type' => 'web_search_call', 'status' => 'completed', 'action' => ['sources' => [['url' => $offer['product_link']]]]],
        ['type' => 'message', 'content' => [['type' => 'output_text', 'text' => json_encode(['results' => [$offer, $offer]])]]],
    ]])]);

    $this->postJson(route('mi_app.price-comparisons.search'), ['product_name' => 'Oak table', 'description' => 'Round oak dining table, 90 cm', 'query' => 'pedestal base', 'reference_url' => 'https://example.com/target', 'max_results' => 2])
        ->assertOk()->assertJsonCount(1, 'results')->assertJsonPath('results.0.match_reason', $offer['match_reason'])->assertJsonPath('results.0.price_evidence', $offer['price_evidence'])->assertDontSee('sk-configured-research');
    Http::assertSent(fn ($request): bool => $request->header('Authorization')[0] === 'Bearer sk-configured-research'
        && $request['model'] === 'gpt-5.4'
        && str_contains($request['input'][1]['content'], 'Round oak dining table, 90 cm')
        && str_contains($request['input'][1]['content'], 'pedestal base')
        && str_contains($request['input'][1]['content'], 'https://example.com/target')
        && str_contains($request['input'][1]['content'], 'up to 2'));
    $this->assertDatabaseCount('mi_product_price_comparisons', 0);
});

it('shows a setup error and makes no provider request when AI research is disabled', function () {
    $this->signInMI($this->miUser());
    config()->set('ai_assistant.enabled', false);
    Http::preventStrayRequests();
    Http::fake();

    $this->postJson(route('mi_app.price-comparisons.search'), ['product_name' => 'Oak table', 'max_results' => 2])
        ->assertStatus(503)->assertJsonPath('setup_required', true)->assertJsonPath('error_code', 'provider_disabled')->assertJsonPath('results', []);
    Http::assertNothingSent();
});

it('researches with Claude and follows a paused search without using OpenAI credentials or saving results', function () {
    (require database_path('migrations/2026_10_26_085724_create_ai_provider_settings_tables.php'))->up();
    (require database_path('migrations/2026_11_13_063602_add_research_provider_to_ai_provider_settings.php'))->up();
    AiProviderSetting::create(['id' => 1, 'research_provider' => 'anthropic', 'anthropic_api_key' => 'sk-claude-research', 'anthropic_model' => 'claude-sonnet-5-5', 'enabled' => true]);
    config()->set('services.openai.key', 'sk-never-use-openai');
    $this->signInMI($this->miUser());
    $offer = ['store_name' => 'Oak Store', 'product_name' => 'Oak table', 'photo_url' => null, 'price' => 100, 'currency' => 'USD', 'product_link' => 'https://shop.example/table'];
    Http::preventStrayRequests();
    Http::fakeSequence('https://api.anthropic.com/v1/messages')
        ->push(['stop_reason' => 'pause_turn', 'content' => [['type' => 'text', 'text' => 'Researching listings.']]])
        ->push(['stop_reason' => 'end_turn', 'content' => [['type' => 'text', 'text' => 'Here are the comparable offers: '.json_encode(['results' => [$offer, [...$offer, 'product_link' => 'https://uncited.example/table']]]), 'citations' => [['type' => 'web_search_result_location', 'url' => $offer['product_link']]]]]]);

    $this->postJson(route('mi_app.price-comparisons.search'), ['product_name' => 'Oak table', 'description' => 'Solid oak pedestal', 'query' => 'round dining', 'reference_url' => 'https://example.com/reference', 'max_results' => 1])
        ->assertOk()->assertJsonCount(1, 'results')->assertJsonPath('results.0.product_link', $offer['product_link']);
    Http::assertSent(fn ($request): bool => $request->header('x-api-key')[0] === 'sk-claude-research'
        && ! $request->hasHeader('Authorization') && $request['model'] === 'claude-sonnet-5-5'
        && str_contains($request['messages'][0]['content'], 'Solid oak pedestal')
        && str_contains($request['messages'][0]['content'], 'round dining')
        && str_contains($request['messages'][0]['content'], 'https://example.com/reference')
        && count($request['messages']) === 2);
    Http::assertSentCount(2);
    $this->assertDatabaseCount('mi_product_price_comparisons', 0);
});

it('returns Claude errors separately from research results', function (array $body, int $providerStatus, bool $setup) {
    (require database_path('migrations/2026_10_26_085724_create_ai_provider_settings_tables.php'))->up();
    (require database_path('migrations/2026_11_13_063602_add_research_provider_to_ai_provider_settings.php'))->up();
    AiProviderSetting::create(['id' => 1, 'research_provider' => 'anthropic', 'anthropic_api_key' => 'sk-claude', 'anthropic_model' => 'claude-sonnet-5-5', 'enabled' => true]);
    $this->signInMI($this->miUser());
    Http::preventStrayRequests();
    Http::fake(['https://api.anthropic.com/v1/messages' => Http::response($body, $providerStatus)]);

    $response = $this->postJson(route('mi_app.price-comparisons.search'), ['product_name' => 'Oak table', 'max_results' => 2]);

    $response->assertStatus(502)->assertJsonPath('results', [])->assertDontSee('sk-claude');
    if ($setup) {
        $response->assertJsonPath('setup_required', true);
    }
    Http::assertSentCount(1);
})->with([
    'billing' => [['error' => ['type' => 'invalid_request_error', 'message' => 'Your credit balance is too low.']], 400, true],
    'invalid credential' => [['error' => ['type' => 'authentication_error']], 401, true],
    'search access denied' => [['error' => ['type' => 'permission_error']], 403, true],
    'tool failure inside successful HTTP response' => [['stop_reason' => 'end_turn', 'content' => [['type' => 'web_search_tool_result', 'content' => ['type' => 'web_search_tool_result_error', 'error_code' => 'unavailable']]]], 200, false],
    'truncated output' => [['stop_reason' => 'max_tokens', 'content' => []], 200, false],
]);

it('does not use the OpenAI key when Claude credentials are missing', function () {
    (require database_path('migrations/2026_10_26_085724_create_ai_provider_settings_tables.php'))->up();
    (require database_path('migrations/2026_11_13_063602_add_research_provider_to_ai_provider_settings.php'))->up();
    AiProviderSetting::create(['id' => 1, 'research_provider' => 'anthropic', 'api_key' => 'sk-openai-only', 'enabled' => true]);
    config()->set('services.anthropic.key', null);
    $this->signInMI($this->miUser());
    Http::preventStrayRequests();
    Http::fake();

    $this->postJson(route('mi_app.price-comparisons.search'), ['product_name' => 'Oak table', 'max_results' => 2])
        ->assertStatus(503)->assertJsonPath('setup_required', true)->assertJsonPath('results', [])->assertSee('Claude web search is not configured.');
    Http::assertNothingSent();
});

it('returns actionable OpenAI web search rejection details without exposing credentials', function () {
    $this->signInMI($this->miUser());
    config()->set('services.openai.key', 'test-key');
    Http::fake(['https://api.openai.com/v1/responses' => Http::response([
        'error' => ['code' => 'unsupported_tool', 'message' => 'The selected model cannot use web_search.'],
    ], 400, ['x-request-id' => 'req_price_search_test'])]);

    $this->postJson(route('mi_app.price-comparisons.search'), ['product_name' => 'Oak table', 'max_results' => 5])
        ->assertStatus(502)
        ->assertJsonPath('message', 'OpenAI rejected the web search request: The selected model cannot use web_search.')
        ->assertJsonPath('request_id', 'req_price_search_test')
        ->assertDontSee('test-key');
});

it('requires the selected search method input and never falls back from image to name', function () {
    $this->signInMI($this->miUser());

    $this->postJson(route('mi_app.price-comparisons.search'), ['method' => 'name', 'max_results' => 5])
        ->assertUnprocessable()->assertJsonValidationErrors('product_name');
    $this->postJson(route('mi_app.price-comparisons.search'), ['method' => 'image', 'product_name' => 'Oak table', 'max_results' => 5])
        ->assertUnprocessable()->assertJsonValidationErrors('method');
});

it('saves only validated selected price comparisons for the authorized product', function () {
    $product = MI_Product::factory()->create();
    $this->signInMI($this->miUser());
    $result = [
        'store_name' => 'Example Store', 'product_name' => 'Oak side table', 'photo_url' => 'https://example.com/table.jpg',
        'price' => '125.00', 'currency' => 'usd', 'product_link' => 'https://example.com/table',
    ];

    $this->postJson(route('mi_app.price-comparisons.store', $product), ['results' => [$result]])
        ->assertCreated()->assertJsonPath('message', 'Selected comparisons saved.');
    $this->assertDatabaseHas('mi_product_price_comparisons', [
        'product_id' => $product->getKey(), 'store_name' => 'Example Store', 'currency' => 'USD', 'product_link' => 'https://example.com/table', 'photo_url' => 'https://example.com/table.jpg',
    ]);
    $this->postJson(route('mi_app.price-comparisons.store', $product), ['results' => [$result]])->assertCreated();
    $this->assertDatabaseCount('mi_product_price_comparisons', 1);

    $this->postJson(route('mi_app.price-comparisons.store', $product), ['results' => [[...$result, 'product_link' => 'javascript:alert(1)']]])
        ->assertUnprocessable()->assertJsonValidationErrors('results.0.product_link');
    $this->assertDatabaseCount('mi_product_price_comparisons', 1);
});

it('saves explicitly staged comparisons with a newly created product', function () {
    $fixture = MI_Product::factory()->create();
    $this->signInMI($this->miUser());
    $offer = ['store_name' => 'Selected Store', 'product_name' => 'Oak side table', 'photo_url' => 'https://example.com/selected-table.jpg', 'price' => '125.00', 'currency' => 'USD', 'product_link' => 'https://example.com/selected-table'];

    $this->post(route('mi_app.store_1'), array_replace(miProductEditPayload($fixture), [
        'item_name' => 'Price comparison regression product',
        'description' => 'Solid oak side table',
        'price_comparisons_json' => json_encode([$offer]),
    ]))->assertRedirect(route('mi_app.index'))->assertSessionHasNoErrors();

    $product = MI_Product::where('item_name', 'Price comparison regression product')->sole();
    expect($product->description)->toBe('Solid oak side table');
    $this->assertDatabaseHas('mi_product_price_comparisons', ['product_id' => $product->getKey(), 'store_name' => 'Selected Store', 'product_link' => $offer['product_link'], 'currency' => 'USD', 'photo_url' => $offer['photo_url']]);
    $this->assertDatabaseCount('mi_product_price_comparisons', 1);
});

it('normalizes provider image URLs while keeping offers with missing or unsafe photos reviewable', function (?string $photoUrl, ?string $expected) {
    $this->signInMI($this->miUser());
    config()->set('services.openai.key', 'test-key');
    $offer = ['store_name' => 'Image Store', 'product_name' => 'Oak table', 'photo_url' => $photoUrl, 'price' => 100, 'currency' => 'USD', 'product_link' => 'https://example.com/table'];
    Http::preventStrayRequests();
    Http::fake(['https://api.openai.com/v1/responses' => Http::response(['output' => [
        ['type' => 'message', 'content' => [['type' => 'output_text', 'text' => json_encode(['results' => [$offer]]), 'annotations' => [['type' => 'url_citation', 'url' => $offer['product_link']]]]]],
    ]])]);

    $this->postJson(route('mi_app.price-comparisons.search'), ['product_name' => 'Oak table', 'max_results' => 1])
        ->assertOk()->assertJsonCount(1, 'results')->assertJsonPath('results.0.photo_url', $expected);
    Http::assertSentCount(1);
    $this->assertDatabaseCount('mi_product_price_comparisons', 0);
})->with([
    'provider image including signed query' => ['https://images.example.com/product.jpg?token=abc&size=800', 'https://images.example.com/product.jpg?token=abc&size=800'],
    'public HTTP image' => ['http://images.example.com/product.jpg', 'http://images.example.com/product.jpg'],
    'no image' => [null, null],
    'javascript' => ['javascript:alert(1)', null],
    'embedded credentials' => ['https://user:secret@images.example.com/product.jpg', null],
    'loopback' => ['http://127.0.0.1/image.jpg', null],
    'private network' => ['http://192.168.1.4/image.jpg', null],
    'IPv6 loopback' => ['http://[::1]/image.jpg', null],
    'IPv4 mapped IPv6 loopback' => ['http://[::ffff:127.0.0.1]/image.jpg', null],
    'local name' => ['https://files.local/image.jpg', null],
    'numeric host alias' => ['http://2130706433/image.jpg', null],
    'browser backslash ambiguity' => ['https://public.example.com\\@127.0.0.1/image.jpg', null],
]);

it('rejects unsafe selected comparison image URLs on both product save paths', function (string $photoUrl) {
    $product = MI_Product::factory()->create();
    $this->signInMI($this->miUser());
    $offer = ['store_name' => 'Image Store', 'product_name' => 'Table', 'photo_url' => $photoUrl, 'price' => 100, 'currency' => 'USD', 'product_link' => 'https://example.com/table'];

    $this->postJson(route('mi_app.price-comparisons.store', $product), ['results' => [$offer]])
        ->assertUnprocessable()->assertJsonValidationErrors('results.0.photo_url');
    $this->postJson(route('mi_app.store_1'), array_replace(miProductEditPayload($product), ['price_comparisons_json' => json_encode([$offer])]))
        ->assertUnprocessable()->assertJsonValidationErrors('price_comparisons.0.photo_url');
    $this->assertDatabaseCount('mi_product_price_comparisons', 0);
    $this->assertDatabaseCount('mi_products', 1);
})->with(['http://10.0.0.1/image.jpg', 'https://user:secret@example.com/image.jpg', 'file:///tmp/image.jpg', 'http://[::1]/image.jpg']);

it('fills a missing saved offer image only after explicit selection without replacing existing image data', function () {
    $product = MI_Product::factory()->create();
    $comparison = $product->priceComparisons()->create(['store_name' => 'Image Store', 'product_name' => 'Table', 'price' => 100, 'currency' => 'USD', 'product_link' => 'https://example.com/table']);
    $this->signInMI($this->miUser());
    $offer = ['store_name' => 'Image Store', 'product_name' => 'Table', 'photo_url' => 'https://images.example.com/table.jpg', 'price' => 100, 'currency' => 'USD', 'product_link' => $comparison->product_link];

    $this->postJson(route('mi_app.price-comparisons.store', $product), ['results' => [$offer]])->assertCreated();
    expect($comparison->fresh()->photo_url)->toBe($offer['photo_url']);
    $this->postJson(route('mi_app.price-comparisons.store', $product), ['results' => [[...$offer, 'photo_url' => 'https://images.example.com/another.jpg']]])->assertCreated();
    expect($comparison->fresh()->photo_url)->toBe($offer['photo_url']);
    $this->assertDatabaseCount('mi_product_price_comparisons', 1);
    $uploaded = $product->priceComparisons()->create(['store_name' => 'Uploaded Store', 'product_name' => 'Table', 'price' => 100, 'currency' => 'USD', 'product_link' => 'https://example.com/uploaded-offer', 'photo_path' => 'comparisons/kept.png']);
    $this->postJson(route('mi_app.price-comparisons.store', $product), ['results' => [[...$offer, 'product_link' => $uploaded->product_link]]])->assertCreated();
    expect($uploaded->fresh()->photo_path)->toBe('comparisons/kept.png');
    expect($uploaded->fresh()->photo_url)->toBeNull();
});

it('renders saved comparison image areas safely and keeps unavailable-image offers visible', function () {
    Schema::create('warehouse', function (Blueprint $table): void {
        $table->id('warehouse_id');
        $table->string('warehouse_name');
    });
    $product = MI_Product::factory()->create();
    $this->signInMI($this->miUser());
    foreach ([
        ['photo_url' => 'https://images.example.com/product.jpg?token=abc&size=800'],
        ['photo_url' => 'http://127.0.0.1/private-image.jpg'],
        ['photo_path' => 'comparisons/uploaded.png'],
    ] as $index => $image) {
        $product->priceComparisons()->create([...$image, 'store_name' => 'Saved Store', 'product_name' => 'Saved offer '.$index, 'price' => 100, 'currency' => 'USD', 'product_link' => 'https://example.com/product-'.$index]);
    }

    $this->get(route('mi_app.edit', $product))->assertOk()
        ->assertSee('src="https://images.example.com/product.jpg?token=abc&amp;size=800"', false)
        ->assertDontSee('src="http://127.0.0.1/private-image.jpg"', false)
        ->assertSee('Saved offer 1')->assertSee('No product image available')->assertSee('comparisons/uploaded.png');
    if ($prefix = getenv('MI_COMPARISON_IMAGE_FIXTURE_PREFIX')) {
        file_put_contents($prefix.'-create.html', view('mi_app.designer_module.partials._price-comparisons', ['product' => null])->render());
        file_put_contents($prefix.'-edit.html', view('mi_app.designer_module.partials._price-comparisons', ['product' => $product])->render());
    }
});

it('normalizes structured provider image references into the canonical photo URL', function (array $imageFields, ?string $expected, string $status) {
    $this->signInMI($this->miUser());
    config()->set('services.openai.key', 'test-key');
    $offer = [...$imageFields, 'store_name' => 'Market Store', 'product_name' => 'Oak table', 'price' => 100, 'currency' => 'USD', 'product_link' => 'https://store.example.com/table'];
    Http::preventStrayRequests();
    Http::fake(['https://api.openai.com/v1/responses' => Http::response(['output' => [
        ['type' => 'message', 'content' => [['type' => 'output_text', 'text' => json_encode(['results' => [$offer]]), 'annotations' => [['url' => $offer['product_link']]]]]],
    ]])]);

    $response = $this->postJson(route('mi_app.price-comparisons.search'), ['product_name' => 'Oak table', 'max_results' => 1]);

    $response->assertOk()->assertJsonPath('results.0.photo_url', $expected)->assertJsonPath('results.0.image_status', $status);
    Http::assertSentCount(1);
    $this->assertDatabaseCount('mi_product_price_comparisons', 0);
})->with([
    'nested photo URL' => [['photo_url' => ['url' => 'https://images.example.com/table.jpg']], 'https://images.example.com/table.jpg', 'available'],
    'alternate image URL property' => [['image_url' => 'https://images.example.com/table.jpg'], 'https://images.example.com/table.jpg', 'available'],
    'Claude image source' => [['image' => ['type' => 'image', 'source' => ['type' => 'url', 'url' => 'https://images.example.com/table.jpg']]], 'https://images.example.com/table.jpg', 'available'],
    'ImageObject list' => [['images' => [['@type' => 'ImageObject', 'contentUrl' => 'https://images.example.com/table.jpg']]], 'https://images.example.com/table.jpg', 'available'],
    'null canonical field does not hide actual field' => [['photo_url' => null, 'productImageUrl' => 'https://images.example.com/table.jpg'], 'https://images.example.com/table.jpg', 'available'],
    'relative listing image' => [['image_reference' => ['src' => '/images/table.jpg']], 'https://store.example.com/images/table.jpg', 'available'],
    'HTML encoded query' => [['thumbnail' => ['url' => 'https://images.example.com/table.jpg?x=1&amp;y=2']], 'https://images.example.com/table.jpg?x=1&y=2', 'available'],
    'markdown image reference' => [['photo_url' => '![table](https://images.example.com/table.jpg)'], 'https://images.example.com/table.jpg', 'available'],
    'unencoded path space' => [['image_url' => 'https://images.example.com/oak table.jpg'], 'https://images.example.com/oak%20table.jpg', 'available'],
    'opaque reference is not guessed' => [['image_reference' => 'provider-image-id-42'], null, 'unresolved'],
    'unknown image field is not silently missing' => [['product_image_data' => ['opaque' => '42']], null, 'unresolved'],
    'structured private URL' => [['image' => ['url' => 'http://127.0.0.1/table.jpg']], null, 'unresolved'],
]);

it('maps native Claude source image fields only to their matching listing and saves the normalized URL on explicit selection', function () {
    (require database_path('migrations/2026_10_26_085724_create_ai_provider_settings_tables.php'))->up();
    (require database_path('migrations/2026_11_13_063602_add_research_provider_to_ai_provider_settings.php'))->up();
    AiProviderSetting::create(['id' => 1, 'research_provider' => 'anthropic', 'anthropic_api_key' => 'sk-claude', 'anthropic_model' => 'claude-sonnet-5-5', 'enabled' => true]);
    $this->signInMI($this->miUser());
    $product = MI_Product::factory()->create();
    $imageUrl = 'https://images.example.com/product.jpg?token=abc&size=800';
    $offer = ['store_name' => 'Market Store', 'product_name' => 'Oak table', 'photo_url' => null, 'price' => 100, 'currency' => 'USD', 'product_link' => 'https://store.example.com/table'];
    $this->mock(VerifiedListingImageResolver::class)->shouldNotReceive('resolve');
    Http::preventStrayRequests();
    Http::fake(['https://api.anthropic.com/v1/messages' => Http::response(['stop_reason' => 'end_turn', 'content' => [
        ['type' => 'web_search_tool_result', 'content' => [
            ['type' => 'web_search_result', 'url' => 'https://store.example.com/another', 'images' => [['url' => 'https://images.example.com/wrong.jpg']]],
            ['type' => 'web_search_result', 'url' => $offer['product_link'], 'image' => ['source' => ['type' => 'url', 'url' => $imageUrl]]],
        ]],
        ['type' => 'text', 'text' => json_encode(['results' => [$offer]])],
    ]])]);

    $response = $this->postJson(route('mi_app.price-comparisons.search'), ['product_name' => 'Oak table', 'max_results' => 1]);

    $response->assertOk()->assertJsonPath('results.0.photo_url', $imageUrl)->assertJsonPath('results.0.image_status', 'available');
    $this->assertDatabaseCount('mi_product_price_comparisons', 0);
    if ($path = getenv('MI_COMPARISON_STRUCTURED_RESPONSE')) {
        file_put_contents($path, $response->getContent());
    }
    $this->postJson(route('mi_app.price-comparisons.store', $product), ['results' => [[...$offer, 'photo_url' => $response->json('results.0.photo_url')]]])->assertCreated();
    $this->assertDatabaseHas('mi_product_price_comparisons', ['product_id' => $product->getKey(), 'photo_url' => $imageUrl]);
    Http::assertSentCount(1);
});

it('retrieves image metadata only for verified product listings when the provider returns no image', function () {
    $this->signInMI($this->miUser());
    config()->set('services.openai.key', 'test-key');
    $offer = ['store_name' => 'Store', 'product_name' => 'Table', 'photo_url' => null, 'price' => 100, 'currency' => 'USD', 'product_link' => 'https://store.example.com/table'];
    $this->mock(VerifiedListingImageResolver::class)->shouldReceive('resolve')->once()->with($offer['product_link'])->andReturn(['url' => 'https://images.example.com/verified-table.jpg', 'status' => 'available']);
    Http::preventStrayRequests();
    Http::fake(['https://api.openai.com/v1/responses' => Http::response(['output' => [
        ['type' => 'message', 'content' => [['type' => 'output_text', 'text' => json_encode(['results' => [$offer, [...$offer, 'product_link' => 'https://uncited.example.com/table']]]), 'annotations' => [['url' => $offer['product_link']]]]]],
    ]])]);

    $this->postJson(route('mi_app.price-comparisons.search'), ['product_name' => 'Table', 'max_results' => 2])
        ->assertOk()->assertJsonCount(1, 'results')->assertJsonPath('results.0.photo_url', 'https://images.example.com/verified-table.jpg');
    $this->assertDatabaseCount('mi_product_price_comparisons', 0);
    Http::assertSentCount(1);
});

it('adds a complete public listing by link for review and saves it only after explicit selection', function () {
    $product = MI_Product::factory()->create();
    $this->signInMI($this->miUser());
    config()->set('services.openai.key', null);
    $this->mock(PublicDnsResolver::class)->shouldReceive('addresses')->andReturn(['93.184.216.34']);
    $this->app->forgetInstance(VerifiedListingImageResolver::class);
    $source = 'https://store.example.com/manual?utm_source=test';
    $metadata = ['@type' => 'Product', 'url' => 'https://store.example.com/manual', 'name' => 'Manual Oak Table', 'image' => ['@type' => 'ImageObject', 'contentUrl' => 'https://images.example.com/manual.jpg'], 'offers' => ['@type' => 'Offer', 'price' => '240.00', 'priceCurrency' => 'USD', 'seller' => ['name' => 'Manual Store']]];
    Http::preventStrayRequests();
    Http::fake([$source => Http::response('<link rel="canonical" href="https://store.example.com/manual"><script type="application/ld+json">'.json_encode($metadata).'</script>', 200, ['Content-Type' => 'text/html'])]);

    $response = $this->postJson(route('mi_app.price-comparisons.from-link'), ['url' => $source]);

    $response->assertOk()->assertJsonPath('results.0.product_name', 'Manual Oak Table')->assertJsonPath('results.0.store_name', 'Manual Store')
        ->assertJsonPath('results.0.price', 240)->assertJsonPath('results.0.currency', 'USD')
        ->assertJsonPath('results.0.photo_url', 'https://images.example.com/manual.jpg')->assertJsonPath('results.0.product_link', $source)
        ->assertJsonPath('results.0.canonical_url', 'https://store.example.com/manual');
    $this->assertDatabaseCount('mi_product_price_comparisons', 0);
    if ($path = getenv('MI_COMPARISON_MANUAL_RESPONSE')) {
        file_put_contents($path, $response->getContent());
    }
    $result = array_intersect_key($response->json('results.0'), array_flip(['store_name', 'product_name', 'price', 'currency', 'photo_url', 'product_link']));
    $this->postJson(route('mi_app.price-comparisons.store', $product), ['results' => [$result]])->assertCreated();
    $this->assertDatabaseHas('mi_product_price_comparisons', ['product_id' => $product->getKey(), 'photo_url' => 'https://images.example.com/manual.jpg', 'product_link' => $source]);
    $this->post(route('mi_app.store_1'), array_replace(miProductEditPayload($product), ['item_name' => 'Manual listing create regression', 'price_comparisons_json' => json_encode([$result])]))
        ->assertRedirect(route('mi_app.index'))->assertSessionHasNoErrors();
    $created = MI_Product::where('item_name', 'Manual listing create regression')->sole();
    $this->assertDatabaseHas('mi_product_price_comparisons', ['product_id' => $created->getKey(), 'photo_url' => 'https://images.example.com/manual.jpg', 'product_link' => $source]);
    Http::assertSentCount(1);
});

it('requires authentication before retrieving a manual comparison link', function () {
    Http::preventStrayRequests();
    Http::fake();

    $this->postJson(route('mi_app.price-comparisons.from-link'), ['url' => 'https://store.example.com/manual'])->assertUnauthorized();
    Http::assertNothingSent();
});

it('rejects unsafe manual product URLs before requesting a page', function (string $url) {
    $this->signInMI($this->miUser());
    Http::preventStrayRequests();
    Http::fake();

    $this->postJson(route('mi_app.price-comparisons.from-link'), ['url' => $url])->assertUnprocessable()->assertJsonValidationErrors('url');
    Http::assertNothingSent();
    $this->assertDatabaseCount('mi_product_price_comparisons', 0);
})->with(['http://localhost/item', 'http://10.0.0.1/item', 'http://169.254.169.254/item', 'http://[::1]/item', 'file:///etc/passwd', 'https://user:password@example.com/item']);

it('blocks a public-looking manual URL when DNS resolves privately', function () {
    $this->signInMI($this->miUser());
    $this->mock(PublicDnsResolver::class)->shouldReceive('addresses')->andReturn(['10.0.0.1']);
    $this->app->forgetInstance(VerifiedListingImageResolver::class);
    Http::preventStrayRequests();
    Http::fake();

    $this->postJson(route('mi_app.price-comparisons.from-link'), ['url' => 'https://private.example.com/item'])
        ->assertUnprocessable()->assertJsonPath('results', [])->assertSee('could not be retrieved');
    Http::assertNothingSent();
    $this->assertDatabaseCount('mi_product_price_comparisons', 0);
});

it('does not add incomplete ambiguous or unsupported manual listings', function (array $metadata, string $message) {
    $this->signInMI($this->miUser());
    $this->mock(PublicDnsResolver::class)->shouldReceive('addresses')->andReturn(['93.184.216.34']);
    $this->app->forgetInstance(VerifiedListingImageResolver::class);
    Http::preventStrayRequests();
    Http::fake(['https://store.example.com/manual' => Http::response('<meta property="og:site_name" content="Manual Store"><script type="application/ld+json">'.json_encode($metadata).'</script>', 200, ['Content-Type' => 'text/html'])]);

    $this->postJson(route('mi_app.price-comparisons.from-link'), ['url' => 'https://store.example.com/manual'])
        ->assertUnprocessable()->assertJsonPath('results', [])->assertSee($message);
    $this->assertDatabaseCount('mi_product_price_comparisons', 0);
    Http::assertSentCount(1);
})->with([
    'missing price' => [['@type' => 'Product', 'name' => 'Table'], 'does not provide a verifiable'],
    'missing currency' => [['@type' => 'Product', 'name' => 'Table', 'offers' => ['price' => 100]], 'does not provide a verifiable'],
    'different variant prices' => [['@type' => 'Product', 'name' => 'Table', 'offers' => [['price' => 100, 'priceCurrency' => 'USD'], ['price' => 120, 'priceCurrency' => 'USD']]], 'multiple prices'],
    'non-USD listing' => [['@type' => 'Product', 'name' => 'Table', 'offers' => ['price' => 100, 'priceCurrency' => 'EUR']], 'accepts USD listings only'],
    'missing name' => [['@type' => 'Product', 'offers' => ['price' => 100, 'priceCurrency' => 'USD']], 'does not provide a verifiable'],
    'malformed structured type' => [['@type' => [['Product']], 'name' => 'Table'], 'does not provide a verifiable'],
]);

it('offers a correlated manual fallback for distinct listing retrieval failures', function (int $status, string $code, string $message) {
    $this->signInMI($this->miUser());
    $this->mock(PublicDnsResolver::class)->shouldReceive('addresses')->andReturn(['93.184.216.34']);
    $this->app->forgetInstance(VerifiedListingImageResolver::class);
    Http::preventStrayRequests();
    Http::fake(['https://www.etsy.com/listing/123/table' => Http::response('Sensitive upstream content must not be exposed', $status, ['Content-Type' => 'text/html'])]);
    Log::spy();

    $response = $this->postJson(route('mi_app.price-comparisons.from-link'), ['url' => 'https://www.etsy.com/listing/123/table']);

    $response->assertUnprocessable()->assertJsonPath('results', [])->assertJsonPath('error_code', $code)
        ->assertJsonPath('fallback.product_link', 'https://www.etsy.com/listing/123/table')->assertSee($message)->assertDontSee('Sensitive upstream');
    expect($response->json('correlation_id'))->toBeUuid();
    Log::shouldHaveReceived('warning')->with('MI product listing requires manual review.', Mockery::on(fn (array $context): bool => $context['correlation_id'] === $response->json('correlation_id') && $context['http_status'] === $status && $context['reason'] === $code))->once();
    $this->assertDatabaseCount('mi_product_price_comparisons', 0);
})->with([
    'store blocked access' => [403, 'access_blocked', 'blocked automatic access'],
    'rate limit' => [429, 'rate_limited', 'limiting requests'],
    'unavailable listing' => [404, 'not_found', 'listing is unavailable'],
    'store error' => [503, 'store_unavailable', 'store returned an error'],
]);

it('prefills readable metadata but leaves unknown listing details empty for confirmation', function () {
    $this->signInMI($this->miUser());
    $this->mock(PublicDnsResolver::class)->shouldReceive('addresses')->andReturn(['93.184.216.34']);
    $this->app->forgetInstance(VerifiedListingImageResolver::class);
    Http::preventStrayRequests();
    Http::fake(['https://store.example.com/table' => Http::response('<meta property="og:title" content="Oak Table"><meta property="og:site_name" content="Wood Store"><meta property="og:image" content="/table.jpg">', 200, ['Content-Type' => 'text/html'])]);

    $response = $this->postJson(route('mi_app.price-comparisons.from-link'), ['url' => 'https://store.example.com/table']);

    $response->assertUnprocessable()->assertJsonPath('fallback.product_name', 'Oak Table')->assertJsonPath('fallback.store_name', 'Wood Store')
        ->assertJsonPath('fallback.photo_url', 'https://store.example.com/table.jpg')->assertJsonMissingPath('fallback.price')->assertJsonMissingPath('fallback.currency')->assertJsonPath('results', []);
    $this->assertDatabaseCount('mi_product_price_comparisons', 0);
});

it('distinguishes a network failure without exposing exception details', function () {
    $this->signInMI($this->miUser());
    $this->mock(PublicDnsResolver::class)->shouldReceive('addresses')->andReturn(['93.184.216.34']);
    $this->app->forgetInstance(VerifiedListingImageResolver::class);
    Http::preventStrayRequests();
    Http::fake(['https://store.example.com/table' => fn () => throw new ConnectionException('Sensitive connection details')]);

    $this->postJson(route('mi_app.price-comparisons.from-link'), ['url' => 'https://store.example.com/table'])
        ->assertUnprocessable()->assertJsonPath('error_code', 'network_error')->assertJsonPath('fallback.product_link', 'https://store.example.com/table')->assertDontSee('Sensitive connection');
    $this->assertDatabaseCount('mi_product_price_comparisons', 0);
});

it('validates a confirmed manual comparison without fetching its page or image or saving automatically', function () {
    $this->signInMI($this->miUser());
    $this->mock(PublicDnsResolver::class)->shouldReceive('addresses')->withAnyArgs()->andReturn(['93.184.216.34']);
    Http::preventStrayRequests();
    Http::fake();
    $details = ['product_name' => 'Oak Table', 'store_name' => 'Handmade Store', 'price' => '240.50', 'currency' => 'usd', 'photo_url' => 'https://images.example.com/table.jpg', 'product_link' => 'https://www.etsy.com/listing/123/table'];

    $response = $this->postJson(route('mi_app.price-comparisons.manual'), $details);

    $response->assertOk()->assertJsonPath('results.0.product_name', 'Oak Table')->assertJsonPath('results.0.price', 240.5)->assertJsonPath('results.0.currency', 'USD')
        ->assertJsonPath('results.0.photo_url', $details['photo_url'])->assertJsonPath('results.0.product_link', $details['product_link'])->assertJsonPath('results.0.source', 'manual');
    Http::assertNothingSent();
    $this->assertDatabaseCount('mi_product_price_comparisons', 0);
});

it('rejects incomplete and unsafe confirmed manual details', function (array $changes, string $field) {
    $this->signInMI($this->miUser());
    Http::preventStrayRequests();
    Http::fake();
    $details = array_replace(['product_name' => 'Oak Table', 'store_name' => 'Handmade Store', 'price' => '240.50', 'currency' => 'USD', 'photo_url' => null, 'product_link' => 'https://www.etsy.com/listing/123/table'], $changes);

    $this->postJson(route('mi_app.price-comparisons.manual'), $details)->assertUnprocessable()->assertJsonValidationErrors($field);

    Http::assertNothingSent();
    $this->assertDatabaseCount('mi_product_price_comparisons', 0);
})->with([
    'missing name' => [['product_name' => ''], 'product_name'],
    'missing store' => [['store_name' => ''], 'store_name'],
    'missing price' => [['price' => ''], 'price'],
    'missing currency' => [['currency' => ''], 'currency'],
    'private page' => [['product_link' => 'http://127.0.0.1/item'], 'product_link'],
    'unsafe image' => [['photo_url' => 'javascript:alert(1)'], 'photo_url'],
    'private image' => [['photo_url' => 'http://10.0.0.1/image.jpg'], 'photo_url'],
]);

it('rejects private DNS and unusual ports when confirming manual details', function (string $url, array $addresses) {
    $this->signInMI($this->miUser());
    $this->mock(PublicDnsResolver::class)->shouldReceive('addresses')->andReturn($addresses);
    Http::preventStrayRequests();
    Http::fake();

    $this->postJson(route('mi_app.price-comparisons.manual'), ['product_name' => 'Table', 'store_name' => 'Store', 'price' => 20, 'currency' => 'USD', 'product_link' => $url])
        ->assertUnprocessable()->assertJsonPath('results', []);
    Http::assertNothingSent();
    $this->assertDatabaseCount('mi_product_price_comparisons', 0);
})->with([
    'private DNS' => ['https://private.example.com/table', ['10.0.0.1']],
    'unsafe port' => ['https://store.example.com:8080/table', ['93.184.216.34']],
]);

it('rejects an image hostname resolving privately without fetching it', function () {
    $this->signInMI($this->miUser());
    $this->mock(PublicDnsResolver::class)->shouldReceive('addresses')->andReturnUsing(fn (string $host): array => $host === 'store.example.com' ? ['93.184.216.34'] : ['10.0.0.1']);
    Http::preventStrayRequests();
    Http::fake();

    $this->postJson(route('mi_app.price-comparisons.manual'), ['product_name' => 'Table', 'store_name' => 'Store', 'price' => 20, 'currency' => 'USD', 'product_link' => 'https://store.example.com/table', 'photo_url' => 'https://private.example.com/image.jpg'])
        ->assertUnprocessable()->assertJsonValidationErrors('photo_url');
    Http::assertNothingSent();
    $this->assertDatabaseCount('mi_product_price_comparisons', 0);
});

it('requires authentication before confirming manual comparison details', function () {
    $this->postJson(route('mi_app.price-comparisons.manual'), [])->assertUnauthorized();
    $this->assertDatabaseCount('mi_product_price_comparisons', 0);
});
