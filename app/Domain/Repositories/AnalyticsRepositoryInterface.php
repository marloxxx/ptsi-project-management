<?php

declare(strict_types=1);

namespace App\Domain\Repositories;

use App\Models\TicketHistory;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

interface AnalyticsRepositoryInterface
{
    /**
     * Retrieve overview metrics scoped to the authenticated user.
     *
     * Expected keys differ based on role. When the user is a super admin the
     * payload contains `total_projects`, `total_tickets`, `team_members`, and
     * `my_assigned_tickets`. For regular members the payload includes
     * `my_projects`, `project_tickets`, `my_assigned_tickets`,
     * `my_created_tickets`, `new_tickets_this_week`, `my_overdue_tickets`,
     * `my_completed_this_week`, and `team_members`.
     *
     * @return array<string, int|string>
     */
    public function getOverviewCounts(User $user): array;

    /**
     * @return array{labels: array<int, string>, data: array<int, int>}
     */
    public function getTicketsPerProject(User $user): array;

    /**
     * @return array{labels: array<int, string>, data: array<int, int>}
     */
    public function getMonthlyTicketTrend(User $user): array;

    /**
     * @return array{labels: array<int, string>, projects: array<int, int>, assignments: array<int, int>}
     */
    public function getUserStatistics(User $user): array;

    /**
     * @return Builder<TicketHistory>
     */
    public function recentActivityQuery(User $user): Builder;
}
