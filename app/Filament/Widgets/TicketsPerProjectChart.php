<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Domain\Services\AnalyticsServiceInterface;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;

class TicketsPerProjectChart extends ChartWidget
{
    protected ?string $heading = 'Tickets per Project';

    protected ?string $description = 'Distribution of tickets across all projects';

    protected static ?int $sort = 3;

    protected ?string $maxHeight = '300px';

    protected ?string $pollingInterval = '60s';

    protected int|string|array $columnSpan = [
        'default' => 1,
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

        $dataset = $this->analyticsService->getTicketsPerProject($user);

        $labels = $dataset['labels'] ?? [];
        $data = $dataset['data'] ?? [];

        $palette = [
            '#184980',
            '#09A8E1',
            '#00B0A8',
            '#F59E0B',
            '#E11D48',
            '#6366F1',
            '#10B981',
            '#EC4899',
            '#6B7280',
            '#14B8A6',
        ];

        $colors = collect($labels)
            ->keys()
            ->map(fn(int $index): string => $palette[$index % count($palette)])
            ->all();

        return [
            'datasets' => [
                [
                    'label' => 'Ticket Count',
                    'data' => $data,
                    'backgroundColor' => $colors,
                    'borderColor' => $colors,
                    'borderWidth' => 1,
                ],
            ],
            'labels' => $labels,
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
                    'display' => false,
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
