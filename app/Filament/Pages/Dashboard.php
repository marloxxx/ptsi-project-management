<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Filament\Widgets\AccountWidget;
use App\Filament\Widgets\MonthlyTicketTrendChart;
use App\Filament\Widgets\RecentActivityTable;
use App\Filament\Widgets\StatsOverview;
use App\Filament\Widgets\TicketsPerProjectChart;
use App\Filament\Widgets\UserStatisticsChart;
use Filament\Pages\Dashboard as BaseDashboard;
use Illuminate\Support\Facades\Auth;

class Dashboard extends BaseDashboard
{
    public static function canAccess(): bool
    {
        return Auth::user()?->can('reports.view') ?? false;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    /**
     * @return array<int, string>
     */
    public function getWidgets(): array
    {
        return [
            AccountWidget::class,
            StatsOverview::class,
            TicketsPerProjectChart::class,
            UserStatisticsChart::class,
            MonthlyTicketTrendChart::class,
            RecentActivityTable::class,
        ];
    }

    /**
     * @return int|array<string, int>
     */
    public function getColumns(): int|array
    {
        return [
            'default' => 1,
            'md' => 2,
            'xl' => 3,
        ];
    }
}
