<?php

use App\Models\MI_Product;
use App\Models\MI_Product_Factory_Cost;
use App\Models\MI_Product_Image;
use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\MIWorkflowTestCase;

pest()->extend(MIWorkflowTestCase::class);

beforeEach(function () {
    foreach ([
        '2026_07_27_073520_create_m_i__products_table.php',
        '2026_07_29_075636_create_categories_table.php',
        '2026_07_29_075711_create_sub_categories_table.php',
        '2026_07_29_075746_create_product_types_table.php',
        '2026_07_29_075829_create_collections_table.php',
        '2026_07_30_085237_create_mi_materials_table.php',
        '2026_07_31_015928_update_mi_products_table_for_new_product_system.php',
        '2026_07_31_020944_change_materials_and_color_to_json_in_mi_products_table.php',
        '2026_08_17_000000_add_price_to_products_table.php',
        '2026_10_06_040342_create_mi_product_images_table.php',
        '2026_10_06_085134_add_unique_item_code_to_mi_products_table.php',
        '2026_10_12_055813_create_mi_product_factory_costs_table.php',
        '2026_10_20_082229_add_country_of_production_to_mi_products_table.php',
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
    $product = MI_Product::factory()->create(['materials' => ['Custom timber'], 'color' => ['Custom color']]);
    $image = $product->images()->create(['image_type' => 'url', 'image_url' => 'https://example.com/original.jpg', 'is_primary' => true]);
    $this->signInMI($this->miUser());

    $this->from(route('mi_app.edit', $product))->put(route('mi_app.update', $product), array_replace(miProductEditPayload($product), [
        'item_name' => 'Rejected product name', 'materials' => '', 'color' => '', 'remove_image_ids' => [$image->id],
    ]))->assertRedirect(route('mi_app.edit', $product))
        ->assertSessionHasErrors(['materials' => 'The materials field is required.'])
        ->assertSessionHas('_old_input', fn (array $input): bool => array_key_exists('materials', $input) && $input['materials'] === null);

    $response = $this->get(route('mi_app.edit', $product))->assertSee('The materials field is required.');
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
            'currency' => 'PHP',
            'is_primary' => '1',
        ]],
        'image_links' => ['https://example.com/create.jpg'],
        'product_images' => [UploadedFile::fake()->image('created.png')],
    ]);

    $this->post(route('mi_app.store_1'), $payload)->assertRedirect(route('mi_app.index'))->assertSessionHasNoErrors();

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

    $this->get(route('mi_app.show', $product))->assertOk()->assertSee('Country of Production')->assertSee('Not Set');
    $this->get(route('mi_app.edit', $product))->assertOk()->assertSee('Country of Production')->assertSee('value=""', false);
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

it('shows the primary factory cost and product prices on the detail page', function () {
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

    $this->get(route('mi_app.show', $product))
        ->assertSee('Primary Factory Cost')
        ->assertSee('Selected Factory')
        ->assertSee('PHP 63.75')
        ->assertSee('Purchase Price')
        ->assertSee('PHP 88.25')
        ->assertSee('Selling Price')
        ->assertSee('PHP 120.50')
        ->assertDontSee('PHP 61.11');
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
    expect($cost->currency)->toBe('PHP');
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
    $product = MI_Product::factory()->create();
    $this->signInMI($this->miUser());

    $this->patch(route('mi_app.update', $product), array_replace(miProductEditPayload($product), [
        'factory_costs' => [
            ['factory_name' => 'Factory A', 'factory_cost' => '1250.00', 'currency' => 'PHP', 'effective_date' => '2026-10-12', 'remarks' => 'Includes packing'],
            ['factory_name' => 'Factory B', 'factory_cost' => '1180.00', 'is_primary' => true],
            ['factory_name' => 'Factory C', 'factory_cost' => '1350.00'],
        ],
    ]))->assertRedirect(route('mi_app.edit', $product))->assertSessionHasNoErrors();

    $this->assertDatabaseCount('mi_product_factory_costs', 3);
    $this->assertDatabaseHas('mi_product_factory_costs', [
        'product_id' => $product->getKey(), 'factory_name' => 'Factory A', 'factory_cost' => '1250.00',
        'currency' => 'PHP', 'remarks' => 'Includes packing', 'is_primary' => false,
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
    'unsupported currency' => [['factory_costs' => [['factory_name' => 'Factory A', 'factory_cost' => '12.00', 'currency' => 'USD']]], 'factory_costs.0.currency'],
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
