<?php

declare(strict_types=1);

namespace App\Domain\Repositories;

use App\Models\Project;
use App\Models\ProjectWorkflow;

interface ProjectWorkflowRepositoryInterface
{
    /**
     * @param  array<int, string>  $relations
     */
    public function find(int $id, array $relations = []): ?ProjectWorkflow;

    public function forProject(Project $project): ?ProjectWorkflow;

    /**
     * @param  array<string, mixed>  $data
     */
    public function createOrUpdate(Project $project, array $data): ProjectWorkflow;

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(ProjectWorkflow $workflow, array $data): ProjectWorkflow;

    public function delete(ProjectWorkflow $workflow): bool;
}
