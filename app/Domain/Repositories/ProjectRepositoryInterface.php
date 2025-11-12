<?php

declare(strict_types=1);

namespace App\Domain\Repositories;

use App\Models\Project;
use Illuminate\Database\Eloquent\Collection;

interface ProjectRepositoryInterface
{
    /**
     * @param  array<int, string>  $relations
     * @return Collection<int, Project>
     */
    public function all(array $relations = []): Collection;

    /**
     * @param  array<int, string>  $relations
     */
    public function find(int $id, array $relations = []): ?Project;

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Project;

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Project $project, array $data): Project;

    public function delete(Project $project): bool;

    /**
     * @param  array<int, int>  $memberIds
     */
    public function syncMembers(Project $project, array $memberIds): void;

    /**
     * Retrieve projects accessible to a user, ordered for board/timeline usage.
     *
     * @param  array<string, mixed>  $filters
     *                                         - include_all: bool (default false) include all projects regardless of membership
     *                                         - require_schedule: bool (default false) only include projects with both start_date and end_date
     *                                         - with: array<int, string> (default []) relationships to eager load
     * @return Collection<int, Project>
     */
    public function accessibleForUser(int $userId, array $filters = []): Collection;
}
