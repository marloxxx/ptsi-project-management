<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Domain\Services\AnalyticsServiceInterface;
use App\Models\Project;
use Filament\Widgets\ChartWidget;

class ProjectBurndownWidget extends ChartWidget
{
    protected ?string $heading = 'Project Burndown';

    protected ?string $description = 'Track remaining work over time for the entire project';

    protected static ?int $sort = 5;

    protected ?string $maxHeight = '300px';

    protected ?string $pollingInterval = '60s';

    protected int|string|array $columnSpan = [
        'default' => 1,
        'md' => 2,
        'xl' => 2,
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

        $burndownData = $this->analyticsService->getProjectBurndown($projectId, $this->days ?? 30);

        if (empty($burndownData)) {
            return [
                'datasets' => [],
                'labels' => [],
            ];
        }

        $labels = array_column($burndownData, 'date');
        $remaining = array_column($burndownData, 'remaining');
        $ideal = array_column($burndownData, 'ideal');

        // Format labels to be more readable
        $formattedLabels = array_map(function (string $date): string {
            return \Carbon\Carbon::parse($date)->format('M d');
        }, $labels);

        return [
            'datasets' => [
                [
                    'label' => 'Remaining',
                    'data' => $remaining,
                    'borderColor' => '#E11D48',
                    'backgroundColor' => 'rgba(225, 29, 72, 0.1)',
                    'borderWidth' => 2,
                    'fill' => true,
                    'tension' => 0.4,
                ],
                [
                    'label' => 'Ideal',
                    'data' => $ideal,
                    'borderColor' => '#10B981',
                    'backgroundColor' => 'rgba(16, 185, 129, 0.1)',
                    'borderWidth' => 2,
                    'borderDash' => [5, 5],
                    'fill' => false,
                    'tension' => 0.4,
                ],
            ],
            'labels' => $formattedLabels,
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
                        'text' => 'Remaining Tickets',
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
