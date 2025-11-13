<?php

declare(strict_types=1);

namespace App\Application\Services;

use App\Domain\Repositories\ExternalAccessTokenRepositoryInterface;
use App\Domain\Services\ExternalPortalServiceInterface;
use App\Models\ExternalAccessToken;
use App\Models\Project;
use App\Models\Ticket;
use App\Models\TicketHistory;
use App\Models\TicketPriority;
use App\Models\TicketStatus;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;

class ExternalPortalService implements ExternalPortalServiceInterface
{
    public function __construct(
        private readonly ExternalAccessTokenRepositoryInterface $externalAccessTokens,
    ) {}

    public function resolveContext(string $token): array
    {
        $externalToken = $this->externalAccessTokens->findByToken($token);

        if ($externalToken instanceof ExternalAccessToken && ! $externalToken->relationLoaded('project')) {
            $externalToken->load('project');
        }

        if (! $externalToken instanceof ExternalAccessToken || ! $externalToken->project instanceof Project) {
            throw new ModelNotFoundException('External portal token not found.');
        }

        return [
            'token' => $externalToken,
            'project' => $externalToken->project,
        ];
    }

    public function verifyPassword(ExternalAccessToken $token, string $password): bool
    {
        return Hash::check($password, (string) $token->password);
    }

    public function markAccessed(ExternalAccessToken $token): void
    {
        $this->externalAccessTokens->update($token, [
            'last_accessed_at' => Carbon::now(),
        ]);
    }

    public function projectSummary(Project $project): array
    {
        $project->loadMissing(['ticketStatuses', 'tickets']);

        $ticketsQuery = Ticket::query()
            ->where('project_id', $project->getKey());

        $totalTickets = (int) $ticketsQuery->count();

        $completedTickets = (int) $ticketsQuery
            ->whereHas('status', static fn ($query) => $query->where('is_completed', true))
            ->count();

        $overdueTickets = (int) Ticket::query()
            ->where('project_id', $project->getKey())
            ->whereDate('due_date', '<', Carbon::today())
            ->whereHas('status', static fn ($query) => $query->where('is_completed', false))
            ->count();

        $newThisWeek = (int) Ticket::query()
            ->where('project_id', $project->getKey())
            ->where('created_at', '>=', Carbon::now()->subDays(7))
            ->count();

        $completedThisWeek = (int) Ticket::query()
            ->where('project_id', $project->getKey())
            ->where('updated_at', '>=', Carbon::now()->subDays(7))
            ->whereHas('status', static fn ($query) => $query->where('is_completed', true))
            ->count();

        $progress = $totalTickets > 0
            ? round($completedTickets / $totalTickets, 2)
            : 0.0;

        return [
            'total' => $totalTickets,
            'completed' => $completedTickets,
            'progress' => $progress,
            'overdue' => $overdueTickets,
            'new_this_week' => $newThisWeek,
            'completed_this_week' => $completedThisWeek,
        ];
    }

    /**
     * @return Collection<int, array{id: int, name: string, color: string|null, count: int}>
     *
     * @phpstan-return Collection<int, array{id: int, name: string, color: string|null, count: int}>
     */
    public function ticketsByStatus(Project $project): Collection
    {
        $statuses = TicketStatus::query()
            ->select(['id', 'name', 'color'])
            ->where('project_id', $project->getKey())
            ->withCount([
                'tickets as tickets_count' => function ($query) use ($project): void {
                    $query->where('project_id', $project->getKey());
                },
            ])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $mapped = $statuses->map(fn (TicketStatus $status): array => [
            'id' => (int) $status->getKey(),
            'name' => $status->name,
            'color' => $status->color ?? null,
            'count' => (int) $status->tickets_count,
        ]);

        // @phpstan-ignore-next-line
        return $mapped;
    }

    public function ticketPriorities(): Collection
    {
        return collect(
            TicketPriority::query()
                ->select(['id', 'name'])
                ->orderBy('name')
                ->get()
                ->map(fn ($priority): array => [
                    'id' => (int) $priority->getKey(),
                    'name' => $priority->name,
                ])
        );
    }

    /**
     * @return LengthAwarePaginator<int, Ticket>
     */
    public function paginatedTickets(Project $project, array $filters = []): LengthAwarePaginator
    {
        $query = Ticket::query()
            ->with(['status', 'priority', 'assignees'])
            ->where('project_id', $project->getKey())
            ->orderBy('id');

        if (! empty($filters['status_id'])) {
            $query->where('ticket_status_id', (int) $filters['status_id']);
        }

        if (! empty($filters['priority_id'])) {
            $query->where('priority_id', (int) $filters['priority_id']);
        }

        if (! empty($filters['search'])) {
            $search = (string) $filters['search'];

            $query->where(static function ($inner) use ($search): void {
                $inner->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('uuid', 'like', "%{$search}%");
            });
        }

        $pageName = $filters['page_name'] ?? 'tickets';

        return $query->paginate(10, ['*'], $pageName);
    }

    /**
     * @return LengthAwarePaginator<int, TicketHistory>
     */
    public function recentActivities(Project $project, array $filters = []): LengthAwarePaginator
    {
        $pageName = $filters['page_name'] ?? 'activities';

        return TicketHistory::query()
            ->with(['ticket', 'toStatus'])
            ->whereHas('ticket', static function ($query) use ($project): void {
                $query->where('project_id', $project->getKey());
            })
            ->orderByDesc('created_at')
            ->paginate(10, ['*'], $pageName);
    }
}
