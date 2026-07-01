<?php

namespace Database\Factories;

use App\Models\Platform;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Platform>
 */
class PlatformFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->randomElement(['Netflix', 'Disney+', 'Spotify', 'HBO Max']),
            'base_price' => fake()->randomFloat(2, 2, 15),
            'profiles_per_account' => fake()->numberBetween(1, 5),
            'active' => true,
        ];
    }
}
