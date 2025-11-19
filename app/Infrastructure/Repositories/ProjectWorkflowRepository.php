<?php

declare(strict_types=1);

namespace App\Infrastructure\Repositories;

use App\Domain\Repositories\ProjectWorkflowRepositoryInterface;
use App\Models\Project;
use App\Models\ProjectWorkflow;

class ProjectWorkflowRepository implements ProjectWorkflowRepositoryInterface
{
    /**
     * @param  array<int, string>  $relations
     */
    public function find(int $id, array $relations = []): ?ProjectWorkflow
    {
        return ProjectWorkflow::with($relations)->find($id);
    }

    public function forProject(Project $project): ?ProjectWorkflow
    {
        return $project->workflow;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createOrUpdate(Project $project, array $data): ProjectWorkflow
    {
        /** @var ProjectWorkflow $workflow */
        $workflow = $project->workflow()->updateOrCreate(
            ['project_id' => $project->id],
            $data
        );

        return $workflow;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(ProjectWorkflow $workflow, array $data): ProjectWorkflow
    {
        $workflow->update($data);

        return $workflow->fresh();
    }

    public function delete(ProjectWorkflow $workflow): bool
    {
        return (bool) $workflow->delete();
    }
}
