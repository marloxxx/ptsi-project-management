<?php

declare(strict_types=1);

namespace App\Application\Services;

use App\Domain\Repositories\TicketCommentRepositoryInterface;
use App\Domain\Repositories\TicketRepositoryInterface;
use App\Domain\Services\GlobalSearchServiceInterface;
use App\Models\Ticket;
use App\Models\TicketComment;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;

class GlobalSearchService implements GlobalSearchServiceInterface
{
    public function __construct(
        protected TicketRepositoryInterface $ticketRepository,
        protected TicketCommentRepositoryInterface $ticketCommentRepository
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function search(string $query, ?int $projectId = null, array $filters = []): array
    {
        $user = Auth::user();
        if (! $user) {
            return [
                'tickets' => new Collection,
                'comments' => new Collection,
            ];
        }

        $searchTerm = trim($query);
        if (empty($searchTerm)) {
            return [
                'tickets' => new Collection,
                'comments' => new Collection,
            ];
        }

        // Search tickets
        $tickets = $this->searchTickets($searchTerm, $user, $projectId, $filters);

        // Search comments
        $comments = $this->searchComments($searchTerm, $user, $projectId, $filters);

        return [
            'tickets' => $tickets,
            'comments' => $comments,
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Collection<int, Ticket>
     */
    protected function searchTickets(string $query, User $user, ?int $projectId, array $filters): Collection
    {
        $ticketQuery = Ticket::query()
            ->where(function ($q) use ($query): void {
                $q->where('name', 'like', "%{$query}%")
                    ->orWhere('description', 'like', "%{$query}%")
                    ->orWhere('uuid', 'like', "%{$query}%");
            })
            ->whereHas('project.members', function ($memberQuery) use ($user): void {
                $memberQuery->where('user_id', $user->id);
            });

        if ($projectId) {
            $ticketQuery->where('project_id', $projectId);
        }

        // Apply additional filters if provided
        if (isset($filters['status_id'])) {
            $ticketQuery->where('ticket_status_id', $filters['status_id']);
        }

        if (isset($filters['priority_id'])) {
            $ticketQuery->where('priority_id', $filters['priority_id']);
        }

        if (isset($filters['assignee_id'])) {
            $ticketQuery->whereHas('assignees', function ($assigneeQuery) use ($filters): void {
                $assigneeQuery->where('user_id', $filters['assignee_id']);
            });
        }

        return $ticketQuery
            ->with(['project', 'status', 'priority', 'assignees'])
            ->orderBy('updated_at', 'desc')
            ->limit(50)
            ->get();
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Collection<int, TicketComment>
     */
    protected function searchComments(string $query, User $user, ?int $projectId, array $filters): Collection
    {
        $commentQuery = TicketComment::query()
            ->where('body', 'like', "%{$query}%")
            ->whereHas('ticket.project.members', function ($memberQuery) use ($user): void {
                $memberQuery->where('user_id', $user->id);
            });

        if ($projectId) {
            $commentQuery->whereHas('ticket', function ($ticketQuery) use ($projectId): void {
                $ticketQuery->where('project_id', $projectId);
            });
        }

        return $commentQuery
            ->with(['ticket.project', 'author'])
            ->orderBy('updated_at', 'desc')
            ->limit(50)
            ->get();
    }
}
