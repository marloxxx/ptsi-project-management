<?php

declare(strict_types=1);

namespace App\Application\Services;

use App\Domain\Repositories\ProjectWorkflowRepositoryInterface;
use App\Domain\Repositories\TicketCommentRepositoryInterface;
use App\Domain\Repositories\TicketHistoryRepositoryInterface;
use App\Domain\Repositories\TicketRepositoryInterface;
use App\Domain\Repositories\TicketStatusRepositoryInterface;
use App\Domain\Services\TicketServiceInterface;
use App\Models\Project;
use App\Models\Ticket;
use App\Models\TicketComment;
use App\Models\User;
use App\Notifications\TicketCommentAdded;
use App\Notifications\TicketCommentUpdated;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use RuntimeException;

class TicketService implements TicketServiceInterface
{
    public function __construct(
        protected TicketRepositoryInterface $ticketRepository,
        protected TicketStatusRepositoryInterface $ticketStatusRepository,
        protected TicketHistoryRepositoryInterface $ticketHistoryRepository,
        protected TicketCommentRepositoryInterface $ticketCommentRepository,
        protected ProjectWorkflowRepositoryInterface $projectWorkflowRepository
    ) {}

    /**
     * @param  array<string, mixed>  $data
     * @param  array<int, int>  $assigneeIds
     */
    public function create(array $data, array $assigneeIds = []): Ticket
    {
        return DB::transaction(function () use ($data, $assigneeIds) {
            // Validate initial status against workflow if workflow exists
            if (isset($data['ticket_status_id']) && isset($data['project_id'])) {
                $this->validateTransition(null, (int) $data['ticket_status_id'], (int) $data['project_id']);
            }

            /** @var Ticket $ticket */
            $ticket = $this->ticketRepository->create($data);

            if (! empty($assigneeIds)) {
                $this->ticketRepository->syncAssignees($ticket, $assigneeIds);
            }

            $this->recordStatusChange(
                $ticket,
                null,
                (int) $ticket->ticket_status_id,
                'Ticket created'
            );

            return $ticket->fresh(['assignees', 'status', 'project']);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<int, int>|null  $assigneeIds
     */
    public function update(int $ticketId, array $data, ?array $assigneeIds = null): Ticket
    {
        return DB::transaction(function () use ($ticketId, $data, $assigneeIds) {
            $ticket = $this->findTicketOrFail($ticketId);
            $ticket->loadMissing(['project']);

            $originalStatusId = $ticket->ticket_status_id;

            // Validate transition if status is being changed
            if (array_key_exists('ticket_status_id', $data) && (int) $data['ticket_status_id'] !== (int) $originalStatusId) {
                $this->validateTransition(
                    (int) $originalStatusId,
                    (int) $data['ticket_status_id'],
                    (int) $ticket->project_id
                );
            }

            $ticket = $this->ticketRepository->update($ticket, $data);

            if ($assigneeIds !== null) {
                $this->ticketRepository->syncAssignees($ticket, $assigneeIds);
            }

            if (array_key_exists('ticket_status_id', $data) && (int) $data['ticket_status_id'] !== (int) $originalStatusId) {
                $this->recordStatusChange(
                    $ticket,
                    (int) $originalStatusId,
                    (int) $data['ticket_status_id'],
                    $data['status_note'] ?? null
                );
            }

            return $ticket->fresh(['assignees', 'status', 'priority', 'epic']);
        });
    }

    public function delete(int $ticketId): bool
    {
        $ticket = $this->findTicketOrFail($ticketId);

        return $this->ticketRepository->delete($ticket);
    }

    public function changeStatus(int $ticketId, int $statusId, ?string $note = null): Ticket
    {
        return DB::transaction(function () use ($ticketId, $statusId, $note) {
            $ticket = $this->findTicketOrFail($ticketId);
            $ticket->loadMissing(['project']);

            $previousStatusId = (int) $ticket->ticket_status_id;

            if ($previousStatusId === $statusId) {
                return $ticket;
            }

            $status = $this->ticketStatusRepository->find($statusId);

            if (! $status) {
                throw new RuntimeException('Target status not found.');
            }

            // Validate transition against workflow
            $this->validateTransition($previousStatusId, $statusId, (int) $ticket->project_id);

            $ticket = $this->ticketRepository->update($ticket, [
                'ticket_status_id' => $statusId,
            ]);

            $this->recordStatusChange($ticket, $previousStatusId, $statusId, $note);

            return $ticket->fresh(['status']);
        });
    }

    /**
     * @param  array<int, int>  $userIds
     */
    public function assignUsers(int $ticketId, array $userIds): Ticket
    {
        $ticket = $this->findTicketOrFail($ticketId);

        $this->ticketRepository->syncAssignees($ticket, $userIds);

        return $ticket->fresh(['assignees']);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function addComment(int $ticketId, array $data): TicketComment
    {
        $ticket = $this->findTicketOrFail($ticketId);
        $ticket->loadMissing(['project', 'creator', 'assignees']);

        $payload = array_merge($data, [
            'ticket_id' => $ticket->id,
            'user_id' => $data['user_id'] ?? Auth::id(),
        ]);

        if (! $payload['user_id']) {
            throw new RuntimeException('Comment author is required.');
        }

        $comment = $this->ticketCommentRepository->create($payload);
        $comment->load(['author', 'ticket.project', 'ticket.creator', 'ticket.assignees']);

        $this->notifyCommentAdded($comment);

        return $comment;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateComment(int $commentId, array $data): TicketComment
    {
        $comment = $this->ticketCommentRepository->find($commentId, [
            'ticket.project',
            'ticket.creator',
            'ticket.assignees',
            'author',
        ]);

        if (! $comment instanceof TicketComment) {
            throw new RuntimeException('Ticket comment not found.');
        }

        $comment = $this->ticketCommentRepository->update($comment, $data);
        $comment->load(['author', 'ticket.project', 'ticket.creator', 'ticket.assignees']);

        $this->notifyCommentUpdated($comment);

        return $comment;
    }

    public function deleteComment(int $commentId): bool
    {
        $comment = $this->ticketCommentRepository->find($commentId);

        if (! $comment) {
            return false;
        }

        return $this->ticketCommentRepository->delete($comment);
    }

    protected function findTicketOrFail(int $ticketId): Ticket
    {
        $ticket = $this->ticketRepository->find($ticketId, ['assignees', 'status']);

        if (! $ticket) {
            throw new RuntimeException('Ticket not found.');
        }

        return $ticket;
    }

    /**
     * Validate if a status transition is allowed according to the project workflow.
     *
     * @throws RuntimeException
     */
    protected function validateTransition(?int $fromStatusId, int $toStatusId, int $projectId): void
    {
        $workflow = $this->projectWorkflowRepository->forProject(
            Project::findOrFail($projectId)
        );

        // If no workflow defined, allow all transitions (backward compatible)
        if (! $workflow) {
            return;
        }

        if (! $workflow->isTransitionAllowed($fromStatusId, $toStatusId)) {
            $fromStatus = $fromStatusId ? $this->ticketStatusRepository->find($fromStatusId) : null;
            $fromStatusName = ($fromStatus !== null ? $fromStatus->name : null) ?? ($fromStatusId ? 'Unknown' : 'Initial');
            $toStatus = $this->ticketStatusRepository->find($toStatusId);
            $toStatusName = ($toStatus !== null ? $toStatus->name : null) ?? 'Unknown';

            throw new RuntimeException(
                sprintf(
                    'Transition from "%s" to "%s" is not allowed by the project workflow.',
                    $fromStatusName,
                    $toStatusName
                )
            );
        }
    }

    protected function recordStatusChange(Ticket $ticket, ?int $fromStatusId, int $toStatusId, ?string $note = null): void
    {
        $this->ticketHistoryRepository->create([
            'ticket_id' => $ticket->id,
            'user_id' => Auth::id(),
            'from_ticket_status_id' => $fromStatusId,
            'to_ticket_status_id' => $toStatusId,
            'note' => $note,
        ]);
    }

    private function notifyCommentAdded(TicketComment $comment): void
    {
        $ticket = $comment->ticket;

        if (! $ticket instanceof Ticket) {
            $ticket = $this->findTicketOrFail((int) $comment->ticket_id);
            $ticket->loadMissing(['project', 'creator', 'assignees']);
            $comment->setRelation('ticket', $ticket);
        }

        $recipients = $this->resolveCommentRecipients($ticket, (int) $comment->user_id);

        if ($recipients->isEmpty()) {
            return;
        }

        Notification::send($recipients, new TicketCommentAdded($ticket, $comment));
    }

    private function notifyCommentUpdated(TicketComment $comment): void
    {
        $ticket = $comment->ticket;

        if (! $ticket instanceof Ticket) {
            $ticket = $this->findTicketOrFail((int) $comment->ticket_id);
            $ticket->loadMissing(['project', 'creator', 'assignees']);
            $comment->setRelation('ticket', $ticket);
        }

        $recipients = $this->resolveCommentRecipients($ticket, (int) $comment->user_id);

        if ($recipients->isEmpty()) {
            return;
        }

        Notification::send($recipients, new TicketCommentUpdated($ticket, $comment));
    }

    /**
     * @return Collection<int, User>
     */
    private function resolveCommentRecipients(Ticket $ticket, int $actorId): Collection
    {
        $recipients = collect();

        if ($ticket->creator instanceof User && $ticket->creator->getKey() !== $actorId) {
            $recipients->push($ticket->creator);
        }

        $assignedUsers = $ticket->assignees()
            ->where('users.id', '!=', $actorId)
            ->get();

        $commenters = $ticket->comments()
            ->where('ticket_comments.user_id', '!=', $actorId)
            ->with('author')
            ->get()
            ->map(fn (TicketComment $comment): ?User => $comment->author)
            ->filter();

        return $recipients
            ->merge($assignedUsers)
            ->merge($commenters)
            ->filter(fn ($user): bool => $user instanceof User)
            ->unique(fn (User $user): int => (int) $user->getKey())
            ->values();
    }
}
