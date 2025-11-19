<?php

namespace Database\Factories;

use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ProjectCustomField>
 */
class ProjectCustomFieldFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $type = $this->faker->randomElement(['text', 'number', 'select', 'date']);

        return [
            'project_id' => Project::factory(),
            'key' => $this->faker->unique()->slug(),
            'label' => $this->faker->words(2, true),
            'type' => $type,
            'options' => $type === 'select' ? [
                $this->faker->word(),
                $this->faker->word(),
                $this->faker->word(),
            ] : null,
            'required' => $this->faker->boolean(30),
            'order' => $this->faker->numberBetween(0, 100),
            'active' => true,
        ];
    }
}
