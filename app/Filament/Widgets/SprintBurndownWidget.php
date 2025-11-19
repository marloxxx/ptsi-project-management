<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Domain\Services\SprintServiceInterface;
use App\Models\Project;
use App\Models\Sprint;
use Filament\Widgets\ChartWidget;

class SprintBurndownWidget extends ChartWidget
{
    protected ?string $heading = 'Sprint Burndown';

    protected ?string $description = 'Track remaining work over time';

    protected static ?int $sort = 1;

    protected ?string $maxHeight = '300px';

    protected ?string $pollingInterval = '30s';

    protected int|string|array $columnSpan = [
        'default' => 1,
        'md' => 2,
        'xl' => 2,
    ];

    protected SprintServiceInterface $sprintService;

    public ?int $sprintId = null;

    public function boot(SprintServiceInterface $sprintService): void
    {
        $this->sprintService = $sprintService;
    }

    protected function getData(): array
    {
        $sprint = $this->getSprint();

        if (! $sprint) {
            return [
                'datasets' => [],
                'labels' => [],
            ];
        }

        $burndownData = $this->sprintService->computeBurndown($sprint);

        if (empty($burndownData)) {
            return [
                'datasets' => [],
                'labels' => [],
            ];
        }

        $labels = array_column($burndownData, 'date');
        $remaining = array_column($burndownData, 'remaining');
        $ideal = array_column($burndownData, 'ideal');

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

    protected function getSprint(): ?Sprint
    {
        if ($this->sprintId !== null) {
            return Sprint::find($this->sprintId);
        }

        // Get project from route parameter
        $recordId = $this->getRecordId();

        if ($recordId !== null) {
            $project = Project::find($recordId);

            if ($project instanceof Project) {
                return $project->sprints()
                    ->where('state', 'Active')
                    ->first();
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

    public function getHeading(): ?string
    {
        $sprint = $this->getSprint();

        if (! $sprint) {
            return 'Sprint Burndown (No Active Sprint)';
        }

        return sprintf('Sprint Burndown: %s', $sprint->name);
    }
}
