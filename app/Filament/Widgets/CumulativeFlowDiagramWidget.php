<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Domain\Services\AnalyticsServiceInterface;
use App\Models\Project;
use Filament\Widgets\ChartWidget;

class CumulativeFlowDiagramWidget extends ChartWidget
{
    protected ?string $heading = 'Cumulative Flow Diagram';

    protected ?string $description = 'Track work in progress across statuses over time';

    protected static ?int $sort = 2;

    protected ?string $maxHeight = '400px';

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

        $data = $this->analyticsService->getCumulativeFlowDiagram($projectId, $this->days ?? 30);

        if (empty($data['datasets'])) {
            return [
                'datasets' => [],
                'labels' => [],
            ];
        }

        // Format labels to be more readable
        $labels = array_map(function (string $date): string {
            return \Carbon\Carbon::parse($date)->format('M d');
        }, $data['labels']);

        return [
            'datasets' => $data['datasets'],
            'labels' => $labels,
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
                    'stacked' => true,
                    'title' => [
                        'display' => true,
                        'text' => 'Number of Tickets',
                    ],
                ],
                'x' => [
                    'stacked' => true,
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
