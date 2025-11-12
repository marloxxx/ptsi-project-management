<?php

declare(strict_types=1);

namespace App\Infrastructure\Repositories;

use App\Domain\Repositories\ProjectRepositoryInterface;
use App\Models\Project;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Arr;

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

    /**
     * @param  array<string, mixed>  $filters
     * @return Collection<int, Project>
     */
    public function accessibleForUser(int $userId, array $filters = []): Collection
    {
        $includeAll = (bool) Arr::get($filters, 'include_all', false);
        $requireSchedule = (bool) Arr::get($filters, 'require_schedule', false);
        /** @var array<int, string> $with */
        $with = Arr::get($filters, 'with', []);

        $query = Project::query()
            ->with($with)
            ->orderByRaw('pinned_at IS NULL')
            ->orderByDesc('pinned_at')
            ->orderBy('name');

        if ($requireSchedule) {
            $query->whereNotNull('start_date')
                ->whereNotNull('end_date');
        }

        if (! $includeAll) {
            $query->whereHas('members', function ($memberQuery) use ($userId): void {
                $memberQuery->where('user_id', $userId);
            });
        }

        return $query->get();
    }
}
