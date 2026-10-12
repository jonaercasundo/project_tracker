<?php

use App\Models\MI_Product;
use App\Models\MI_Product_Factory_Cost;
use Database\Seeders\MIProductFactoryCostSeeder;
use Illuminate\Database\QueryException;
use Tests\MIWorkflowTestCase;

class MIProductFactoryCostSchemaTestCase extends MIWorkflowTestCase
{
    protected static ?string $mysqlReadyToken = null;

    protected function migrateFreshUsing(): array
    {
        return array_replace(parent::migrateFreshUsing(), ['--path' => [
            'database/migrations/2026_07_27_073520_create_m_i__products_table.php',
            'database/migrations/2026_07_29_075636_create_categories_table.php',
            'database/migrations/2026_07_29_075711_create_sub_categories_table.php',
            'database/migrations/2026_07_29_075746_create_product_types_table.php',
            'database/migrations/2026_07_29_075829_create_collections_table.php',
            'database/migrations/2026_07_30_085237_create_mi_materials_table.php',
            'database/migrations/2026_07_31_015928_update_mi_products_table_for_new_product_system.php',
            'database/migrations/2026_07_31_020944_change_materials_and_color_to_json_in_mi_products_table.php',
            'database/migrations/2026_08_17_000000_add_price_to_products_table.php',
            'database/migrations/2026_10_06_085134_add_unique_item_code_to_mi_products_table.php',
            'database/migrations/2026_10_12_055813_create_mi_product_factory_costs_table.php',
        ]]);
    }
}

pest()->extend(MIProductFactoryCostSchemaTestCase::class);

it('stores factory costs against the real product key without changing product prices', function () {
    $product = MI_Product::factory()->create(['purchase_cost' => '12.34', 'price' => '25.50']);

    $cost = $product->factoryCosts()->create([
        'factory_name' => 'Factory A',
        'factory_cost' => '1250.01',
        'effective_date' => '2026-10-12',
    ])->refresh();

    expect($cost->product->getKey())->toBe($product->getKey());
    expect($cost->factory_cost)->toBe('1250.01');
    expect($cost->currency)->toBe('PHP');
    expect($cost->is_primary)->toBeFalse();
    expect($cost->effective_date->toDateString())->toBe('2026-10-12');
    expect($product->fresh()->only(['purchase_cost', 'price']))->toBe(['purchase_cost' => '12.34', 'price' => '25.50']);
});

it('allows multiple secondary costs but rejects a second primary for the same product', function () {
    $product = MI_Product::factory()->create();
    MI_Product_Factory_Cost::factory()->for($product, 'product')->primary()->create();
    MI_Product_Factory_Cost::factory()->for($product, 'product')->count(2)->create();
    MI_Product_Factory_Cost::factory()->primary()->create();

    expect(fn () => MI_Product_Factory_Cost::factory()->for($product, 'product')->primary()->create())
        ->toThrow(QueryException::class);

    expect($product->factoryCosts()->count())->toBe(3);
    $this->assertDatabaseCount('mi_product_factory_costs', 4);
});

it('removes only the deleted products factory costs', function () {
    $product = MI_Product::factory()->create();
    MI_Product_Factory_Cost::factory()->for($product, 'product')->primary()->create();
    MI_Product_Factory_Cost::factory()->for($product, 'product')->create();
    $otherCost = MI_Product_Factory_Cost::factory()->primary()->create();

    $product->delete();

    $this->assertDatabaseCount('mi_product_factory_costs', 1);
    $this->assertModelExists($otherCost);
});

it('rejects a factory cost with no existing product', function () {
    expect(fn () => MI_Product_Factory_Cost::factory()->create(['product_id' => 999999]))
        ->toThrow(QueryException::class);

    $this->assertDatabaseCount('mi_product_factory_costs', 0);
});

it('seeds an opt in demo once without adding costs to existing products', function () {
    $existingProduct = MI_Product::factory()->create();

    $this->seed(MIProductFactoryCostSeeder::class);
    $this->seed(MIProductFactoryCostSeeder::class);

    $this->assertDatabaseCount('mi_products', 2);
    $this->assertDatabaseCount('mi_product_factory_costs', 3);
    expect($existingProduct->factoryCosts()->count())->toBe(0);
    expect(MI_Product_Factory_Cost::where('is_primary', true)->count())->toBe(1);
});

it('does not seed demonstration costs in production', function () {
    $this->app->instance('env', 'production');

    $this->app->make(MIProductFactoryCostSeeder::class)->run();

    $this->assertDatabaseCount('mi_products', 0);
    $this->assertDatabaseCount('mi_product_factory_costs', 0);
});
