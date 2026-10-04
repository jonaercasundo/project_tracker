<?php

namespace Database\Factories;

use App\Models\BiddingDeliveryAddress;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\BiddingKeyStage>
 */
class BiddingKeyStageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'delivery_address_id' => BiddingDeliveryAddressFactory::new(),
            'project_id' => fn (array $attributes): int => BiddingDeliveryAddress::query()->findOrFail($attributes['delivery_address_id'])->project_id,
            'lot_id' => fn (array $attributes): int => BiddingDeliveryAddress::query()->findOrFail($attributes['delivery_address_id'])->lot_id,
            'name' => fake()->words(2, true),
        ];
    }
}
