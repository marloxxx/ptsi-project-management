<?php

namespace App\Livewire\External;

use App\Domain\Services\ExternalPortalServiceInterface;
use App\Models\Project;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\LengthAwarePaginator as Paginator;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.external')]
class Dashboard extends Component
{
    use WithPagination;

    public string $token;

    public ?Project $project = null;

    public array $summary = [
        'total' => 0,
        'completed' => 0,
        'progress' => 0,
        'overdue' => 0,
        'new_this_week' => 0,
        'completed_this_week' => 0,
    ];

    /**
     * @var array<int, array{id: int, name: string, color: ?string, count: int}>
     */
    public array $statusOverview = [];

    /**
     * @var array<int, array{id: int, name: string}>
     */
    public array $priorities = [];

    public ?int $selectedStatus = null;

    public ?int $selectedPriority = null;

    public string $search = '';

    public string $activeTab = 'overview';

    protected string $paginationTheme = 'bootstrap';

    private ExternalPortalServiceInterface $portalService;

    public function boot(ExternalPortalServiceInterface $portalService): void
    {
        $this->portalService = $portalService;
    }

    public function mount(string $token): void
    {
        $this->token = $token;

        if (! Session::get($this->authenticatedCacheKey())) {
            $this->redirectRoute('external.login', ['token' => $this->token]);
        }

        $context = $this->portalService->resolveContext($this->token);

        $this->project = $context['project']->loadMissing(['ticketStatuses']);

        $this->priorities = $this->portalService->ticketPriorities()->all();
        $this->statusOverview = $this->portalService->ticketsByStatus($this->project)->all();
        $this->summary = $this->portalService->projectSummary($this->project);
    }

    public function updatedSelectedStatus(): void
    {
        $this->resetTicketsPage();
    }

    public function updatedSelectedPriority(): void
    {
        $this->resetTicketsPage();
    }

    public function updatedSearch(): void
    {
        $this->resetTicketsPage();
    }

    public function clearFilters(): void
    {
        $this->selectedStatus = null;
        $this->selectedPriority = null;
        $this->search = '';

        $this->resetTicketsPage();
    }

    public function refreshData(): void
    {
        if (! $this->project instanceof Project) {
            return;
        }

        $this->summary = $this->portalService->projectSummary($this->project);
        $this->statusOverview = $this->portalService->ticketsByStatus($this->project)->all();

        $this->dispatch('external-dashboard-refreshed');
    }

    public function getTicketsProperty(): LengthAwarePaginator
    {
        if (! $this->project instanceof Project) {
            return $this->emptyPaginator('tickets');
        }

        return $this->portalService->paginatedTickets($this->project, [
            'status_id' => $this->selectedStatus,
            'priority_id' => $this->selectedPriority,
            'search' => $this->search,
            'page_name' => 'tickets',
        ]);
    }

    public function getActivitiesProperty(): LengthAwarePaginator
    {
        if (! $this->project instanceof Project) {
            return $this->emptyPaginator('activities');
        }

        return $this->portalService->recentActivities($this->project, [
            'page_name' => 'activities',
        ]);
    }

    public function render()
    {
        return view('livewire.external.dashboard');
    }

    private function resetTicketsPage(): void
    {
        $this->resetPage('tickets');
    }

    private function authenticatedCacheKey(): string
    {
        return sprintf('external_portal_authenticated_%s', $this->token);
    }

    private function emptyPaginator(string $pageName): LengthAwarePaginator
    {
        return new Paginator([], 0, 10, 1, [
            'path' => request()->url(),
            'pageName' => $pageName,
        ]);
    }
}
