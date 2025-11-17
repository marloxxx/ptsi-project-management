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

    /**
     * Get Cumulative Flow Diagram data for a project.
     *
     * @param  int  $days  Number of days to look back
     * @return array{labels: array<int, string>, datasets: array<int, array{label: string, data: array<int, int>, backgroundColor: string}>}
     */
    public function getCumulativeFlowDiagram(int $projectId, int $days = 30): array;

    /**
     * Get Lead Time and Cycle Time metrics for a project.
     *
     * @param  int  $days  Number of days to look back
     * @return array{lead_time: array{avg: float, min: float, max: float}, cycle_time: array{avg: float, min: float, max: float}, chart_data: array{labels: array<int, string>, lead_times: array<int, float>, cycle_times: array<int, float>}}
     */
    public function getLeadCycleTime(int $projectId, int $days = 30): array;

    /**
     * Get Throughput metrics (tickets completed per time period).
     *
     * @param  int  $days  Number of days to look back
     * @return array{labels: array<int, string>, data: array<int, int>, avg_per_day: float, total: int}
     */
    public function getThroughput(int $projectId, int $days = 30): array;

    /**
     * Get project-level burndown chart data.
     *
     * @param  int  $days  Number of days to look back
     * @return array<int, array{date: string, remaining: int, ideal: float}>
     */
    public function getProjectBurndown(int $projectId, int $days = 30): array;

    /**
     * Get project-level velocity (completed tickets per period).
     *
     * @param  int  $periods  Number of periods to analyze
     * @return array{labels: array<int, string>, data: array<int, int>, avg_velocity: float}
     */
    public function getProjectVelocity(int $projectId, int $periods = 8): array;
}
