<?php

declare(strict_types=1);

namespace App\Domain\Repositories;

use App\Models\ProjectCustomField;
use Illuminate\Database\Eloquent\Collection;

interface ProjectCustomFieldRepositoryInterface
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): ProjectCustomField;

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(ProjectCustomField $customField, array $data): ProjectCustomField;

    public function delete(ProjectCustomField $customField): bool;

    public function find(int $id): ?ProjectCustomField;

    /**
     * @return Collection<int, ProjectCustomField>
     */
    public function forProject(int $projectId): Collection;

    /**
     * @return Collection<int, ProjectCustomField>
     */
    public function activeForProject(int $projectId): Collection;

    public function findByKey(int $projectId, string $key): ?ProjectCustomField;
}
