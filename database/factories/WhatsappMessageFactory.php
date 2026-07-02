<?php

namespace Database\Factories;

use App\Models\WhatsappMessage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WhatsappMessage>
 */
class WhatsappMessageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'to' => '521' . fake()->numerify('##########'),
            'direction' => 'outbound',
            'type' => 'template',
            'body' => 'mensaje de prueba',
            'status' => 'sent',
        ];
    }
}
