<?php

namespace Database\Factories;

use App\Models\Account;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Account>
 */
class AccountFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'platform_id' => \App\Models\Platform::factory(),
            'email' => fake()->unique()->safeEmail(),
            'password' => 'secret123',
            'profiles_total' => 5,
            'status' => \App\Enums\AccountStatus::Active->value,
            'purchased_at' => now(),
            'cost' => fake()->randomFloat(2, 1, 10),
        ];
    }
}
