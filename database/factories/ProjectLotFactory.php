<?php

namespace Database\Factories;

use App\Models\ProjectLot;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProjectLot>
 */
class ProjectLotFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'project_id' => ProjectInformationFactory::new(),
            'lot_no' => 'Lot 1',
            'country' => 'Philippines',
        ];
    }
}
