<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Project;
use App\Models\ProjectWorkflow;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\ProjectWorkflow>
 */
class ProjectWorkflowFactory extends Factory
{
    protected $model = ProjectWorkflow::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'definition' => [
                'initial_statuses' => [],
                'transitions' => [],
            ],
        ];
    }

    /**
     * Create a workflow with simple transitions.
     *
     * @param  array<int>  $statusIds
     */
    public function withTransitions(array $statusIds): static
    {
        if (empty($statusIds)) {
            return $this;
        }

        $transitions = [];
        $initialStatuses = [$statusIds[0]];

        // Allow transitions: status[i] -> status[i+1]
        for ($i = 0; $i < count($statusIds) - 1; $i++) {
            $fromStatusId = (string) $statusIds[$i];
            $transitions[$fromStatusId] = [$statusIds[$i + 1]];
        }

        return $this->state(fn (array $attributes): array => [
            'definition' => [
                'initial_statuses' => $initialStatuses,
                'transitions' => $transitions,
            ],
        ]);
    }
}
