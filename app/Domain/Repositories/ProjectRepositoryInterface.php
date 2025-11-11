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
}
