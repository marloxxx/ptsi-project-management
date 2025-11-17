<?php

declare(strict_types=1);

namespace App\Application\Policies;

use App\Models\ProjectNote;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ProjectNotePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('project-notes.view');
    }

    public function view(User $user, ProjectNote $projectNote): bool
    {
        return $user->can('project-notes.view') && $this->isProjectMember($user, $projectNote);
    }

    public function create(User $user, ?ProjectNote $projectNote = null): bool
    {
        // For RelationManager, projectNote might be null
        // We'll check project membership in canCreate() method of RelationManager
        if ($projectNote === null) {
            // Allow if user has project-notes create permission
            // Project membership will be checked in RelationManager's canCreate()
            return $user->hasPermissionTo('project-notes.create');
        }

        return $this->canManageProjectNote($user, $projectNote);
    }

    public function update(User $user, ProjectNote $projectNote): bool
    {
        return $this->canManageProjectNote($user, $projectNote);
    }

    public function delete(User $user, ProjectNote $projectNote): bool
    {
        return $this->canManageProjectNote($user, $projectNote);
    }

    protected function isProjectMember(User $user, ProjectNote $projectNote): bool
    {
        if ($user->hasRole('super_admin')) {
            return true;
        }

        // Load project and members if not loaded
        if (! $projectNote->relationLoaded('project')) {
            $projectNote->load('project.members');
        }

        return $projectNote->project->members->contains('id', $user->getKey());
    }

    protected function canManageProjectNote(User $user, ProjectNote $projectNote): bool
    {
        if ($user->hasRole('super_admin')) {
            return true;
        }

        // Only project members can manage project notes
        if (! $this->isProjectMember($user, $projectNote)) {
            return false;
        }

        // Check if user has maintainer or admin role in project
        // For now, we'll check if user is a project member
        // TODO: Implement project-specific roles (Maintainer, Admin, Contributor)
        return true;
    }
}
