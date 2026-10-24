<?php

namespace Database\Factories;

use App\Enums\ServiceLevel;
use App\Enums\ShipmentStatus;
use App\Models\Shipment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Shipment>
 */
class ShipmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'status' => ShipmentStatus::Booked,
            'service_level' => fake()->randomElement(ServiceLevel::cases()),
            'source' => 'admin',
            'sender_name' => fake()->name(),
            'sender_company' => null,
            'sender_email' => fake()->unique()->safeEmail(),
            'sender_phone' => '+234 80'.fake()->numerify('# ### ####'),
            'sender_address' => fake()->streetAddress(),
            'sender_city' => 'Lagos',
            'sender_country' => 'NG',
            'receiver_name' => fake()->name(),
            'receiver_company' => null,
            'receiver_email' => fake()->unique()->safeEmail(),
            'receiver_phone' => '+44 7'.fake()->numerify('### ######'),
            'receiver_address' => fake()->streetAddress(),
            'receiver_city' => 'London',
            'receiver_country' => 'GB',
            'contents' => fake()->randomElement(['Clothing', 'Documents', 'Phone accessories', 'Cosmetics', 'Books']),
            'pieces' => fake()->numberBetween(1, 4),
            'weight_kg' => fake()->randomFloat(1, 0.5, 40),
            'declared_value_cents' => fake()->numberBetween(20, 900) * 100,
            'price_cents' => fake()->numberBetween(40, 600) * 100,
            'pickup_date' => now()->addDay()->toDateString(),
            'estimated_delivery_date' => now()->addDays(7)->toDateString(),
        ];
    }

    public function status(ShipmentStatus $status): static
    {
        return $this->state(fn (): array => ['status' => $status]);
    }

    public function fromWebsite(): static
    {
        return $this->state(fn (): array => ['source' => 'website']);
    }
}
