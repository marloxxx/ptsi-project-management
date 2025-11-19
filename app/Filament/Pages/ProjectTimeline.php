<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Domain\Services\TicketBoardServiceInterface;
use App\Models\Project;
use App\Models\User;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use RuntimeException;
use UnitEnum;

class ProjectTimeline extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static ?string $navigationLabel = 'Project Timeline';

    protected static string|UnitEnum|null $navigationGroup = 'Project Management';

    protected static ?int $navigationSort = 5;

    protected static ?string $title = 'Project Timeline';

    protected static ?string $slug = 'project-timeline/{project?}';

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
     * @var Collection<int, Project>
     */
    public Collection $projects;

    /**
     * @var array<int, array<string, mixed>>
     */
    public array $tasks = [];

    /**
     * @var array<string, array<int, array<string, mixed>>>
     */
    public array $tasksByDate = [];

    public ?int $selectedProjectId = null;

    public ?Project $selectedProject = null;

    public string $searchProject = '';

    public string $currentMonth = '';

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

    public function mount(?int $project = null): void
    {
        $user = self::currentUser();

        if (! $user instanceof User) {
            abort(403);
        }

        $userId = (int) $user->getKey();
        $this->projects = $this->boardService->listAccessibleProjects($userId)->values();

        // Initialize current month to today or first task's month
        $this->currentMonth = now()->format('Y-m');

        if ($project !== null) {
            $this->selectProject((int) $project);
        }
    }

    public function previousMonth(): void
    {
        $month = \Carbon\Carbon::parse($this->currentMonth.'-01')->subMonth();
        $this->currentMonth = $month->format('Y-m');
    }

    public function nextMonth(): void
    {
        $month = \Carbon\Carbon::parse($this->currentMonth.'-01')->addMonth();
        $this->currentMonth = $month->format('Y-m');
    }

    public function goToToday(): void
    {
        $this->currentMonth = now()->format('Y-m');
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getCalendarDaysProperty(): array
    {
        if (empty($this->tasksByDate)) {
            return [];
        }

        $month = \Carbon\Carbon::parse($this->currentMonth.'-01');
        $firstDay = $month->copy()->startOfMonth()->startOfWeek();
        $lastDay = $month->copy()->endOfMonth()->endOfWeek();

        $days = [];
        $currentDay = $firstDay->copy();

        while ($currentDay->lte($lastDay)) {
            $dateKey = $currentDay->format('Y-m-d');
            $tasksForDay = $this->tasksByDate[$dateKey] ?? [];

            $days[] = [
                'date' => $currentDay->copy(),
                'dateKey' => $dateKey,
                'isCurrentMonth' => $currentDay->isSameMonth($month),
                'isToday' => $currentDay->isToday(),
                'isPast' => $currentDay->isPast() && ! $currentDay->isToday(),
                'isFuture' => $currentDay->isFuture(),
                'tasks' => $tasksForDay,
                'hasTasks' => ! empty($tasksForDay),
            ];

            $currentDay->addDay();
        }

        return $days;
    }

    /**
     * @return array<int, string>
     */
    public function getWeekDaysProperty(): array
    {
        return [
            'Min',
            'Sen',
            'Sel',
            'Rab',
            'Kam',
            'Jum',
            'Sab',
        ];
    }

    public function selectProject(int $projectId): void
    {
        $this->selectedProjectId = $projectId;
        $this->selectedProject = $this->projects->firstWhere('id', $projectId);
        $this->loadData();
    }

    public function resetProjectSelection(): void
    {
        $this->selectedProjectId = null;
        $this->selectedProject = null;
        $this->tasks = [];
        $this->tasksByDate = [];
    }

    public function updatedSelectedProjectId(mixed $value): void
    {
        if ($value) {
            $this->selectProject((int) $value);
        } else {
            $this->resetProjectSelection();
        }
    }

    /**
     * @return Collection<int, Project>
     */
    public function getFilteredProjectsProperty(): Collection
    {
        if ($this->searchProject === '') {
            return $this->projects;
        }

        $needle = Str::lower($this->searchProject);

        return $this->projects
            ->filter(
                fn (Project $project): bool => Str::contains(Str::lower($project->name), $needle)
                    || Str::contains(Str::lower((string) $project->ticket_prefix), $needle)
            )
            ->values();
    }

    public function loadData(): void
    {
        if ($this->selectedProjectId === null) {
            $this->tasks = [];
            $this->tasksByDate = [];

            return;
        }

        $user = self::currentUser();

        if (! $user instanceof User) {
            throw new RuntimeException('Pengguna tidak ditemukan.');
        }

        $snapshot = $this->boardService->getTimelineSnapshot((int) $user->getKey());
        $allTasks = $snapshot['gantt']['data'] ?? [];

        // Filter by selected project
        $this->tasks = array_filter($allTasks, fn (array $task): bool => (int) $task['id'] === $this->selectedProjectId);
        $this->tasks = array_values($this->tasks); // Reindex array

        usort($this->tasks, static function (array $left, array $right): int {
            return strcmp((string) $left['start_date'], (string) $right['start_date']);
        });

        // Group tasks by date for calendar view
        $this->tasksByDate = [];
        foreach ($this->tasks as $task) {
            $startDate = \Carbon\Carbon::parse($task['start_date']);
            $dateKey = $startDate->format('Y-m-d');

            if (! isset($this->tasksByDate[$dateKey])) {
                $this->tasksByDate[$dateKey] = [];
            }

            $this->tasksByDate[$dateKey][] = $task;
        }

        // Sort dates
        ksort($this->tasksByDate);

        // Set current month to first task's month if not set
        if ($this->currentMonth === '' && ! empty($this->tasks)) {
            $firstTaskDate = \Carbon\Carbon::parse($this->tasks[0]['start_date']);
            $this->currentMonth = $firstTaskDate->format('Y-m');
        }
    }

    public function getCurrentMonthNameProperty(): string
    {
        if ($this->currentMonth === '') {
            return now()->translatedFormat('F Y');
        }

        $month = \Carbon\Carbon::parse($this->currentMonth.'-01');

        return $month->translatedFormat('F Y');
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
