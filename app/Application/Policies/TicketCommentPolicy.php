<?php

declare(strict_types=1);

namespace App\Application\Policies;

use App\Models\TicketComment;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class TicketCommentPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('tickets.view');
    }

    public function view(User $user, TicketComment $ticketComment): bool
    {
        return $user->can('tickets.view') && $this->isTicketAccessible($user, $ticketComment);
    }

    public function create(User $user, ?TicketComment $ticketComment = null): bool
    {
        // For RelationManager, ticketComment might be null
        // We'll check ticket/project membership in canCreate() method of RelationManager
        if ($ticketComment === null) {
            // Allow if user has tickets.comment permission
            // Ticket/project membership will be checked in RelationManager's canCreate()
            return $user->hasPermissionTo('tickets.comment');
        }

        return $this->canManageTicketComment($user, $ticketComment);
    }

    public function update(User $user, TicketComment $ticketComment): bool
    {
        return $this->canManageTicketComment($user, $ticketComment);
    }

    public function delete(User $user, TicketComment $ticketComment): bool
    {
        return $this->canManageTicketComment($user, $ticketComment);
    }

    protected function isTicketAccessible(User $user, TicketComment $ticketComment): bool
    {
        if ($user->hasRole('super_admin')) {
            return true;
        }

        // Load ticket and project with members if not loaded
        if (! $ticketComment->relationLoaded('ticket')) {
            $ticketComment->load('ticket.project.members');
        }

        return $ticketComment->ticket->project->members->contains('id', $user->getKey());
    }

    protected function canManageTicketComment(User $user, TicketComment $ticketComment): bool
    {
        if ($user->hasRole('super_admin')) {
            return true;
        }

        // Only project members can manage ticket comments
        if (! $this->isTicketAccessible($user, $ticketComment)) {
            return false;
        }

        // Check if user has maintainer or admin role in project
        // For now, we'll check if user is a project member
        // TODO: Implement project-specific roles (Maintainer, Admin, Contributor)
        return true;
    }
}
