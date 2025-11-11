<?php

declare(strict_types=1);

namespace App\Domain\Repositories;

use App\Models\ProjectNote;
use Illuminate\Database\Eloquent\Collection;

interface ProjectNoteRepositoryInterface
{
    public function find(int $id): ?ProjectNote;

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): ProjectNote;

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(ProjectNote $note, array $data): ProjectNote;

    public function delete(ProjectNote $note): bool;

    /**
     * @return Collection<int, ProjectNote>
     */
    public function forProject(int $projectId): Collection;
}
