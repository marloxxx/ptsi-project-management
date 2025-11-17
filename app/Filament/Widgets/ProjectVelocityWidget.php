<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Domain\Services\AnalyticsServiceInterface;
use App\Models\Project;
use Filament\Widgets\ChartWidget;

class ProjectVelocityWidget extends ChartWidget
{
    protected ?string $heading = 'Project Velocity';

    protected ?string $description = 'Completed tickets per week';

    protected static ?int $sort = 6;

    protected ?string $maxHeight = '300px';

    protected ?string $pollingInterval = '60s';

    protected int|string|array $columnSpan = [
        'default' => 1,
        'md' => 2,
        'xl' => 1,
    ];

    protected AnalyticsServiceInterface $analyticsService;

    public ?int $projectId = null;

    public ?int $periods = 8;

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

        $data = $this->analyticsService->getProjectVelocity($projectId, $this->periods ?? 8);

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
                    'backgroundColor' => '#3B82F6',
                    'borderColor' => '#2563EB',
                    'borderWidth' => 2,
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
                        'text' => 'Week',
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
            return 'Project Velocity';
        }

        $data = $this->analyticsService->getProjectVelocity($projectId, $this->periods ?? 8);

        $avgVelocity = $data['avg_velocity'] ?? 0;

        return sprintf(
            'Project Velocity (Avg: %.1f tickets/week)',
            $avgVelocity
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
