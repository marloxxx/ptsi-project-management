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
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class AnalyticsRepository implements AnalyticsRepositoryInterface
{
    public function getOverviewCounts(User $user): array
    {
        $cacheKey = sprintf('analytics:overview:%d:%s', $user->getKey(), $user->hasRole('super_admin') ? 'admin' : 'member');
        $cacheTtl = 300; // 5 minutes

        return Cache::remember($cacheKey, $cacheTtl, function () use ($user): array {
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
        });
    }

    public function getTicketsPerProject(User $user): array
    {
        $cacheKey = sprintf('analytics:tickets-per-project:%d:%s', $user->getKey(), $user->hasRole('super_admin') ? 'admin' : 'member');
        $cacheTtl = 600; // 10 minutes

        return Cache::remember($cacheKey, $cacheTtl, function () use ($user): array {
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
        });
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
        $cacheKey = sprintf('analytics:user-statistics:%d:%s', $user->getKey(), $user->hasRole('super_admin') ? 'admin' : 'member');
        $cacheTtl = 600; // 10 minutes

        return Cache::remember($cacheKey, $cacheTtl, function () use ($user): array {
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
        });
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

    public function getCumulativeFlowDiagram(int $projectId, int $days = 30): array
    {
        $cacheKey = sprintf('analytics:cfd:%d:%d', $projectId, $days);
        $cacheTtl = 300; // 5 minutes

        return Cache::remember($cacheKey, $cacheTtl, function () use ($projectId, $days): array {
            $startDate = Carbon::now()->subDays($days)->startOfDay();
            $endDate = Carbon::now()->endOfDay();

            // Get all statuses for the project ordered by sort_order
            $statuses = \App\Models\TicketStatus::query()
                ->where('project_id', $projectId)
                ->orderBy('sort_order')
                ->get();

            if ($statuses->isEmpty()) {
                return [
                    'labels' => [],
                    'datasets' => [],
                ];
            }

            // Generate date labels
            $labels = [];
            $currentDate = $startDate->copy();
            while ($currentDate->lte($endDate)) {
                $labels[] = $currentDate->format('Y-m-d');
                $currentDate->addDay();
            }

            // Build datasets for each status
            $datasets = [];
            $colors = [
                '#3B82F6',
                '#10B981',
                '#F59E0B',
                '#EF4444',
                '#8B5CF6',
                '#EC4899',
                '#06B6D4',
                '#84CC16',
                '#F97316',
                '#6366F1',
            ];

            foreach ($statuses as $index => $status) {
                $data = [];
                $currentDate = $startDate->copy();

                while ($currentDate->lte($endDate)) {
                    // Count tickets in this status on or before this date
                    // Simplified approach: count tickets that are currently in this status
                    // or were moved to this status before the current date
                    $count = Ticket::query()
                        ->where('project_id', $projectId)
                        ->where(function (Builder $query) use ($status, $currentDate): void {
                            // Ticket created in this status before current date
                            $query->where(function (Builder $q) use ($status, $currentDate): void {
                                $q->where('ticket_status_id', $status->id)
                                    ->where('created_at', '<=', $currentDate->endOfDay());
                            })
                                // Or ticket moved to this status before current date
                                ->orWhereHas('histories', function (Builder $subQuery) use ($status, $currentDate): void {
                                    $subQuery->where('to_ticket_status_id', $status->id)
                                        ->where('created_at', '<=', $currentDate->endOfDay());
                                });
                        })
                        ->count();

                    $data[] = $count;
                    $currentDate->addDay();
                }

                $datasets[] = [
                    'label' => $status->name,
                    'data' => $data,
                    'backgroundColor' => $status->color ?? ($colors[$index % count($colors)] ?? '#6B7280'),
                ];
            }

            return [
                'labels' => $labels,
                'datasets' => $datasets,
            ];
        });
    }

    public function getLeadCycleTime(int $projectId, int $days = 30): array
    {
        $cacheKey = sprintf('analytics:lead-cycle-time:%d:%d', $projectId, $days);
        $cacheTtl = 300; // 5 minutes

        return Cache::remember($cacheKey, $cacheTtl, function () use ($projectId, $days): array {
            $startDate = Carbon::now()->subDays($days)->startOfDay();

            // Get completed tickets
            $completedStatuses = \App\Models\TicketStatus::query()
                ->where('project_id', $projectId)
                ->where('is_completed', true)
                ->pluck('id')
                ->toArray();

            if (empty($completedStatuses)) {
                return [
                    'lead_time' => ['avg' => 0.0, 'min' => 0.0, 'max' => 0.0],
                    'cycle_time' => ['avg' => 0.0, 'min' => 0.0, 'max' => 0.0],
                    'chart_data' => ['labels' => [], 'lead_times' => [], 'cycle_times' => []],
                ];
            }

            $completedTickets = Ticket::query()
                ->where('project_id', $projectId)
                ->whereIn('ticket_status_id', $completedStatuses)
                ->where('created_at', '>=', $startDate)
                ->with(['histories' => function ($query): void {
                    $query->orderBy('created_at');
                }])
                ->get();

            $leadTimes = [];
            $cycleTimes = [];
            $chartData = [];

            foreach ($completedTickets as $ticket) {
                // Lead time: from creation to completion
                $createdAt = Carbon::parse($ticket->created_at);
                $completedAt = Carbon::parse($ticket->updated_at);
                $leadTime = $createdAt->diffInDays($completedAt, true);
                $leadTimes[] = $leadTime;

                // Cycle time: from first "in progress" status to completion
                $firstInProgress = $ticket->histories()
                    ->whereNotNull('to_ticket_status_id')
                    ->orderBy('created_at')
                    ->first();

                if ($firstInProgress) {
                    $inProgressAt = Carbon::parse($firstInProgress->created_at);
                    $cycleTime = $inProgressAt->diffInDays($completedAt, true);
                    $cycleTimes[] = $cycleTime;
                } else {
                    // If no in-progress status, use lead time
                    $cycleTimes[] = $leadTime;
                }
            }

            $avgLeadTime = empty($leadTimes) ? 0.0 : (float) (array_sum($leadTimes) / count($leadTimes));
            $minLeadTime = empty($leadTimes) ? 0.0 : (float) min($leadTimes);
            $maxLeadTime = empty($leadTimes) ? 0.0 : (float) max($leadTimes);

            $avgCycleTime = empty($cycleTimes) ? 0.0 : (float) (array_sum($cycleTimes) / count($cycleTimes));
            $minCycleTime = empty($cycleTimes) ? 0.0 : (float) min($cycleTimes);
            $maxCycleTime = empty($cycleTimes) ? 0.0 : (float) max($cycleTimes);

            // Group chart data by week for better visualization
            $weeklyData = [];
            foreach ($completedTickets as $ticket) {
                $createdAt = Carbon::parse($ticket->created_at);
                $week = $createdAt->format('Y-W');
                if (! isset($weeklyData[$week])) {
                    $weeklyData[$week] = ['lead_times' => [], 'cycle_times' => []];
                }

                $leadTime = $createdAt->diffInDays(Carbon::parse($ticket->updated_at), true);
                $weeklyData[$week]['lead_times'][] = $leadTime;

                $firstInProgress = $ticket->histories()
                    ->whereNotNull('to_ticket_status_id')
                    ->orderBy('created_at')
                    ->first();

                if ($firstInProgress) {
                    $inProgressAt = Carbon::parse($firstInProgress->created_at);
                    $cycleTime = $inProgressAt->diffInDays(Carbon::parse($ticket->updated_at), true);
                    $weeklyData[$week]['cycle_times'][] = $cycleTime;
                } else {
                    $weeklyData[$week]['cycle_times'][] = $leadTime;
                }
            }

            $chartLabels = [];
            $chartLeadTimes = [];
            $chartCycleTimes = [];

            foreach ($weeklyData as $week => $data) {
                $chartLabels[] = $week;
                $chartLeadTimes[] = (float) (array_sum($data['lead_times']) / count($data['lead_times']));
                $chartCycleTimes[] = (float) (array_sum($data['cycle_times']) / count($data['cycle_times']));
            }

            return [
                'lead_time' => [
                    'avg' => round($avgLeadTime, 2),
                    'min' => round($minLeadTime, 2),
                    'max' => round($maxLeadTime, 2),
                ],
                'cycle_time' => [
                    'avg' => round($avgCycleTime, 2),
                    'min' => round($minCycleTime, 2),
                    'max' => round($maxCycleTime, 2),
                ],
                'chart_data' => [
                    'labels' => $chartLabels,
                    'lead_times' => $chartLeadTimes,
                    'cycle_times' => $chartCycleTimes,
                ],
            ];
        });
    }

    public function getThroughput(int $projectId, int $days = 30): array
    {
        $cacheKey = sprintf('analytics:throughput:%d:%d', $projectId, $days);
        $cacheTtl = 300; // 5 minutes

        return Cache::remember($cacheKey, $cacheTtl, function () use ($projectId, $days): array {
            $startDate = Carbon::now()->subDays($days)->startOfDay();
            $endDate = Carbon::now()->endOfDay();

            $completedStatuses = \App\Models\TicketStatus::query()
                ->where('project_id', $projectId)
                ->where('is_completed', true)
                ->pluck('id')
                ->toArray();

            if (empty($completedStatuses)) {
                return [
                    'labels' => [],
                    'data' => [],
                    'avg_per_day' => 0.0,
                    'total' => 0,
                ];
            }

            $driver = DB::getDriverName();

            if ($driver === 'pgsql') {
                $dateExpression = 'DATE(updated_at)';
            } elseif ($driver === 'sqlite') {
                $dateExpression = 'DATE(updated_at)';
            } else {
                $dateExpression = 'DATE(updated_at)';
            }

            $completedTickets = Ticket::query()
                ->select([
                    DB::raw("{$dateExpression} AS completion_date"),
                    DB::raw('COUNT(*) AS count'),
                ])
                ->where('project_id', $projectId)
                ->whereIn('ticket_status_id', $completedStatuses)
                ->where('updated_at', '>=', $startDate)
                ->where('updated_at', '<=', $endDate)
                ->groupBy('completion_date')
                ->orderBy('completion_date')
                ->get();

            // Generate all dates in range
            $labels = [];
            $data = [];
            $currentDate = $startDate->copy();
            $total = 0;

            while ($currentDate->lte($endDate)) {
                $dateStr = $currentDate->format('Y-m-d');
                $labels[] = $currentDate->format('M d');
                /** @var object{completion_date: string, count: int|string}|null $count */
                $count = $completedTickets->firstWhere('completion_date', $dateStr);
                $dayCount = $count ? (int) $count->count : 0;
                $data[] = $dayCount;
                $total += $dayCount;
                $currentDate->addDay();
            }

            $avgPerDay = $days > 0 ? round($total / $days, 2) : 0.0;

            return [
                'labels' => $labels,
                'data' => $data,
                'avg_per_day' => $avgPerDay,
                'total' => $total,
            ];
        });
    }

    public function getProjectBurndown(int $projectId, int $days = 30): array
    {
        $cacheKey = sprintf('analytics:project-burndown:%d:%d', $projectId, $days);
        $cacheTtl = 300; // 5 minutes

        return Cache::remember($cacheKey, $cacheTtl, function () use ($projectId, $days): array {
            $startDate = Carbon::now()->subDays($days)->startOfDay();
            $endDate = Carbon::now()->endOfDay();

            $completedStatuses = \App\Models\TicketStatus::query()
                ->where('project_id', $projectId)
                ->where('is_completed', true)
                ->pluck('id')
                ->toArray();

            // Get all tickets in the project
            $allTickets = Ticket::query()
                ->where('project_id', $projectId)
                ->where('created_at', '<=', $endDate)
                ->get();

            $totalTickets = $allTickets->count();

            if ($totalTickets === 0) {
                return [];
            }

            $burndownData = [];
            $currentDate = $startDate->copy();

            while ($currentDate->lte($endDate)) {
                // Count tickets that were completed on or before this date
                $completedCount = $allTickets->filter(function ($ticket) use ($currentDate, $completedStatuses): bool {
                    if (! in_array($ticket->ticket_status_id, $completedStatuses, true)) {
                        return false;
                    }

                    return Carbon::parse($ticket->updated_at)->lte($currentDate->endOfDay());
                })->count();

                $remaining = max(0, $totalTickets - $completedCount);

                // Ideal burndown (linear)
                $daysElapsed = $startDate->diffInDays($currentDate) + 1;
                $ideal = max(0, $totalTickets * (1 - ($daysElapsed / $days)));

                $burndownData[] = [
                    'date' => $currentDate->format('Y-m-d'),
                    'remaining' => $remaining,
                    'ideal' => round($ideal, 2),
                ];

                $currentDate->addDay();
            }

            return $burndownData;
        });
    }

    public function getProjectVelocity(int $projectId, int $periods = 8): array
    {
        $cacheKey = sprintf('analytics:project-velocity:%d:%d', $projectId, $periods);
        $cacheTtl = 300; // 5 minutes

        return Cache::remember($cacheKey, $cacheTtl, function () use ($projectId, $periods): array {
            $completedStatuses = \App\Models\TicketStatus::query()
                ->where('project_id', $projectId)
                ->where('is_completed', true)
                ->pluck('id')
                ->toArray();

            if (empty($completedStatuses)) {
                return [
                    'labels' => [],
                    'data' => [],
                    'avg_velocity' => 0.0,
                ];
            }

            // Get completed tickets grouped by week
            $driver = DB::getDriverName();

            if ($driver === 'pgsql') {
                $yearExpression = 'EXTRACT(YEAR FROM updated_at)';
                $weekExpression = 'EXTRACT(WEEK FROM updated_at)';
            } elseif ($driver === 'sqlite') {
                $yearExpression = "strftime('%Y', updated_at)";
                $weekExpression = "strftime('%W', updated_at)";
            } else {
                $yearExpression = 'YEAR(updated_at)';
                $weekExpression = 'WEEK(updated_at)';
            }

            $completedTickets = Ticket::query()
                ->select([
                    DB::raw("{$yearExpression} AS year"),
                    DB::raw("{$weekExpression} AS week"),
                    DB::raw('COUNT(*) AS count'),
                ])
                ->where('project_id', $projectId)
                ->whereIn('ticket_status_id', $completedStatuses)
                ->groupBy('year', 'week')
                ->orderBy('year', 'desc')
                ->orderBy('week', 'desc')
                ->limit($periods)
                ->get();

            $labels = [];
            $data = [];
            $total = 0;

            foreach ($completedTickets->reverse() as $ticket) {
                /** @var object{year: int|string, week: int|string, count: int|string} $ticket */
                $labels[] = sprintf('W%d/%d', (int) $ticket->week, (int) $ticket->year);
                $count = (int) $ticket->count;
                $data[] = $count;
                $total += $count;
            }

            $avgVelocity = $periods > 0 ? round($total / $periods, 2) : 0.0;

            return [
                'labels' => $labels,
                'data' => $data,
                'avg_velocity' => $avgVelocity,
            ];
        });
    }

    /**
     * Clear analytics cache for a specific user.
     */
    public function clearCacheForUser(User $user): void
    {
        $isSuperAdmin = $user->hasRole('super_admin');
        $roleSuffix = $isSuperAdmin ? 'admin' : 'member';

        Cache::forget(sprintf('analytics:overview:%d:%s', $user->getKey(), $roleSuffix));
        Cache::forget(sprintf('analytics:tickets-per-project:%d:%s', $user->getKey(), $roleSuffix));
        Cache::forget(sprintf('analytics:user-statistics:%d:%s', $user->getKey(), $roleSuffix));
        Cache::forget(sprintf('analytics:project-ids:%d', $user->getKey()));
    }

    /**
     * Clear analytics cache for all users (use with caution).
     */
    public function clearAllAnalyticsCache(): void
    {
        Cache::flush();
    }

    /**
     * @return array<int, int>
     */
    private function projectIdsForUser(User $user): array
    {
        $cacheKey = sprintf('analytics:project-ids:%d', $user->getKey());
        $cacheTtl = 300; // 5 minutes

        return Cache::remember($cacheKey, $cacheTtl, function () use ($user): array {
            return Project::query()
                ->whereHas('members', function (Builder $query) use ($user): void {
                    $query->where('user_id', $user->getKey());
                })
                ->pluck('id')
                ->all();
        });
    }
}
