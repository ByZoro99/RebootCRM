<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Payment>
 */
class PaymentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'sale_id' => \App\Models\Sale::factory(),
            'amount' => fake()->randomFloat(2, 5, 50),
            'method' => 'efectivo',
            'status' => \App\Enums\PaymentStatus::Paid->value,
            'paid_at' => now(),
        ];
    }
}
