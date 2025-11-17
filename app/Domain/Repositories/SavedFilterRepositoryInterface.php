<?php

declare(strict_types=1);

namespace App\Domain\Repositories;

use App\Models\SavedFilter;
use Illuminate\Database\Eloquent\Collection;

interface SavedFilterRepositoryInterface
{
    /**
     * @param  array<int, string>  $relations
     * @return Collection<int, SavedFilter>
     */
    public function all(array $relations = []): Collection;

    /**
     * @param  array<int, string>  $relations
     */
    public function find(int $id, array $relations = []): ?SavedFilter;

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): SavedFilter;

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(SavedFilter $savedFilter, array $data): SavedFilter;

    public function delete(SavedFilter $savedFilter): bool;

    /**
     * Get saved filters for a specific owner (user or project).
     *
     * @param  array<int, string>  $relations
     * @return Collection<int, SavedFilter>
     */
    public function findByOwner(string $ownerType, int $ownerId, array $relations = []): Collection;

    /**
     * Get saved filters accessible to a user (based on visibility).
     *
     * @param  array<int, string>  $relations
     * @return Collection<int, SavedFilter>
     */
    public function accessibleForUser(int $userId, ?int $projectId = null, array $relations = []): Collection;
}
