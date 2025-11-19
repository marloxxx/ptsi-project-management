<?php

namespace Database\Factories;

use App\Models\ProjectCustomField;
use App\Models\Ticket;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TicketCustomValue>
 */
class TicketCustomValueFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'ticket_id' => Ticket::factory(),
            'custom_field_id' => ProjectCustomField::factory(),
            'value' => null, // Will be set based on custom field type in state
        ];
    }

    /**
     * Set value based on custom field type.
     */
    public function withValue(mixed $value): static
    {
        return $this->state(fn (array $attributes) => [
            'value' => $value,
        ]);
    }
}
