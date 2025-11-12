<?php

declare(strict_types=1);

namespace App\Application\Services;

use App\Domain\Repositories\TicketCommentRepositoryInterface;
use App\Domain\Repositories\TicketHistoryRepositoryInterface;
use App\Domain\Repositories\TicketRepositoryInterface;
use App\Domain\Repositories\TicketStatusRepositoryInterface;
use App\Domain\Services\TicketServiceInterface;
use App\Models\Ticket;
use App\Models\TicketComment;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class TicketService implements TicketServiceInterface
{
    public function __construct(
        protected TicketRepositoryInterface $ticketRepository,
        protected TicketStatusRepositoryInterface $ticketStatusRepository,
        protected TicketHistoryRepositoryInterface $ticketHistoryRepository,
        protected TicketCommentRepositoryInterface $ticketCommentRepository
    ) {}

    /**
     * @param  array<string, mixed>  $data
     * @param  array<int, int>  $assigneeIds
     */
    public function create(array $data, array $assigneeIds = []): Ticket
    {
        return DB::transaction(function () use ($data, $assigneeIds) {
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

            $originalStatusId = $ticket->ticket_status_id;

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

            $previousStatusId = (int) $ticket->ticket_status_id;

            if ($previousStatusId === $statusId) {
                return $ticket;
            }

            $status = $this->ticketStatusRepository->find($statusId);

            if (! $status) {
                throw new RuntimeException('Target status not found.');
            }

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

        $payload = array_merge($data, [
            'ticket_id' => $ticket->id,
            'user_id' => $data['user_id'] ?? Auth::id(),
        ]);

        if (! $payload['user_id']) {
            throw new RuntimeException('Comment author is required.');
        }

        return $this->ticketCommentRepository->create($payload);
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
}
