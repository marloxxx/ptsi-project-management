<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Domain\Services\TicketBoardServiceInterface;
use App\Domain\Services\TicketServiceInterface;
use App\Filament\Resources\Tickets\TicketResource;
use App\Models\Project;
use App\Models\Ticket;
use App\Models\TicketStatus;
use App\Models\User;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\CheckboxList;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Livewire\Attributes\On;
use Throwable;
use UnitEnum;

class ProjectBoard extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedViewColumns;

    protected static ?string $navigationLabel = 'Project Board';

    protected static ?string $title = 'Project Board';

    protected static ?string $slug = 'project-board/{project?}';

    protected string $view = 'filament.pages.project-board';

    protected static string|UnitEnum|null $navigationGroup = 'Project Management';

    protected static ?int $navigationSort = 4;

    /** @var Collection<int, Project> */
    public Collection $projects;

    /** @var Collection<int, TicketStatus> */
    public Collection $ticketStatuses;

    /** @var Collection<int, User> */
    public Collection $projectUsers;

    public ?Project $selectedProject = null;

    public ?int $selectedProjectId = null;

    /**
     * @var array<int, string>
     */
    public array $sortOrders = [];

    /**
     * @var array<int, int>
     */
    public array $selectedUserIds = [];

    public string $searchProject = '';

    private TicketBoardServiceInterface $boardService;

    private TicketServiceInterface $ticketService;

    public static function canAccess(): bool
    {
        return self::currentUser()?->can('tickets.view') ?? false;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    public function boot(
        TicketBoardServiceInterface $boardService,
        TicketServiceInterface $ticketService
    ): void {
        $this->boardService = $boardService;
        $this->ticketService = $ticketService;
    }

    public function mount(?int $project = null): void
    {
        $this->projects = collect();
        $this->ticketStatuses = collect();
        $this->projectUsers = collect();

        $userId = Auth::id();

        if (! $userId) {
            abort(403);
        }

        $this->projects = $this->boardService
            ->listAccessibleProjects((int) $userId)
            ->values();

        if ($project !== null) {
            $this->selectProject((int) $project);
        }
    }

    public function getSubheading(): ?string
    {
        return 'Manage tickets via Kanban board view';
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
                fn(Project $project): bool => Str::contains(Str::lower($project->name), $needle)
                    || Str::contains(Str::lower((string) $project->ticket_prefix), $needle)
            )
            ->values();
    }

    public function updatedSelectedProjectId(mixed $value): void
    {
        if ($value) {
            $this->selectProject((int) $value);
        } else {
            $this->resetSelection();
        }
    }

    public function selectProject(int $projectId): void
    {
        $userId = Auth::id();

        if (! $userId) {
            abort(403);
        }

        $isSameProject = $this->selectedProjectId === $projectId;
        $assigneeFilter = $isSameProject ? $this->selectedUserIds : [];

        if (! $isSameProject) {
            $this->selectedUserIds = [];
            $this->sortOrders = [];
        }

        try {
            $context = $this->boardService->getBoardContext($projectId, (int) $userId, [
                'assignee_ids' => $assigneeFilter,
            ]);
        } catch (Throwable $exception) {
            Notification::make()
                ->title('Unable to load project board')
                ->body($exception->getMessage())
                ->danger()
                ->send();

            return;
        }

        $this->selectedProjectId = $projectId;
        $this->selectedProject = $context['project'];
        $this->ticketStatuses = $context['statuses']->values();
        $this->projectUsers = $context['members']->values();

        $this->applySortOrders();
    }

    public function updatedSelectedUserIds(): void
    {
        $this->loadTicketStatuses();
    }

    public function clearUserFilter(): void
    {
        $this->selectedUserIds = [];
        $this->loadTicketStatuses();
    }

    public function setSortOrder(int $statusId, string $sortOrder): void
    {
        $this->sortOrders[$statusId] = $sortOrder;
        $this->applySortOrders();
    }

    #[On('ticket-moved')]
    public function moveTicket(int $ticketId, int $newStatusId): void
    {
        if (! $this->canMoveTickets()) {
            Notification::make()
                ->title('Permission denied')
                ->body('You do not have permission to move tickets.')
                ->danger()
                ->send();

            return;
        }

        try {
            $this->ticketService->changeStatus($ticketId, $newStatusId);
            $this->loadTicketStatuses();

            $this->dispatch('ticket-updated');

            Notification::make()
                ->title('Ticket updated')
                ->success()
                ->send();
        } catch (Throwable $exception) {
            Notification::make()
                ->title('Unable to move ticket')
                ->body($exception->getMessage())
                ->danger()
                ->send();
        }
    }

    #[On('refresh-board')]
    public function refreshBoard(): void
    {
        $this->loadTicketStatuses();
        $this->dispatch('ticket-updated');
    }

    public function showTicketDetails(int $ticketId): void
    {
        $url = TicketResource::getUrl('view', ['record' => $ticketId]);

        $this->js("window.open('{$url}', '_blank')");
    }

    public function editTicket(int $ticketId): void
    {
        if (! $this->canMoveTickets()) {
            Notification::make()
                ->title('Permission denied')
                ->body('You do not have permission to edit tickets.')
                ->danger()
                ->send();

            return;
        }

        $this->redirect(TicketResource::getUrl('edit', ['record' => $ticketId]));
    }

    protected function loadTicketStatuses(): void
    {
        if ($this->selectedProjectId === null) {
            $this->ticketStatuses = collect();
            $this->projectUsers = collect();

            return;
        }

        $this->selectProject($this->selectedProjectId);
    }

    protected function resetSelection(): void
    {
        $this->selectedProject = null;
        $this->selectedProjectId = null;
        $this->ticketStatuses = collect();
        $this->projectUsers = collect();
        $this->selectedUserIds = [];
    }

    protected function applySortOrders(): void
    {
        $this->ticketStatuses = $this->ticketStatuses->map(function (TicketStatus $status) {
            $sortOrder = $this->sortOrders[$status->getKey()] ?? 'date_created_newest';

            $status->setRelation(
                'tickets',
                $this->applySorting($status->tickets, $sortOrder)->values()
            );

            return $status;
        })->values();
    }

    /**
     * @param  Collection<int, Ticket>  $tickets
     * @return Collection<int, Ticket>
     */
    protected function applySorting(Collection $tickets, string $sortOrder): Collection
    {
        return match ($sortOrder) {
            'date_created_oldest' => $tickets->sortBy('created_at'),
            'card_name_alphabetical' => $tickets->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE),
            'due_date' => $tickets->sortBy(fn(Ticket $ticket) => $ticket->due_date?->format('Y-m-d') ?? '9999-12-31'),
            'priority' => $tickets->sortBy(fn(Ticket $ticket) => $ticket->priority?->getKey() ?? PHP_INT_MAX),
            default => $tickets->sortByDesc('created_at'),
        };
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('new_ticket')
                ->label('New Ticket')
                ->icon('heroicon-m-plus')
                ->visible(fn(): bool => $this->selectedProject !== null && (self::currentUser()?->can('tickets.create') ?? false))
                ->url(fn(): string => TicketResource::getUrl('create', [
                    'project_id' => $this->selectedProject?->getKey(),
                    'ticket_status_id' => $this->ticketStatuses->first()?->getKey(),
                ]))
                ->openUrlInNewTab(),
            Action::make('refresh_board')
                ->label('Refresh Board')
                ->icon('heroicon-m-arrow-path')
                ->color('warning')
                ->action('refreshBoard'),
            Action::make('filter_users')
                ->label('Filter by Member')
                ->icon('heroicon-m-user-group')
                ->visible(fn(): bool => $this->selectedProject !== null && $this->projectUsers->isNotEmpty())
                ->schema([
                    CheckboxList::make('selectedUserIds')
                        ->label('Members')
                        ->options(fn(): array => $this->projectUsers->pluck('name', 'id')->toArray())
                        ->columns(2)
                        ->bulkToggleable()
                        ->searchable(),
                ])
                ->fillForm([
                    'selectedUserIds' => $this->selectedUserIds,
                ])
                ->modalWidth('md')
                ->color('info')
                ->action(function (array $data): void {
                    /** @var array<int, int>|null $ids */
                    $ids = $data['selectedUserIds'] ?? [];
                    $this->selectedUserIds = array_values(
                        array_filter(
                            array_map(static fn($value): int => (int) $value, $ids ?? []),
                            static fn(int $id): bool => $id > 0
                        )
                    );

                    $this->loadTicketStatuses();

                    if ($this->selectedUserIds !== []) {
                        Notification::make()
                            ->title('Filter applied')
                            ->body('Showing tickets for selected members.')
                            ->success()
                            ->send();
                    } else {
                        Notification::make()
                            ->title('Filter cleared')
                            ->body('Showing all tickets.')
                            ->info()
                            ->send();
                    }
                }),
        ];
    }

    public function canMoveTickets(): bool
    {
        return self::currentUser()?->can('tickets.update') ?? false;
    }

    private static function currentUser(): ?User
    {
        $user = Auth::user();

        return $user instanceof User ? $user : null;
    }
}
