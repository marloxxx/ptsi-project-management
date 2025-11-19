<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Domain\Services\AnalyticsServiceInterface;
use App\Models\Project;
use Filament\Widgets\ChartWidget;

class LeadCycleTimeWidget extends ChartWidget
{
    protected ?string $heading = 'Lead Time & Cycle Time';

    protected ?string $description = 'Average time from creation to completion and from in-progress to completion';

    protected static ?int $sort = 3;

    protected ?string $maxHeight = '350px';

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

        $data = $this->analyticsService->getLeadCycleTime($projectId, $this->days ?? 30);

        if (empty($data['chart_data']['labels'])) {
            return [
                'datasets' => [],
                'labels' => [],
            ];
        }

        return [
            'datasets' => [
                [
                    'label' => 'Lead Time (days)',
                    'data' => $data['chart_data']['lead_times'],
                    'borderColor' => '#3B82F6',
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                    'borderWidth' => 2,
                    'fill' => true,
                    'tension' => 0.4,
                ],
                [
                    'label' => 'Cycle Time (days)',
                    'data' => $data['chart_data']['cycle_times'],
                    'borderColor' => '#10B981',
                    'backgroundColor' => 'rgba(16, 185, 129, 0.1)',
                    'borderWidth' => 2,
                    'fill' => true,
                    'tension' => 0.4,
                ],
            ],
            'labels' => $data['chart_data']['labels'],
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
                        'text' => 'Days',
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
            return 'Lead Time & Cycle Time';
        }

        $data = $this->analyticsService->getLeadCycleTime($projectId, $this->days ?? 30);

        $avgLeadTime = $data['lead_time']['avg'] ?? 0;
        $avgCycleTime = $data['cycle_time']['avg'] ?? 0;

        return sprintf(
            'Lead Time & Cycle Time (Avg: %.1f / %.1f days)',
            $avgLeadTime,
            $avgCycleTime
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
