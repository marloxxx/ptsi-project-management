<?php

declare(strict_types=1);

namespace App\Infrastructure\Repositories;

use App\Domain\Repositories\ProjectRepositoryInterface;
use App\Models\Project;
use Illuminate\Database\Eloquent\Collection;

class ProjectRepository implements ProjectRepositoryInterface
{
    public function all(array $relations = []): Collection
    {
        return Project::with($relations)->get();
    }

    public function find(int $id, array $relations = []): ?Project
    {
        return Project::with($relations)->find($id);
    }

    public function create(array $data): Project
    {
        return Project::create($data);
    }

    public function update(Project $project, array $data): Project
    {
        $project->update($data);

        return $project->fresh();
    }

    public function delete(Project $project): bool
    {
        return (bool) $project->delete();
    }

    public function syncMembers(Project $project, array $memberIds): void
    {
        $project->members()->sync($memberIds);
    }
}

