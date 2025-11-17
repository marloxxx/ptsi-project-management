<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\Sprint;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Sprint>
 */
class SprintFactory extends Factory
{
    protected $model = Sprint::class;

    public function definition(): array
    {
        $startDate = $this->faker->dateTimeBetween('-1 month', '+1 month');
        $endDate = $this->faker->dateTimeBetween($startDate, '+2 months');

        return [
            'project_id' => Project::factory(),
            'name' => $this->faker->sentence(2),
            'goal' => $this->faker->optional()->sentence(),
            'state' => $this->faker->randomElement(['Planned', 'Active', 'Closed']),
            'start_date' => $startDate,
            'end_date' => $endDate,
            'closed_at' => null,
            'created_by' => User::factory(),
        ];
    }

    public function planned(): static
    {
        return $this->state(fn (array $attributes): array => [
            'state' => 'Planned',
            'closed_at' => null,
        ]);
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes): array => [
            'state' => 'Active',
            'closed_at' => null,
        ]);
    }

    public function closed(): static
    {
        return $this->state(function (array $attributes): array {
            $startDate = $attributes['start_date'] ?? '-1 week';
            $endDate = $attributes['end_date'] ?? 'now';

            // Ensure closed_at is after start_date
            $closedAt = $this->faker->dateTimeBetween($startDate, $endDate);

            return [
                'state' => 'Closed',
                'closed_at' => $closedAt,
            ];
        });
    }
}
