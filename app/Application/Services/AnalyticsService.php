<?php

declare(strict_types=1);

namespace App\Application\Services;

use App\Domain\Repositories\AnalyticsRepositoryInterface;
use App\Domain\Services\AnalyticsServiceInterface;
use App\Models\TicketHistory;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class AnalyticsService implements AnalyticsServiceInterface
{
    public function __construct(
        private readonly AnalyticsRepositoryInterface $analyticsRepository,
    ) {}

    public function getOverviewStats(User $user): array
    {
        $counts = $this->analyticsRepository->getOverviewCounts($user);
        $scope = $counts['scope'] ?? 'member';

        if ($scope === 'super_admin') {
            return [
                [
                    'label' => 'Total Projects',
                    'value' => (int) ($counts['total_projects'] ?? 0),
                    'description' => 'Active projects in the system',
                    'icon' => 'heroicon-m-rectangle-stack',
                    'color' => 'primary',
                ],
                [
                    'label' => 'Total Tickets',
                    'value' => (int) ($counts['total_tickets'] ?? 0),
                    'description' => 'Tickets across all projects',
                    'icon' => 'heroicon-m-ticket',
                    'color' => 'success',
                ],
                [
                    'label' => 'My Assigned Tickets',
                    'value' => (int) ($counts['my_assigned_tickets'] ?? 0),
                    'description' => 'Tickets assigned to you',
                    'icon' => 'heroicon-m-user-circle',
                    'color' => 'info',
                ],
                [
                    'label' => 'Team Members',
                    'value' => (int) ($counts['team_members'] ?? 0),
                    'description' => 'Registered users',
                    'icon' => 'heroicon-m-users',
                    'color' => 'gray',
                ],
            ];
        }

        $assigned = (int) ($counts['my_assigned_tickets'] ?? 0);
        $assignedColor = $assigned > 10 ? 'danger' : ($assigned > 5 ? 'warning' : 'success');

        $overdue = (int) ($counts['my_overdue_tickets'] ?? 0);
        $overdueColor = $overdue > 0 ? 'danger' : 'success';

        return [
            [
                'label' => 'My Projects',
                'value' => (int) ($counts['my_projects'] ?? 0),
                'description' => 'Projects you are part of',
                'icon' => 'heroicon-m-rectangle-stack',
                'color' => 'primary',
            ],
            [
                'label' => 'My Assigned Tickets',
                'value' => $assigned,
                'description' => 'Tickets assigned to you',
                'icon' => 'heroicon-m-user-circle',
                'color' => $assignedColor,
            ],
            [
                'label' => 'My Created Tickets',
                'value' => (int) ($counts['my_created_tickets'] ?? 0),
                'description' => 'Tickets you created',
                'icon' => 'heroicon-m-pencil-square',
                'color' => 'info',
            ],
            [
                'label' => 'Project Tickets',
                'value' => (int) ($counts['project_tickets'] ?? 0),
                'description' => 'Total tickets in your projects',
                'icon' => 'heroicon-m-ticket',
                'color' => 'success',
            ],
            [
                'label' => 'Completed This Week',
                'value' => (int) ($counts['my_completed_this_week'] ?? 0),
                'description' => 'Your completed tickets',
                'icon' => 'heroicon-m-check-circle',
                'color' => ((int) ($counts['my_completed_this_week'] ?? 0)) > 0 ? 'success' : 'gray',
            ],
            [
                'label' => 'New Tasks This Week',
                'value' => (int) ($counts['new_tickets_this_week'] ?? 0),
                'description' => 'Created in your projects',
                'icon' => 'heroicon-m-plus-circle',
                'color' => 'info',
            ],
            [
                'label' => 'My Overdue Tasks',
                'value' => $overdue,
                'description' => 'Your past due tickets',
                'icon' => 'heroicon-m-exclamation-triangle',
                'color' => $overdueColor,
            ],
            [
                'label' => 'Team Members',
                'value' => (int) ($counts['team_members'] ?? 0),
                'description' => 'People in your projects',
                'icon' => 'heroicon-m-users',
                'color' => 'gray',
            ],
        ];
    }

    public function getTicketsPerProject(User $user): array
    {
        return $this->analyticsRepository->getTicketsPerProject($user);
    }

    public function getMonthlyTicketTrend(User $user): array
    {
        return $this->analyticsRepository->getMonthlyTicketTrend($user);
    }

    public function getUserStatistics(User $user): array
    {
        return $this->analyticsRepository->getUserStatistics($user);
    }

    /**
     * @return Builder<TicketHistory>
     */
    public function recentActivityQuery(User $user): Builder
    {
        return $this->analyticsRepository->recentActivityQuery($user);
    }
}
