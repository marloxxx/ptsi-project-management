<?php

declare(strict_types=1);

namespace App\Domain\Services;

use App\Models\Project;
use App\Models\Sprint;
use Illuminate\Database\Eloquent\Collection;

interface SprintServiceInterface
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(Project $project, array $data): Sprint;

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Sprint $sprint, array $data): Sprint;

    public function activate(Sprint $sprint): Sprint;

    public function close(Sprint $sprint): Sprint;

    public function reopen(Sprint $sprint): Sprint;

    /**
     * @param  array<int, int>  $ticketIds
     */
    public function assignTickets(Sprint $sprint, array $ticketIds): void;

    /**
     * Compute burndown data for a sprint.
     *
     * @return array<int, array{date: string, remaining: int, ideal: float}>
     */
    public function computeBurndown(Sprint $sprint): array;

    /**
     * Compute velocity (completed story points or ticket count) for a sprint.
     */
    public function computeVelocity(Sprint $sprint): float;

    /**
     * @return Collection<int, Sprint>
     */
    public function forProject(Project $project, ?string $state = null): Collection;
}
