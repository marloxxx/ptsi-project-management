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
}
