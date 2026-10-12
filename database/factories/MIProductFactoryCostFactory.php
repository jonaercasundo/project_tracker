<?php

namespace Database\Factories;

use App\Models\MI_Product;
use App\Models\MI_Product_Factory_Cost;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<MI_Product_Factory_Cost> */
class MIProductFactoryCostFactory extends Factory
{
    protected $model = MI_Product_Factory_Cost::class;

    /** @return array{product_id: MIProductFactory, factory_name: string, factory_cost: string, currency: string, effective_date: null, remarks: null, is_primary: bool} */
    public function definition(): array
    {
        return [
            'product_id' => MI_Product::factory(),
            'factory_name' => fake()->company(),
            'factory_cost' => number_format(fake()->numberBetween(0, 10000000) / 100, 2, '.', ''),
            'currency' => 'PHP',
            'effective_date' => null,
            'remarks' => null,
            'is_primary' => false,
        ];
    }

    public function primary(): static
    {
        return $this->state(fn (array $attributes): array => ['is_primary' => true]);
    }
}
