<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Domain\Services\TicketBoardServiceInterface;
use App\Models\User;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;
use RuntimeException;
use UnitEnum;

class ProjectTimeline extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static ?string $navigationLabel = 'Project Timeline';

    protected static string|UnitEnum|null $navigationGroup = 'Project Management';

    protected static ?int $navigationSort = 5;

    protected static ?string $title = 'Project Timeline';

    protected string $view = 'filament.pages.project-timeline';

    /**
     * @var array<string, int>
     */
    public array $counts = [
        'all' => 0,
        'overdue' => 0,
        'approaching_deadline' => 0,
        'nearly_complete' => 0,
    ];

    /**
     * @var array<int, array<string, mixed>>
     */
    public array $tasks = [];

    private TicketBoardServiceInterface $boardService;

    public static function canAccess(): bool
    {
        return self::currentUser()?->can('tickets.view') ?? false;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    public function boot(TicketBoardServiceInterface $boardService): void
    {
        $this->boardService = $boardService;
    }

    public function mount(): void
    {
        $this->loadData();
    }

    public function loadData(): void
    {
        $user = self::currentUser();

        if (! $user instanceof User) {
            throw new RuntimeException('Pengguna tidak ditemukan.');
        }

        $snapshot = $this->boardService->getTimelineSnapshot((int) $user->getKey());

        $this->counts = $snapshot['counts'] ?? $this->counts;
        $this->tasks = $snapshot['gantt']['data'] ?? [];

        usort($this->tasks, static function (array $left, array $right): int {
            return strcmp((string) $left['start_date'], (string) $right['start_date']);
        });
    }

    /**
     * @return array<string, string>
     */
    public function getStatusBadges(): array
    {
        return [
            'overdue' => 'bg-red-100 text-red-700 dark:bg-red-500/10 dark:text-red-300',
            'approaching_deadline' => 'bg-amber-100 text-amber-700 dark:bg-amber-500/10 dark:text-amber-300',
            'nearly_complete' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300',
            'in_progress' => 'bg-blue-100 text-blue-700 dark:bg-blue-500/10 dark:text-blue-300',
        ];
    }

    private static function currentUser(): ?User
    {
        $user = Auth::user();

        return $user instanceof User ? $user : null;
    }
}
