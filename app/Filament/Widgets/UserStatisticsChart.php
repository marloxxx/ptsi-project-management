<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Domain\Services\AnalyticsServiceInterface;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;

class UserStatisticsChart extends ChartWidget
{
    protected ?string $heading = 'User Assignment Statistics';

    protected static ?int $sort = 4;

    protected ?string $maxHeight = '300px';

    protected ?string $pollingInterval = '60s';

    protected int|string|array $columnSpan = [
        'md' => 2,
        'xl' => 1,
    ];

    protected AnalyticsServiceInterface $analyticsService;

    public function boot(AnalyticsServiceInterface $analyticsService): void
    {
        $this->analyticsService = $analyticsService;
    }

    protected function getData(): array
    {
        $user = Auth::user();

        if (! $user) {
            return ['datasets' => [], 'labels' => []];
        }

        $dataset = $this->analyticsService->getUserStatistics($user);

        return [
            'datasets' => [
                [
                    'label' => 'Projects',
                    'data' => $dataset['projects'] ?? [],
                    'backgroundColor' => '#184980',
                    'borderColor' => '#184980',
                    'borderWidth' => 1,
                ],
                [
                    'label' => 'Assigned Tickets',
                    'data' => $dataset['assignments'] ?? [],
                    'backgroundColor' => '#10B981',
                    'borderColor' => '#10B981',
                    'borderWidth' => 1,
                ],
            ],
            'labels' => $dataset['labels'] ?? [],
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'top',
                ],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                ],
            ],
            'responsive' => true,
            'maintainAspectRatio' => false,
        ];
    }
}
