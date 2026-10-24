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
            'phone' => '+234 80'.fake()->numerify('# ### ####'),
            'address' => fake()->streetAddress(),
            'city' => 'Lagos',
            'country' => 'NG',
            'is_default' => false,
        ];
    }
}
