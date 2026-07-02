<?php

namespace Database\Factories;

use App\Models\WhatsappNumber;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WhatsappNumber>
 */
class WhatsappNumberFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'label' => 'Ventas',
            'phone_number_id' => (string) fake()->numerify('##############'),
            'display_number' => '521' . fake()->numerify('##########'),
            'access_token' => 'test-token',
            'is_default' => true,
            'active' => true,
        ];
    }
}
