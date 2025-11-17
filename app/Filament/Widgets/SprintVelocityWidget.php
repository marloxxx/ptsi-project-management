<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Domain\Services\SprintServiceInterface;
use App\Models\Project;
use App\Models\Sprint;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class SprintVelocityWidget extends StatsOverviewWidget
{
    protected ?string $pollingInterval = '30s';

    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = [
        'default' => 1,
        'md' => 2,
    ];

    protected SprintServiceInterface $sprintService;

    public ?int $sprintId = null;

    public function boot(SprintServiceInterface $sprintService): void
    {
        $this->sprintService = $sprintService;
    }

    protected function getStats(): array
    {
        $sprint = $this->getSprint();

        if (! $sprint) {
            return [
                Stat::make('Current Sprint', 'No Active Sprint')
                    ->description('No active sprint found for this project')
                    ->color('gray')
                    ->icon('heroicon-o-calendar-days'),
            ];
        }

        $velocity = $this->sprintService->computeVelocity($sprint);
        $totalTickets = $sprint->tickets()->count();
        $completedTickets = $sprint->tickets()
            ->whereHas('status', function ($query): void {
                $query->where('is_completed', true);
            })
            ->count();

        $completionPercentage = $totalTickets > 0
            ? round(($completedTickets / $totalTickets) * 100, 1)
            : 0;

        return [
            Stat::make('Sprint Velocity', (string) $velocity)
                ->description('Completed tickets in this sprint')
                ->color('success')
                ->icon('heroicon-o-chart-bar'),
            Stat::make('Total Tickets', (string) $totalTickets)
                ->description('Total tickets in sprint')
                ->color('info')
                ->icon('heroicon-o-ticket'),
            Stat::make('Completion', sprintf('%s%%', $completionPercentage))
                ->description(sprintf('%d of %d tickets completed', $completedTickets, $totalTickets))
                ->color($completionPercentage >= 100 ? 'success' : ($completionPercentage >= 75 ? 'warning' : 'danger'))
                ->icon('heroicon-o-check-circle'),
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
}
