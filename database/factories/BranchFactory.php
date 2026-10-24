<?php

namespace Database\Factories;

use App\Models\Branch;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Branch>
 */
class BranchFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->city().' Branch',
            'city' => 'New York',
            'country' => 'US',
            'address' => fake()->streetAddress(),
            'phone' => '+1 (718) 555-'.fake()->numerify('####'),
            'email' => fake()->safeEmail(),
            'opening_hours' => 'Mon–Fri 8am–6pm',
            'latitude' => 40.6413,
            'longitude' => -73.7781,
            'is_hub' => false,
            'is_warehouse' => false,
            'is_active' => true,
        ];
    }
}
