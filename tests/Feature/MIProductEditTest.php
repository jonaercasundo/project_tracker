<?php

use App\Models\MI_Product;
use App\Models\MI_Product_Image;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
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
    ] as $migration) {
        (require database_path('migrations/'.$migration))->up();
    }
});

/** @return array<string, mixed> */
function miProductEditPayload(MI_Product $product): array
{
    return $product->only(['item_name', 'category_id', 'sub_category_id', 'type_of_sample', 'materials']);
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
    $this->signInMI($this->miUser($role, $company));
    $route = $method === 'get' ? 'mi_app.edit' : 'mi_app.update';

    $this->{$method}(route($route, $product), miProductEditPayload($product))->assertForbidden();

    expect($product->fresh()->item_name)->toBe($product->item_name);
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
        ]))->assertRedirect(route('mi_app.edit', $product))->assertSessionHasErrors(['error' => 'Unable to save the product. Your existing data has been preserved. Please try again.']);
    } finally {
        MI_Product_Image::flushEventListeners();
    }

    expect($product->fresh()->item_name)->toBe($product->item_name);
    expect($product->images()->pluck('id')->all())->toBe([$image->id]);
    expect($image->fresh()->is_primary)->toBeTrue();
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

it('creates a product with media then loads its shared options and saves an edit', function () {
    Storage::fake('public');
    $fixture = MI_Product::factory()->create();
    $this->signInMI($this->miUser());
    $this->freezeTime();
    $payload = array_replace(miProductEditPayload($fixture), ['item_name' => 'New create fixture', 'image_links' => ['https://example.com/create.jpg'], 'product_images' => [UploadedFile::fake()->image('created.png')]]);

    $this->post(route('mi_app.store_1'), $payload)->assertRedirect(route('mi_app.index'))->assertSessionHasNoErrors();

    $created = MI_Product::where('item_name', 'New create fixture')->sole();
    expect($created->sku)->not->toBeEmpty();
    expect($created->draft_number)->toBe('DR-'.now()->format('Y').'-'.str_pad((string) $created->getKey(), 4, '0', STR_PAD_LEFT));
    expect($created->images()->count())->toBe(2);
    expect($created->images()->where('is_primary', true)->count())->toBe(1);
    $this->get(route('mi_app.create'))->assertSee('Acacia Wood')->assertSee('Natural');
    $this->get(route('mi_app.edit', $created))->assertSee('Acacia Wood')->assertSee('Natural');
    $this->put(route('mi_app.update', $created), miProductEditPayload($created))->assertSessionHasNoErrors();
    expect($created->images()->count())->toBe(2);
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
