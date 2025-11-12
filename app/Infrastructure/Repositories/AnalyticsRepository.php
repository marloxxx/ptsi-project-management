<?php

declare(strict_types=1);

namespace App\Infrastructure\Repositories;

use App\Domain\Repositories\AnalyticsRepositoryInterface;
use App\Models\Project;
use App\Models\Ticket;
use App\Models\TicketHistory;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class AnalyticsRepository implements AnalyticsRepositoryInterface
{
    public function getOverviewCounts(User $user): array
    {
        $isSuperAdmin = $user->hasRole('super_admin');

        if ($isSuperAdmin) {
            return [
                'scope' => 'super_admin',
                'total_projects' => (int) Project::count(),
                'total_tickets' => (int) Ticket::count(),
                'team_members' => (int) User::count(),
                'my_assigned_tickets' => (int) $this->assignedTicketsQuery($user->getKey())->count(),
            ];
        }

        $projectIds = $this->projectIdsForUser($user);

        return [
            'scope' => 'member',
            'my_projects' => (int) count($projectIds),
            'project_tickets' => (int) Ticket::query()
                ->whereIn('project_id', $projectIds)
                ->count(),
            'my_assigned_tickets' => (int) $this->assignedTicketsQuery($user->getKey())->count(),
            'my_created_tickets' => (int) Ticket::query()
                ->where('created_by', $user->getKey())
                ->count(),
            'new_tickets_this_week' => (int) Ticket::query()
                ->whereIn('project_id', $projectIds)
                ->where('created_at', '>=', Carbon::now()->subDays(7))
                ->count(),
            'my_overdue_tickets' => (int) $this->assignedTicketsQuery($user->getKey())
                ->whereNotNull('tickets.due_date')
                ->where('tickets.due_date', '<', Carbon::now())
                ->whereHas('status', function (Builder $query): void {
                    $query->where('is_completed', false);
                })
                ->count(),
            'my_completed_this_week' => (int) $this->assignedTicketsQuery($user->getKey())
                ->whereHas('status', function (Builder $query): void {
                    $query->where('is_completed', true);
                })
                ->where('tickets.updated_at', '>=', Carbon::now()->subDays(7))
                ->count(),
            'team_members' => (int) User::query()
                ->whereHas('projects', static function (Builder $query) use ($projectIds): void {
                    $query->whereIn('projects.id', $projectIds);
                })
                ->whereKeyNot($user->getKey())
                ->distinct()
                ->count('users.id'),
        ];
    }

    public function getTicketsPerProject(User $user): array
    {
        $isSuperAdmin = $user->hasRole('super_admin');

        $projectsQuery = Project::query()
            ->withCount('tickets')
            ->orderBy('name');

        if (! $isSuperAdmin) {
            $projectsQuery->whereHas('members', function (Builder $query) use ($user): void {
                $query->where('user_id', $user->getKey());
            });
        }

        $projects = $projectsQuery->get();

        return [
            'labels' => $projects->pluck('name')->all(),
            'data' => $projects->pluck('tickets_count')->map(static fn (int $count): int => $count)->all(),
        ];
    }

    public function getMonthlyTicketTrend(User $user): array
    {
        $isSuperAdmin = $user->hasRole('super_admin');

        $baseQuery = Ticket::query();

        if (! $isSuperAdmin) {
            $baseQuery->whereHas('project.members', function (Builder $query) use ($user): void {
                $query->where('user_id', $user->getKey());
            });
        }

        $earliestTicket = (clone $baseQuery)
            ->orderBy('created_at')
            ->first();

        if (! $earliestTicket) {
            return ['labels' => [], 'data' => []];
        }

        $startDate = Carbon::parse($earliestTicket->created_at)->startOfMonth();
        $endDate = Carbon::now()->endOfMonth();

        $months = collect();
        $current = $startDate->copy();
        while ($current->lte($endDate)) {
            $months->push($current->copy());
            $current->addMonth();
        }

        $driver = DB::getDriverName();

        if ($driver === 'pgsql') {
            $yearExpression = 'EXTRACT(YEAR FROM created_at)';
            $monthExpression = 'EXTRACT(MONTH FROM created_at)';
        } elseif ($driver === 'sqlite') {
            $yearExpression = "strftime('%Y', created_at)";
            $monthExpression = "strftime('%m', created_at)";
        } else {
            $yearExpression = 'YEAR(created_at)';
            $monthExpression = 'MONTH(created_at)';
        }

        $ticketsQuery = Ticket::query()
            ->select([
                DB::raw("{$yearExpression} AS year"),
                DB::raw("{$monthExpression} AS month"),
                DB::raw('COUNT(*) AS total'),
            ])
            ->where('created_at', '>=', $startDate)
            ->groupBy('year', 'month')
            ->orderBy('year')
            ->orderBy('month');

        if (! $isSuperAdmin) {
            $ticketsQuery->whereHas('project.members', function (Builder $query) use ($user): void {
                $query->where('user_id', $user->getKey());
            });
        }

        $monthlyCounts = $ticketsQuery->getQuery()->get()->keyBy(static function (object $row): string {
            return sprintf('%04d-%02d', (int) $row->year, (int) $row->month);
        });

        $labels = $months->map(static fn (Carbon $month): string => $month->format('M Y'))->all();
        $data = $months->map(static function (Carbon $month) use ($monthlyCounts): int {
            $key = $month->format('Y-m');
            $row = $monthlyCounts[$key] ?? null;

            if ($row === null) {
                return 0;
            }

            /** @var object{year: int|string, month: int|string, total: int|string} $row */
            return (int) $row->total;
        })->all();

        return [
            'labels' => $labels,
            'data' => $data,
        ];
    }

    public function getUserStatistics(User $user): array
    {
        $isSuperAdmin = $user->hasRole('super_admin');

        $usersQuery = User::query()
            ->withCount([
                'projects',
                'assignedTickets',
            ])
            ->orderBy('name');

        if (! $isSuperAdmin) {
            $usersQuery->whereKey($user->getKey());
        }

        $users = $usersQuery->get();

        return [
            'labels' => $users->pluck('name')->all(),
            'projects' => $users->pluck('projects_count')->map(static fn (int $count): int => $count)->all(),
            'assignments' => $users->pluck('assigned_tickets_count')->map(static fn (int $count): int => $count)->all(),
        ];
    }

    /**
     * @return Builder<TicketHistory>
     */
    public function recentActivityQuery(User $user): Builder
    {
        $query = TicketHistory::query()
            ->with(['ticket.project', 'actor', 'toStatus'])
            ->latest();

        if (! $user->hasRole('super_admin')) {
            $query->whereHas('ticket.project.members', function (Builder $subQuery) use ($user): void {
                $subQuery->where('user_id', $user->getKey());
            });
        }

        return $query;
    }

    /**
     * @return Builder<Ticket>
     */
    private function assignedTicketsQuery(int $userId): Builder
    {
        return Ticket::query()
            ->whereHas('assignees', function (Builder $query) use ($userId): void {
                $query->where('users.id', $userId);
            });
    }

    /**
     * @return array<int, int>
     */
    private function projectIdsForUser(User $user): array
    {
        return Project::query()
            ->whereHas('members', function (Builder $query) use ($user): void {
                $query->where('user_id', $user->getKey());
            })
            ->pluck('id')
            ->all();
    }
}
