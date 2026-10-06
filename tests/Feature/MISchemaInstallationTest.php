<?php

use App\Console\Commands\MigrateMI;
use App\Models\MI_Product;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\MIWorkflowTestCase;

pest()->extend(MIWorkflowTestCase::class);

function miCatalogSchemaPaths(): array
{
    return [
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
    ];
}

it('installs the catalog from committed migrations and renders the MI dashboard', function () {
    foreach (miCatalogSchemaPaths() as $path) {
        (require database_path('migrations/'.$path))->up();
    }
    $user = $this->miUser();
    $this->signInMI($user);

    $this->get(route('mi_app.dashboard'))->assertOk()->assertViewHas('stats', fn (array $stats): bool => $stats['total_products'] === 0);
    $this->get(route('mi_app.create'))->assertOk();
    $this->get(route('mi_app.settings'))->assertOk();

    $categoryId = DB::table('mi_categories')->insertGetId(['code' => 'CAT', 'name' => 'Category']);
    $subCategoryId = DB::table('mi_sub_categories')->insertGetId(['category_id' => $categoryId, 'code' => 'SUB', 'name' => 'Subcategory']);
    $product = MI_Product::create([
        'item_name' => 'Catalog fixture', 'category_id' => $categoryId, 'sub_category_id' => $subCategoryId,
        'type_of_sample' => 'Sample', 'materials' => ['Wood'], 'color' => ['Blue'], 'price' => '12.34',
    ]);
    $product->images()->create(['image_type' => 'url', 'image_url' => 'https://example.test/image.png', 'is_primary' => true]);

    expect($product->fresh()->price)->toBe('12.34');
    expect($product->fresh()->materials)->toBe(['Wood']);
    expect($product->images()->sole()->image_url)->toBe('https://example.test/image.png');
    $product->delete();
    $this->assertDatabaseCount('mi_product_images', 0);
});

it('refuses to discard populated legacy product attributes', function () {
    (require database_path('migrations/2026_07_27_073520_create_m_i__products_table.php'))->up();
    DB::table('mi_products')->insert(['main_category' => 'Legacy category', 'item_name' => 'Existing product', 'material' => 'Original material']);

    expect(fn () => (require database_path('migrations/2026_07_31_015928_update_mi_products_table_for_new_product_system.php'))->up())
        ->toThrow(RuntimeException::class, 'Populated legacy MI products require an explicit data conversion');

    $this->assertDatabaseHas('mi_products', ['item_name' => 'Existing product', 'material' => 'Original material']);
    expect(Schema::hasColumn('mi_products', 'main_category'))->toBeTrue();
});

it('fails before changing delivery tables when their legacy parent is missing', function (string $path, string $table) {
    expect(fn () => (require database_path('migrations/'.$path))->up())
        ->toThrow(RuntimeException::class, 'Restore the legacy package_status table');

    expect(Schema::hasTable($table))->toBeFalse();
})->with([
    ['2026_07_22_030841_create_delivery_proofs_table.php', 'delivery_proofs'],
    ['2026_07_22_032736_create_delivery_histories_table.php', 'delivery_history'],
]);

it('preserves a partially created delivery proof table when the parent is missing', function () {
    Schema::create('delivery_proofs', function (Blueprint $table): void {
        $table->id();
        $table->unsignedBigInteger('package_status_id');
        $table->string('photo');
        $table->timestamps();
    });
    DB::table('delivery_proofs')->insert(['package_status_id' => 7, 'photo' => 'original-photo.jpg']);

    expect(fn () => (require database_path('migrations/2026_07_22_030841_create_delivery_proofs_table.php'))->up())
        ->toThrow(RuntimeException::class, 'Restore the legacy package_status table');

    $this->assertDatabaseHas('delivery_proofs', ['package_status_id' => 7, 'photo' => 'original-photo.jpg']);
});

it('repairs a partial delivery proof migration without losing rows and supports repeat execution', function () {
    Schema::create('package_status', fn (Blueprint $table) => $table->id('package_status_id'));
    Schema::create('delivery_proofs', function (Blueprint $table): void {
        $table->id();
        $table->unsignedBigInteger('package_status_id');
        $table->string('photo');
        $table->timestamps();
    });
    DB::table('package_status')->insert(['package_status_id' => 7]);
    DB::table('delivery_proofs')->insert(['package_status_id' => 7, 'photo' => 'original-photo.jpg']);
    $migration = require database_path('migrations/2026_07_22_030841_create_delivery_proofs_table.php');

    $migration->up();
    $migration->up();

    $this->assertDatabaseHas('delivery_proofs', ['photo' => 'original-photo.jpg']);
    DB::table('package_status')->where('package_status_id', 7)->delete();
    $this->assertDatabaseCount('delivery_proofs', 0);
});

it('rejects orphaned delivery proofs before adding the missing foreign key', function () {
    Schema::create('package_status', fn (Blueprint $table) => $table->id('package_status_id'));
    Schema::create('delivery_proofs', function (Blueprint $table): void {
        $table->id();
        $table->unsignedBigInteger('package_status_id');
        $table->string('photo');
        $table->timestamps();
    });
    DB::table('delivery_proofs')->insert(['package_status_id' => 7, 'photo' => 'orphan-photo.jpg']);

    expect(fn () => (require database_path('migrations/2026_07_22_030841_create_delivery_proofs_table.php'))->up())
        ->toThrow(RuntimeException::class, 'Existing delivery proofs reference missing package statuses');

    $this->assertDatabaseHas('delivery_proofs', ['photo' => 'orphan-photo.jpg']);
    expect(Schema::getForeignKeys('delivery_proofs'))->toBe([]);
});

it('creates delivery proof and history foreign keys against the real legacy primary key', function () {
    Schema::create('package_status', fn (Blueprint $table) => $table->id('package_status_id'));
    (require database_path('migrations/2026_07_22_030841_create_delivery_proofs_table.php'))->up();
    (require database_path('migrations/2026_07_22_032736_create_delivery_histories_table.php'))->up();
    $user = $this->miUser();
    DB::table('package_status')->insert(['package_status_id' => 7]);
    DB::table('delivery_proofs')->insert(['package_status_id' => 7, 'photo' => 'proof.jpg']);
    DB::table('delivery_history')->insert(['package_status_id' => 7, 'user_id' => $user->getKey(), 'status' => 'delivered']);

    DB::table('package_status')->where('package_status_id', 7)->delete();

    $this->assertDatabaseCount('delivery_proofs', 0);
    $this->assertDatabaseCount('delivery_history', 0);
});

it('refuses to adopt an incomplete existing delivery proof table', function () {
    Schema::create('package_status', fn (Blueprint $table) => $table->id('package_status_id'));
    Schema::create('delivery_proofs', fn (Blueprint $table) => $table->id());

    expect(fn () => (require database_path('migrations/2026_07_22_030841_create_delivery_proofs_table.php'))->up())
        ->toThrow(RuntimeException::class, 'The existing delivery_proofs schema is incomplete');

    expect(Schema::getColumnListing('delivery_proofs'))->toBe(['id']);
});

it('refuses to silently accept a delivery proof foreign key with different delete behavior', function () {
    Schema::create('package_status', fn (Blueprint $table) => $table->id('package_status_id'));
    Schema::create('delivery_proofs', function (Blueprint $table): void {
        $table->id();
        $table->foreignId('package_status_id')->constrained('package_status', 'package_status_id')->restrictOnDelete();
        $table->string('photo');
        $table->timestamps();
    });

    expect(fn () => (require database_path('migrations/2026_07_22_030841_create_delivery_proofs_table.php'))->up())
        ->toThrow(RuntimeException::class, 'The existing delivery_proofs foreign key requires reconciliation.');
});

it('runs the MI migration command without requiring the legacy delivery module and can be repeated', function () {
    $this->app->make(Kernel::class)->registerCommand(app(MigrateMI::class));

    $this->artisan('mi:migrate', ['--force' => true])->assertSuccessful();
    $this->artisan('mi:migrate', ['--force' => true])->assertSuccessful();

    expect(Schema::hasTable('mi_product_quotations'))->toBeTrue();
    expect(Schema::hasTable('mi_costing_analyses'))->toBeTrue();
    expect(Schema::hasTable('financial_activities'))->toBeTrue();
    expect(Schema::hasTable('delivery_proofs'))->toBeFalse();
    expect(Schema::hasTable('package_status'))->toBeFalse();
    $this->assertDatabaseMissing('migrations', ['migration' => '2026_07_22_030841_create_delivery_proofs_table']);
    $this->assertDatabaseMissing('migrations', ['migration' => '2026_10_04_053818_align_bidding_hierarchy']);
    $this->assertDatabaseHas('migrations', ['migration' => '2026_10_06_080510_enable_mi_executive_approval_workflow']);
    $this->assertDatabaseHas('roles', ['name' => 'executive', 'guard_name' => 'web']);
});

it('previews MI migrations without installing tables or recording them as applied', function () {
    $this->app->make(Kernel::class)->registerCommand(app(MigrateMI::class));

    $this->artisan('mi:migrate', ['--force' => true, '--pretend' => true])->assertSuccessful();

    expect(Schema::hasTable('mi_factories'))->toBeFalse();
    expect(Schema::hasTable('mi_products'))->toBeFalse();
    $this->assertDatabaseMissing('migrations', ['migration' => '2026_07_27_074144_create_mi_factories_table']);
});
