<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Domain\Services\EpicOverviewServiceInterface;
use App\Models\Epic;
use App\Models\Project;
use App\Models\User;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Livewire\Attributes\On;
use UnitEnum;

class EpicsOverview extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedFlag;

    protected static ?string $navigationLabel = 'Epics';

    protected static string|UnitEnum|null $navigationGroup = 'Project Management';

    protected static ?int $navigationSort = 6;

    protected static ?string $title = 'Epics Overview';

    protected static ?string $slug = 'epics/{project?}';

    protected string $view = 'filament.pages.epics-overview';

    /**
     * @var Collection<int, Project>
     */
    public Collection $projects;

    /**
     * @var Collection<int, Epic>
     */
    public Collection $epics;

    public ?int $selectedProjectId = null;

    public string $searchProject = '';

    public string $epicSearch = '';

    /**
     * @var array<int, int>
     */
    public array $expandedEpicIds = [];

    private EpicOverviewServiceInterface $epicOverviewService;

    public static function canAccess(): bool
    {
        return self::currentUser()?->can('epics.view') ?? false;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    public function boot(EpicOverviewServiceInterface $epicOverviewService): void
    {
        $this->epicOverviewService = $epicOverviewService;
    }

    public function mount(?int $project = null): void
    {
        $this->projects = collect();
        $this->epics = collect();

        $this->loadProjects();

        if ($project !== null) {
            $this->selectedProjectId = $project;
        }

        $this->loadEpics();
    }

    public function selectProject(int $projectId): void
    {
        $this->selectedProjectId = $projectId;
        $this->loadEpics();
    }

    public function resetProjectSelection(): void
    {
        $this->selectedProjectId = null;
        $this->loadEpics();
    }

    public function updatedEpicSearch(): void
    {
        $this->loadEpics();
    }

    public function toggleEpic(int $epicId): void
    {
        if (in_array($epicId, $this->expandedEpicIds, true)) {
            $this->expandedEpicIds = array_values(array_diff($this->expandedEpicIds, [$epicId]));

            return;
        }

        $this->expandedEpicIds[] = $epicId;
    }

    #[On('epic-created')]
    #[On('epic-updated')]
    #[On('epic-deleted')]
    #[On('ticket-created')]
    #[On('ticket-updated')]
    #[On('ticket-deleted')]
    public function refreshEpics(): void
    {
        $this->loadEpics();
    }

    public function isExpanded(int $epicId): bool
    {
        return in_array($epicId, $this->expandedEpicIds, true);
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
            ->filter(static function (Project $project) use ($needle): bool {
                $nameMatch = Str::contains(Str::lower($project->name), $needle);
                $prefixMatch = Str::contains(Str::lower((string) $project->ticket_prefix), $needle);

                return $nameMatch || $prefixMatch;
            })
            ->values();
    }

    public function getSelectedProject(): ?Project
    {
        if ($this->selectedProjectId === null) {
            return null;
        }

        /** @var Project|null $project */
        $project = $this->projects->firstWhere('id', $this->selectedProjectId);

        return $project;
    }

    private function loadProjects(): void
    {
        $user = self::currentUser();

        if (! $user instanceof User) {
            abort(403);
        }

        $this->projects = $this->epicOverviewService->listProjects((int) $user->getKey());
    }

    private function loadEpics(): void
    {
        $user = self::currentUser();

        if (! $user instanceof User) {
            abort(403);
        }

        if ($this->selectedProjectId === null) {
            $this->epics = collect();
            $this->expandedEpicIds = [];

            return;
        }

        try {
            $this->epics = $this->epicOverviewService->listEpics(
                (int) $user->getKey(),
                $this->selectedProjectId,
                [
                    'search' => $this->epicSearch !== '' ? $this->epicSearch : null,
                ]
            );

            $this->expandedEpicIds = $this->epics->pluck('id')->values()->all();
        } catch (AuthorizationException $exception) {
            $this->resetProjectSelection();

            Notification::make()
                ->title('Tidak dapat memuat epics')
                ->body($exception->getMessage())
                ->danger()
                ->send();
        }
    }

    private static function currentUser(): ?User
    {
        $user = Auth::user();

        return $user instanceof User ? $user : null;
    }
}
