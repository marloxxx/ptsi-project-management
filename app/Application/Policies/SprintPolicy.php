<?php

declare(strict_types=1);

namespace App\Application\Policies;

use App\Models\Sprint;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class SprintPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('sprints.view');
    }

    public function view(User $user, Sprint $sprint): bool
    {
        return $user->can('sprints.view') && $this->isProjectMember($user, $sprint);
    }

    public function create(User $user, ?Sprint $sprint = null): bool
    {
        // For RelationManager, sprint might be null
        // We'll check project membership in canCreate() method of RelationManager
        if ($sprint === null) {
            // Allow if user has sprint create permission
            // Project membership will be checked in RelationManager's canCreate()
            return $user->hasPermissionTo('sprints.create');
        }

        return $this->canManageSprint($user, $sprint);
    }

    public function update(User $user, Sprint $sprint): bool
    {
        return $this->canManageSprint($user, $sprint);
    }

    public function delete(User $user, Sprint $sprint): bool
    {
        return $this->canManageSprint($user, $sprint);
    }

    public function activate(User $user, Sprint $sprint): bool
    {
        return $this->canManageSprint($user, $sprint);
    }

    public function close(User $user, Sprint $sprint): bool
    {
        return $this->canManageSprint($user, $sprint);
    }

    public function reopen(User $user, Sprint $sprint): bool
    {
        return $this->canManageSprint($user, $sprint);
    }

    public function assignTickets(User $user, Sprint $sprint): bool
    {
        // Contributors can assign tickets they own or are allowed to move
        return $this->isProjectMember($user, $sprint);
    }

    protected function isProjectMember(User $user, Sprint $sprint): bool
    {
        if ($user->hasRole('super_admin')) {
            return true;
        }

        // Load project and members if not loaded
        if (! $sprint->relationLoaded('project')) {
            $sprint->load('project.members');
        }

        return $sprint->project->members->contains('id', $user->getKey());
    }

    protected function canManageSprint(User $user, Sprint $sprint): bool
    {
        if ($user->hasRole('super_admin')) {
            return true;
        }

        // Only project members with role Maintainer|Admin can manage sprints
        if (! $this->isProjectMember($user, $sprint)) {
            return false;
        }

        // Check if user has maintainer or admin role in project
        // For now, we'll check if user is a project member
        // TODO: Implement project-specific roles (Maintainer, Admin, Contributor)
        return true;
    }
}
