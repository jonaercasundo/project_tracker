<?php

namespace Database\Factories;

use App\Models\MI_Product;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\DB;

/** @extends Factory<MI_Product> */
class MIProductFactory extends Factory
{
    protected $model = MI_Product::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        $categoryId = DB::table('mi_categories')->insertGetId(['code' => fake()->unique()->lexify('???'), 'name' => fake()->word()]);
        $subCategoryId = DB::table('mi_sub_categories')->insertGetId(['category_id' => $categoryId, 'code' => fake()->lexify('???'), 'name' => fake()->word()]);

        return [
            'item_name' => fake()->words(3, true), 'item_code' => fake()->unique()->bothify('ITEM-#####'),
            'category_id' => $categoryId, 'sub_category_id' => $subCategoryId,
            'type_of_sample' => 'Factory Design', 'materials' => ['Acacia Wood'], 'color' => ['Natural'],
            'description' => 'Existing product description', 'type' => 'Indoor', 'designed_by' => 'MI Design',
            'product_height' => '40.00', 'product_width' => '20.00', 'purchase_cost' => '12.34', 'price' => '25.50',
            'status' => 'Active',
        ];
    }
}
