<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Domain\Services\AnalyticsServiceInterface;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class StatsOverview extends BaseWidget
{
    protected ?string $pollingInterval = '30s';

    protected static ?int $sort = 1;

    /** @phpstan-ignore-next-line */
    protected int|string|array $columnSpan = [
        'default' => 1,
        'md' => 'full',
    ];

    protected AnalyticsServiceInterface $analyticsService;

    public function boot(AnalyticsServiceInterface $analyticsService): void
    {
        $this->analyticsService = $analyticsService;
    }

    protected function getStats(): array
    {
        $user = Auth::user();

        if (! $user) {
            return [];
        }

        return collect($this->analyticsService->getOverviewStats($user))
            ->map(function (array $stat): Stat {
                $statObject = Stat::make($stat['label'], $stat['value']);

                if (isset($stat['description'])) {
                    $statObject = $statObject->description($stat['description']);
                }

                if (isset($stat['icon'])) {
                    $statObject = $statObject->descriptionIcon($stat['icon']);
                }

                if (isset($stat['color'])) {
                    $statObject = $statObject->color($stat['color']);
                }

                return $statObject;
            })
            ->all();
    }
}
