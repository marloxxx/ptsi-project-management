<?php

declare(strict_types=1);

namespace App\Domain\Repositories;

use App\Models\Project;
use Illuminate\Database\Eloquent\Collection;

interface ProjectRepositoryInterface
{
    public function all(array $relations = []): Collection;

    public function find(int $id, array $relations = []): ?Project;

    public function create(array $data): Project;

    public function update(Project $project, array $data): Project;

    public function delete(Project $project): bool;

    public function syncMembers(Project $project, array $memberIds): void;
}
