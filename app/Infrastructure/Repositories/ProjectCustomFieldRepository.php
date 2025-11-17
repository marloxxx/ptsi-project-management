<?php

declare(strict_types=1);

namespace App\Infrastructure\Repositories;

use App\Domain\Repositories\ProjectCustomFieldRepositoryInterface;
use App\Models\ProjectCustomField;
use Illuminate\Database\Eloquent\Collection;

class ProjectCustomFieldRepository implements ProjectCustomFieldRepositoryInterface
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): ProjectCustomField
    {
        return ProjectCustomField::create($data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(ProjectCustomField $customField, array $data): ProjectCustomField
    {
        $customField->update($data);

        return $customField->fresh();
    }

    public function delete(ProjectCustomField $customField): bool
    {
        return (bool) $customField->delete();
    }

    public function find(int $id): ?ProjectCustomField
    {
        return ProjectCustomField::find($id);
    }

    /**
     * @return Collection<int, ProjectCustomField>
     */
    public function forProject(int $projectId): Collection
    {
        return ProjectCustomField::where('project_id', $projectId)
            ->orderBy('order')
            ->get();
    }

    /**
     * @return Collection<int, ProjectCustomField>
     */
    public function activeForProject(int $projectId): Collection
    {
        return ProjectCustomField::where('project_id', $projectId)
            ->where('active', true)
            ->orderBy('order')
            ->get();
    }

    public function findByKey(int $projectId, string $key): ?ProjectCustomField
    {
        return ProjectCustomField::where('project_id', $projectId)
            ->where('key', $key)
            ->first();
    }
}
