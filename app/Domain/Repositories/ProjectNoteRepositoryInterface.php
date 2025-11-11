<?php

declare(strict_types=1);

namespace App\Domain\Repositories;

use App\Models\ProjectNote;
use Illuminate\Database\Eloquent\Collection;

interface ProjectNoteRepositoryInterface
{
    public function find(int $id): ?ProjectNote;

    public function create(array $data): ProjectNote;

    public function update(ProjectNote $note, array $data): ProjectNote;

    public function delete(ProjectNote $note): bool;

    public function forProject(int $projectId): Collection;
}
