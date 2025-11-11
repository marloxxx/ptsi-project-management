<?php

declare(strict_types=1);

namespace App\Infrastructure\Repositories;

use App\Domain\Repositories\ProjectRepositoryInterface;
use App\Models\Project;
use Illuminate\Database\Eloquent\Collection;

class ProjectRepository implements ProjectRepositoryInterface
{
    /**
     * @param  array<int, string>  $relations
     * @return Collection<int, Project>
     */
    public function all(array $relations = []): Collection
    {
        return Project::with($relations)->get();
    }

    /**
     * @param  array<int, string>  $relations
     */
    public function find(int $id, array $relations = []): ?Project
    {
        return Project::with($relations)->find($id);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Project
    {
        return Project::create($data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Project $project, array $data): Project
    {
        $project->update($data);

        return $project->fresh();
    }

    public function delete(Project $project): bool
    {
        return (bool) $project->delete();
    }

    /**
     * @param  array<int, int>  $memberIds
     */
    public function syncMembers(Project $project, array $memberIds): void
    {
        $project->members()->sync($memberIds);
    }
}
