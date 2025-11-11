<?php

declare(strict_types=1);

namespace App\Infrastructure\Repositories;

use App\Domain\Repositories\ProjectNoteRepositoryInterface;
use App\Models\ProjectNote;
use Illuminate\Database\Eloquent\Collection;

class ProjectNoteRepository implements ProjectNoteRepositoryInterface
{
    public function find(int $id): ?ProjectNote
    {
        return ProjectNote::find($id);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): ProjectNote
    {
        return ProjectNote::create($data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(ProjectNote $note, array $data): ProjectNote
    {
        $note->update($data);

        return $note->fresh();
    }

    public function delete(ProjectNote $note): bool
    {
        return (bool) $note->delete();
    }

    /**
     * @return Collection<int, ProjectNote>
     */
    public function forProject(int $projectId): Collection
    {
        return ProjectNote::where('project_id', $projectId)
            ->orderByDesc('note_date')
            ->get();
    }
}
