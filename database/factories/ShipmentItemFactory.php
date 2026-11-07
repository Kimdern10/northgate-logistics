<?php

namespace Database\Factories;

use App\Models\Shipment;
use App\Models\ShipmentItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ShipmentItem>
 */
class ShipmentItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'shipment_id' => Shipment::factory(),
            'description' => fake()->randomElement(['Cotton T-shirts', 'Paperback books', 'Phone cases', 'Leather wallet']),
            'quantity' => fake()->numberBetween(1, 10),
            'unit_value_cents' => fake()->numberBetween(5, 80) * 100,
            'origin_country' => 'US',
            'hs_code' => null,
        ];
    }
}
