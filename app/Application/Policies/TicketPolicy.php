<?php

declare(strict_types=1);

namespace App\Application\Policies;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class TicketPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('tickets.view');
    }

    public function view(User $user, Ticket $ticket): bool
    {
        return $user->can('tickets.view') && $this->isTicketAccessible($user, $ticket);
    }

    public function create(User $user, ?Ticket $ticket = null): bool
    {
        // For Resource, ticket might be null
        // We'll check project membership in canCreate() method of Resource
        if ($ticket === null) {
            // Allow if user has tickets.create permission
            // Project membership will be checked in Resource's canCreate() or form validation
            return $user->hasPermissionTo('tickets.create');
        }

        return $this->canManageTicket($user, $ticket);
    }

    public function update(User $user, Ticket $ticket): bool
    {
        return $this->canManageTicket($user, $ticket);
    }

    public function delete(User $user, Ticket $ticket): bool
    {
        return $this->canManageTicket($user, $ticket);
    }

    protected function isTicketAccessible(User $user, Ticket $ticket): bool
    {
        if ($user->hasRole('super_admin')) {
            return true;
        }

        // Load project and members if not loaded
        if (! $ticket->relationLoaded('project')) {
            $ticket->load('project.members');
        }

        return $ticket->project->members->contains('id', $user->getKey());
    }

    protected function canManageTicket(User $user, Ticket $ticket): bool
    {
        if ($user->hasRole('super_admin')) {
            return true;
        }

        // Only project members can manage tickets
        if (! $this->isTicketAccessible($user, $ticket)) {
            return false;
        }

        // Check if user has maintainer or admin role in project
        // For now, we'll check if user is a project member
        // TODO: Implement project-specific roles (Maintainer, Admin, Contributor)
        return true;
    }
}
