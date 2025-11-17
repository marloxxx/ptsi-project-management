<?php

declare(strict_types=1);

namespace App\Domain\Repositories;

use App\Models\Project;
use App\Models\Sprint;
use Illuminate\Database\Eloquent\Collection;

interface SprintRepositoryInterface
{
    /**
     * @param  array<int, string>  $relations
     */
    public function find(int $id, array $relations = []): ?Sprint;

    /**
     * @return Collection<int, Sprint>
     */
    public function forProject(Project $project, ?string $state = null): Collection;

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(Project $project, array $data): Sprint;

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Sprint $sprint, array $data): Sprint;

    public function delete(Sprint $sprint): bool;

    public function getActiveSprint(Project $project): ?Sprint;

    /**
     * @param  array<int, int>  $ticketIds
     */
    public function assignTickets(Sprint $sprint, array $ticketIds): void;

    public function unassignTickets(Sprint $sprint): void;
}
