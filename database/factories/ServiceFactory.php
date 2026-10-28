<?php

namespace Database\Factories;

use App\Models\Service;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Service>
 */
class ServiceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = Str::title(fake()->unique()->word().' '.fake()->word());

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'icon' => 'box',
            'summary' => fake()->sentence(),
            'description' => fake()->paragraphs(2, true),
            'features' => ['Door-to-door tracking', 'Customs paperwork'],
            'sort_order' => 0,
            'is_active' => true,
        ];
    }
}
