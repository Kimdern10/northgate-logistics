<?php

namespace Database\Factories;

use App\Models\Address;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Address>
 */
class AddressFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'label' => fake()->randomElement(['Home', 'Office', 'Warehouse']),
            'name' => fake()->name(),
            'company' => null,
            'email' => fake()->safeEmail(),
            'phone' => '+1 (212) 555-'.fake()->numerify('####'),
            'address' => fake()->streetAddress(),
            'city' => 'New York',
            'country' => 'US',
            'is_default' => false,
        ];
    }
}
