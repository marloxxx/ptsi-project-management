@php
    /** @var \Illuminate\Contracts\Pagination\LengthAwarePaginator $tickets */
    $tickets = $this->tickets;
    /** @var \Illuminate\Contracts\Pagination\LengthAwarePaginator $activities */
    $activities = $this->activities;
@endphp

<div>
    <section class="mb-5">
        <header class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h1 class="h4 mb-1">{{ $project?->name ?? __('Project Dashboard') }}</h1>
                <p class="text-secondary mb-0">
                    {{ __('Keep partners informed with progress updates, ticket activity, and milestones.') }}
                </p>
            </div>
            <button type="button" class="btn btn-outline-primary" wire:click="refreshData" wire:loading.attr="disabled">
                <span wire:loading.remove>{{ __('Refresh Data') }}</span>
                <span wire:loading>{{ __('Refreshing...') }}</span>
            </button>
        </header>

        <div class="grid gap-3" style="--pico-grid-columns: repeat(auto-fit, minmax(180px, 1fr));">
            <article class="card p-4 border-0 shadow-sm">
                <p class="text-secondary text-uppercase small mb-1">{{ __('Total Tickets') }}</p>
                <h2 class="h3 mb-0">{{ $summary['total'] }}</h2>
            </article>
            <article class="card p-4 border-0 shadow-sm">
                <p class="text-secondary text-uppercase small mb-1">{{ __('Completed') }}</p>
                <h2 class="h3 mb-1">{{ $summary['completed'] }}</h2>
                <span class="badge bg-success-subtle text-success">
                    {{ __('Progress: :percent%', ['percent' => number_format($summary['progress'] * 100, 0)]) }}
                </span>
            </article>
            <article class="card p-4 border-0 shadow-sm">
                <p class="text-secondary text-uppercase small mb-1">{{ __('Overdue') }}</p>
                <h2 class="h3 mb-1 text-danger">{{ $summary['overdue'] }}</h2>
                <span class="text-secondary small">{{ __('Tasks past their due date') }}</span>
            </article>
            <article class="card p-4 border-0 shadow-sm">
                <p class="text-secondary text-uppercase small mb-1">{{ __('Momentum') }}</p>
                <div class="d-flex flex-column gap-1">
                    <span
                        class="fw-medium">{{ __('New this week: :count', ['count' => $summary['new_this_week']]) }}</span>
                    <span
                        class="fw-medium text-success">{{ __('Completed this week: :count', ['count' => $summary['completed_this_week']]) }}</span>
                </div>
            </article>
        </div>
    </section>

    <section class="mb-5">
        <div class="card p-4 border-0 shadow-sm">
            <h2 class="h5 mb-3">{{ __('Ticket Snapshot') }}</h2>

            <div class="grid gap-3 mb-4" style="--pico-grid-columns: repeat(auto-fit, minmax(240px, 1fr));">
                <div>
                    <label for="status-filter" class="form-label small text-uppercase fw-semibold text-secondary">
                        {{ __('Filter by Status') }}
                    </label>
                    <select id="status-filter" class="form-select" wire:model.live="selectedStatus">
                        <option value="">{{ __('All statuses') }}</option>
                        @foreach ($statusOverview as $status)
                            <option value="{{ $status['id'] }}">{{ $status['name'] }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="priority-filter" class="form-label small text-uppercase fw-semibold text-secondary">
                        {{ __('Filter by Priority') }}
                    </label>
                    <select id="priority-filter" class="form-select" wire:model.live="selectedPriority">
                        <option value="">{{ __('All priorities') }}</option>
                        @foreach ($priorities as $priority)
                            <option value="{{ $priority['id'] }}">{{ $priority['name'] }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="search-filter" class="form-label small text-uppercase fw-semibold text-secondary">
                        {{ __('Search') }}
                    </label>
                    <input id="search-filter" type="search" class="form-control"
                        placeholder="{{ __('Search tickets...') }}" wire:model.live="search" />
                </div>
                <div class="d-flex align-items-end">
                    <button type="button" class="btn btn-outline-secondary w-100" wire:click="clearFilters">
                        {{ __('Clear Filters') }}
                    </button>
                </div>
            </div>

            <div class="mb-4">
                <h3 class="small text-uppercase text-secondary fw-semibold mb-2">{{ __('By Status') }}</h3>
                <div class="grid gap-2" style="--pico-grid-columns: repeat(auto-fit, minmax(160px, 1fr));">
                    @foreach ($statusOverview as $status)
                        <div class="border rounded-3 p-3 d-flex flex-column gap-1"
                            wire:key="status-{{ $status['id'] }}">
                            <span class="fw-semibold">{{ $status['name'] }}</span>
                            <span
                                class="text-secondary small">{{ __('Tickets: :count', ['count' => $status['count']]) }}</span>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th scope="col">{{ __('Ticket') }}</th>
                            <th scope="col">{{ __('Status') }}</th>
                            <th scope="col">{{ __('Priority') }}</th>
                            <th scope="col">{{ __('Assignees') }}</th>
                            <th scope="col">{{ __('Due Date') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($tickets as $ticket)
                            <tr wire:key="ticket-{{ $ticket->getKey() }}">
                                <td>
                                    <span class="fw-semibold">{{ $ticket->uuid }}</span>
                                    <p class="mb-0 text-secondary small">{{ $ticket->name }}</p>
                                </td>
                                <td>
                                    <span class="badge bg-secondary-subtle text-secondary">
                                        {{ $ticket->status?->name ?? __('Unknown') }}
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-primary-subtle text-primary">
                                        {{ $ticket->priority?->name ?? __('N/A') }}
                                    </span>
                                </td>
                                <td>
                                    <div class="d-flex flex-column gap-1">
                                        @forelse ($ticket->assignees as $assignee)
                                            <span class="small text-secondary"
                                                wire:key="assignee-{{ $ticket->getKey() }}-{{ $assignee->getKey() }}">
                                                {{ $assignee->name }}
                                            </span>
                                        @empty
                                            <span class="text-secondary small">{{ __('Unassigned') }}</span>
                                        @endforelse
                                    </div>
                                </td>
                                <td>
                                    <span class="small">
                                        {{ optional($ticket->due_date)->format('d M Y') ?? __('—') }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-secondary py-4">
                                    {{ __('No tickets match the selected filters.') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                {{ $tickets->links() }}
            </div>
        </div>
    </section>

    <section class="mb-5">
        <div class="card p-4 border-0 shadow-sm">
            <h2 class="h5 mb-3">{{ __('Recent Activity') }}</h2>

            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th scope="col">{{ __('Ticket') }}</th>
                            <th scope="col">{{ __('Status') }}</th>
                            <th scope="col">{{ __('Date') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($activities as $history)
                            <tr wire:key="activity-{{ $history->getKey() }}">
                                <td>
                                    <span class="fw-semibold">{{ $history->ticket?->uuid ?? __('N/A') }}</span>
                                    <p class="mb-0 small text-secondary">
                                        {{ $history->ticket?->name ?? __('Unknown ticket') }}</p>
                                </td>
                                <td>
                                    <span class="badge bg-info-subtle text-info">
                                        {{ $history->toStatus?->name ?? __('Status updated') }}
                                    </span>
                                </td>
                                <td>
                                    <span class="small text-secondary">
                                        {{ $history->created_at?->format('d M Y H:i') ?? '—' }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="text-center text-secondary py-4">
                                    {{ __('No recent activity found for this project.') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                {{ $activities->links() }}
            </div>
        </div>
    </section>
</div>
