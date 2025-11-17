<?php

declare(strict_types=1);

namespace App\Infrastructure\Repositories;

use App\Domain\Repositories\SprintRepositoryInterface;
use App\Models\Project;
use App\Models\Sprint;
use Illuminate\Database\Eloquent\Collection;

class SprintRepository implements SprintRepositoryInterface
{
    /**
     * @param  array<int, string>  $relations
     */
    public function find(int $id, array $relations = []): ?Sprint
    {
        return Sprint::with($relations)->find($id);
    }

    /**
     * @return Collection<int, Sprint>
     */
    public function forProject(Project $project, ?string $state = null): Collection
    {
        $query = $project->sprints();

        if ($state !== null) {
            $query->where('state', $state);
        }

        return $query->orderBy('start_date', 'desc')->get();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(Project $project, array $data): Sprint
    {
        /** @var Sprint $sprint */
        $sprint = $project->sprints()->create($data);

        return $sprint;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Sprint $sprint, array $data): Sprint
    {
        $sprint->update($data);

        return $sprint->fresh();
    }

    public function delete(Sprint $sprint): bool
    {
        return (bool) $sprint->delete();
    }

    public function getActiveSprint(Project $project): ?Sprint
    {
        return $project->sprints()
            ->where('state', 'Active')
            ->first();
    }

    /**
     * @param  array<int, int>  $ticketIds
     */
    public function assignTickets(Sprint $sprint, array $ticketIds): void
    {
        $sprint->project->tickets()
            ->whereIn('id', $ticketIds)
            ->update(['sprint_id' => $sprint->getKey()]);
    }

    public function unassignTickets(Sprint $sprint): void
    {
        $sprint->tickets()->update(['sprint_id' => null]);
    }
}
