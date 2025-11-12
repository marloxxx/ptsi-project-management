<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\TicketStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TicketStatus>
 */
class TicketStatusFactory extends Factory
{
    protected $model = TicketStatus::class;

    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'name' => $this->faker->unique()->word(),
            'color' => '#'.$this->faker->hexcolor(),
            'is_completed' => $this->faker->boolean(20),
            'sort_order' => $this->faker->numberBetween(0, 10),
        ];
    }
}
