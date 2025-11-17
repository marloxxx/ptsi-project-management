<?php

declare(strict_types=1);

namespace App\Application\Policies;

use App\Models\Epic;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class EpicPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('epics.view');
    }

    public function view(User $user, Epic $epic): bool
    {
        return $user->can('epics.view') && $this->isProjectMember($user, $epic);
    }

    public function create(User $user, ?Epic $epic = null): bool
    {
        // For RelationManager, epic might be null
        // We'll check project membership in canCreate() method of RelationManager
        if ($epic === null) {
            // Allow if user has epic create permission
            // Project membership will be checked in RelationManager's canCreate()
            return $user->hasPermissionTo('epics.create');
        }

        return $this->canManageEpic($user, $epic);
    }

    public function update(User $user, Epic $epic): bool
    {
        return $this->canManageEpic($user, $epic);
    }

    public function delete(User $user, Epic $epic): bool
    {
        return $this->canManageEpic($user, $epic);
    }

    protected function isProjectMember(User $user, Epic $epic): bool
    {
        if ($user->hasRole('super_admin')) {
            return true;
        }

        // Load project and members if not loaded
        if (! $epic->relationLoaded('project')) {
            $epic->load('project.members');
        }

        return $epic->project->members->contains('id', $user->getKey());
    }

    protected function canManageEpic(User $user, Epic $epic): bool
    {
        if ($user->hasRole('super_admin')) {
            return true;
        }

        // Only project members can manage epics
        if (! $this->isProjectMember($user, $epic)) {
            return false;
        }

        // Check if user has maintainer or admin role in project
        // For now, we'll check if user is a project member
        // TODO: Implement project-specific roles (Maintainer, Admin, Contributor)
        return true;
    }
}
