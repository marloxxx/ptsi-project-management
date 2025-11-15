<?php

namespace Database\Factories;

use App\Models\Epic;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Epic>
 */
class EpicFactory extends Factory
{
    protected $model = Epic::class;

    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'name' => $this->faker->sentence(3),
            'description' => $this->faker->optional()->paragraph(),
            'start_date' => $this->faker->optional()->date(),
            'end_date' => $this->faker->optional()->dateTimeBetween('+1 week', '+1 month'),
            'sort_order' => $this->faker->numberBetween(0, 10),
        ];
    }
}
