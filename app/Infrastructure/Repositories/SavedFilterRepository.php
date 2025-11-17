<?php

declare(strict_types=1);

namespace App\Infrastructure\Repositories;

use App\Domain\Repositories\SavedFilterRepositoryInterface;
use App\Models\SavedFilter;
use Illuminate\Database\Eloquent\Collection;

class SavedFilterRepository implements SavedFilterRepositoryInterface
{
    /**
     * @param  array<int, string>  $relations
     * @return Collection<int, SavedFilter>
     */
    public function all(array $relations = []): Collection
    {
        return SavedFilter::with($relations)->get();
    }

    /**
     * @param  array<int, string>  $relations
     */
    public function find(int $id, array $relations = []): ?SavedFilter
    {
        return SavedFilter::with($relations)->find($id);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): SavedFilter
    {
        return SavedFilter::create($data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(SavedFilter $savedFilter, array $data): SavedFilter
    {
        $savedFilter->update($data);

        return $savedFilter->fresh();
    }

    public function delete(SavedFilter $savedFilter): bool
    {
        return (bool) $savedFilter->delete();
    }

    /**
     * @param  array<int, string>  $relations
     * @return Collection<int, SavedFilter>
     */
    public function findByOwner(string $ownerType, int $ownerId, array $relations = []): Collection
    {
        return SavedFilter::with($relations)
            ->where('owner_type', $ownerType)
            ->where('owner_id', $ownerId)
            ->get();
    }

    /**
     * @param  array<int, string>  $relations
     * @return Collection<int, SavedFilter>
     */
    public function accessibleForUser(int $userId, ?int $projectId = null, array $relations = []): Collection
    {
        $query = SavedFilter::with($relations);

        // User's own filters (private)
        $query->where(function ($q) use ($userId, $projectId): void {
            $q->where(function ($subQ) use ($userId): void {
                $subQ->where('owner_type', \App\Models\User::class)
                    ->where('owner_id', $userId)
                    ->where('visibility', 'private');
            })
                // Public filters
                ->orWhere('visibility', 'public')
                // Team filters (same project members)
                ->orWhere(function ($subQ) use ($projectId): void {
                    $subQ->where('visibility', 'team');
                    if ($projectId) {
                        $subQ->where('project_id', $projectId);
                    }
                })
                // Project filters
                ->orWhere(function ($subQ) use ($projectId): void {
                    $subQ->where('visibility', 'project');
                    if ($projectId) {
                        $subQ->where('project_id', $projectId);
                    }
                });
        });

        return $query->get();
    }
}
