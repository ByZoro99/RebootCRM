<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SaleItem>
 */
class SaleItemFactory extends Factory
{
    public function definition(): array
    {
        return [
            'sale_id' => \App\Models\Sale::factory(),
            'platform_id' => \App\Models\Platform::factory(),
            'profile_id' => null,
            'description' => 'Perfil Netflix 1 mes',
            'price' => fake()->randomFloat(2, 2, 15),
            'months' => 1,
        ];
    }
}
