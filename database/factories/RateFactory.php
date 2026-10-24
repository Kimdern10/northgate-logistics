<?php

namespace Database\Factories;

use App\Enums\ServiceLevel;
use App\Enums\Zone;
use App\Models\Rate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Rate>
 */
class RateFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'service_level' => ServiceLevel::Standard,
            'zone' => Zone::International,
            'base_fee_cents' => 2500,
            'per_kg_cents' => 900,
            'min_days' => 4,
            'max_days' => 7,
            'is_active' => true,
        ];
    }
}
