<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Sale>
 */
class SaleFactory extends Factory
{
    public function definition(): array
    {
        return [
            'customer_id' => \App\Models\Customer::factory(),
            'seller_id' => null,
            'total' => fake()->randomFloat(2, 5, 50),
            'sold_at' => now(),
        ];
    }
}
