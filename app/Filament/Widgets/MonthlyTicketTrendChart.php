<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Domain\Services\AnalyticsServiceInterface;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;

class MonthlyTicketTrendChart extends ChartWidget
{
    protected ?string $heading = 'Monthly Ticket Trend';

    protected ?string $description = 'Ticket creation trend over time';

    protected static ?int $sort = 5;

    protected ?string $maxHeight = '300px';

    protected ?string $pollingInterval = '60s';

    protected int|string|array $columnSpan = [
        'md' => 2,
        'xl' => 2,
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

        $dataset = $this->analyticsService->getMonthlyTicketTrend($user);

        return [
            'datasets' => [
                [
                    'label' => 'Tickets Created',
                    'data' => $dataset['data'] ?? [],
                    'borderColor' => '#184980',
                    'backgroundColor' => 'rgba(24, 73, 128, 0.1)',
                    'borderWidth' => 2,
                    'fill' => true,
                    'tension' => 0.4,
                ],
            ],
            'labels' => $dataset['labels'] ?? [],
        ];
    }

    protected function getType(): string
    {
        return 'line';
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
