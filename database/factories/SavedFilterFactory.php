<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SavedFilter>
 */
class SavedFilterFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'owner_type' => \App\Models\User::class,
            'owner_id' => \App\Models\User::factory(),
            'name' => $this->faker->words(3, true),
            'query' => [
                'status' => $this->faker->randomElement(['open', 'in_progress', 'closed']),
                'priority' => $this->faker->randomElement(['low', 'medium', 'high']),
            ],
            'visibility' => $this->faker->randomElement(['private', 'team', 'project', 'public']),
            'project_id' => \App\Models\Project::factory(),
        ];
    }
}
