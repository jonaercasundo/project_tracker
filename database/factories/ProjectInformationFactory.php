<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ProjectInformation>
 */
class ProjectInformationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'project_id' => fake()->unique()->bothify('BID-########'),
            'project_code' => 'SME',
            'project_name' => fake()->sentence(3),
            'approved_budget_contract_abc' => '500.00',
            'status' => 'Draft',
        ];
    }
}
