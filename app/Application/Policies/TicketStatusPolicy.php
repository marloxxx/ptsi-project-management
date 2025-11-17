<?php

declare(strict_types=1);

namespace App\Application\Policies;

use App\Models\TicketStatus;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class TicketStatusPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('projects.manage-statuses');
    }

    public function view(User $user, TicketStatus $ticketStatus): bool
    {
        return $user->can('projects.manage-statuses') && $this->isProjectMember($user, $ticketStatus);
    }

    public function create(User $user, ?TicketStatus $ticketStatus = null): bool
    {
        // For RelationManager, ticketStatus might be null
        // We'll check project membership in canCreate() method of RelationManager
        if ($ticketStatus === null) {
            // Allow if user has projects.manage-statuses permission
            // Project membership will be checked in RelationManager's canCreate()
            return $user->hasPermissionTo('projects.manage-statuses');
        }

        return $this->canManageTicketStatus($user, $ticketStatus);
    }

    public function update(User $user, TicketStatus $ticketStatus): bool
    {
        return $this->canManageTicketStatus($user, $ticketStatus);
    }

    public function delete(User $user, TicketStatus $ticketStatus): bool
    {
        return $this->canManageTicketStatus($user, $ticketStatus);
    }

    protected function isProjectMember(User $user, TicketStatus $ticketStatus): bool
    {
        if ($user->hasRole('super_admin')) {
            return true;
        }

        // Load project and members if not loaded
        if (! $ticketStatus->relationLoaded('project')) {
            $ticketStatus->load('project.members');
        }

        return $ticketStatus->project->members->contains('id', $user->getKey());
    }

    protected function canManageTicketStatus(User $user, TicketStatus $ticketStatus): bool
    {
        if ($user->hasRole('super_admin')) {
            return true;
        }

        // Only project members can manage ticket statuses
        if (! $this->isProjectMember($user, $ticketStatus)) {
            return false;
        }

        // Check if user has maintainer or admin role in project
        // For now, we'll check if user is a project member
        // TODO: Implement project-specific roles (Maintainer, Admin, Contributor)
        return true;
    }
}
