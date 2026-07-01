<?php

namespace Database\Factories;

use App\Models\Subscription;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Subscription>
 */
class SubscriptionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'customer_id' => \App\Models\Customer::factory(),
            'profile_id' => null,
            'sale_id' => null,
            'starts_at' => now()->subDays(10),
            'expires_at' => now()->addDays(20),
            'status' => \App\Enums\SubscriptionStatus::Active->value,
            'reminder_sent' => false,
        ];
    }
}
