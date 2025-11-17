<?php

declare(strict_types=1);

namespace App\Domain\Services;

use App\Models\TicketHistory;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

interface AnalyticsServiceInterface
{
    /**
     * @return array<int, array{label: string, value: int, description?: string, icon?: string, color?: string}>
     */
    public function getOverviewStats(User $user): array;

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
