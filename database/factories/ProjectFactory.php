<?php

namespace Database\Factories;

use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Project>
 */
class ProjectFactory extends Factory
{
    protected $model = Project::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->sentence(3),
            'description' => $this->faker->optional()->paragraph(),
            'ticket_prefix' => strtoupper($this->faker->lexify('???')),
            'color' => '#'.$this->faker->hexcolor(),
            'start_date' => $this->faker->optional()->date(),
            'end_date' => $this->faker->optional()->dateTimeBetween('+1 week', '+1 month'),
            'pinned_at' => null,
        ];
    }
}
