<?php

namespace Database\Factories;

use App\Models\ProjectLot;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\BiddingDeliveryAddress>
 */
class BiddingDeliveryAddressFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'lot_id' => ProjectLotFactory::new(),
            'project_id' => fn (array $attributes): int => ProjectLot::query()->findOrFail($attributes['lot_id'])->project_id,
            'delivery_address' => fake()->streetAddress(),
        ];
    }
}
