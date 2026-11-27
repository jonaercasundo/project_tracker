<?php

namespace Database\Factories;

use App\Models\MmcBiometricConnection;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MmcBiometricConnection>
 */
class MmcBiometricConnectionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id' => 'MMC',
            'device_ip' => fake()->localIpv4(),
            'tcp_port' => 4370,
            'device_id' => 1,
            'device_serial' => fake()->bothify('SN-########'),
            'comm_key' => (string) fake()->numberBetween(1000, 999999),
        ];
    }
}
