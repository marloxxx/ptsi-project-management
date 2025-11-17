<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Domain\Services\AnalyticsServiceInterface;
use App\Models\Project;
use Filament\Widgets\ChartWidget;

class ThroughputWidget extends ChartWidget
{
    protected ?string $heading = 'Throughput';

    protected ?string $description = 'Number of tickets completed per day';

    protected static ?int $sort = 4;

    protected ?string $maxHeight = '300px';

    protected ?string $pollingInterval = '60s';

    protected int|string|array $columnSpan = [
        'default' => 1,
        'md' => 2,
        'xl' => 1,
    ];

    protected AnalyticsServiceInterface $analyticsService;

    public ?int $projectId = null;

    public ?int $days = 30;

    public function boot(AnalyticsServiceInterface $analyticsService): void
    {
        $this->analyticsService = $analyticsService;
    }

    protected function getData(): array
    {
        $projectId = $this->getProjectId();

        if (! $projectId) {
            return [
                'datasets' => [],
                'labels' => [],
            ];
        }

        $data = $this->analyticsService->getThroughput($projectId, $this->days ?? 30);

        if (empty($data['labels'])) {
            return [
                'datasets' => [],
                'labels' => [],
            ];
        }

        return [
            'datasets' => [
                [
                    'label' => 'Completed Tickets',
                    'data' => $data['data'],
                    'backgroundColor' => '#10B981',
                    'borderColor' => '#059669',
                    'borderWidth' => 1,
                ],
            ],
            'labels' => $data['labels'],
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
                'tooltip' => [
                    'mode' => 'index',
                    'intersect' => false,
                ],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'title' => [
                        'display' => true,
                        'text' => 'Tickets Completed',
                    ],
                ],
                'x' => [
                    'title' => [
                        'display' => true,
                        'text' => 'Date',
                    ],
                ],
            ],
            'responsive' => true,
            'maintainAspectRatio' => false,
        ];
    }

    public function getHeading(): ?string
    {
        $projectId = $this->getProjectId();

        if (! $projectId) {
            return 'Throughput';
        }

        $data = $this->analyticsService->getThroughput($projectId, $this->days ?? 30);

        $avgPerDay = $data['avg_per_day'] ?? 0;
        $total = $data['total'] ?? 0;

        return sprintf(
            'Throughput (Avg: %.1f/day, Total: %d)',
            $avgPerDay,
            $total
        );
    }

    protected function getProjectId(): ?int
    {
        if ($this->projectId !== null) {
            return $this->projectId;
        }

        // Get project from route parameter
        $recordId = $this->getRecordId();

        if ($recordId !== null) {
            $project = Project::find($recordId);

            if ($project instanceof Project) {
                return $project->id;
            }
        }

        return null;
    }

    protected function getRecordId(): ?int
    {
        $route = request()->route();

        if ($route === null) {
            return null;
        }

        $parameters = $route->parameters();

        return isset($parameters['record']) ? (int) $parameters['record'] : null;
    }
}
