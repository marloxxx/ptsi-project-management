<x-filament-panels::page>
    @if (!$selectedProject)
        <div class="space-y-6">
            <x-filament::section>
                <x-slot name="heading">
                    Pilih Proyek
                </x-slot>

                <div class="mb-4">
                    <div class="relative">
                        <x-heroicon-m-magnifying-glass
                            class="w-5 h-5 absolute inset-y-0 left-0 my-auto ml-3 text-gray-400" />
                        <input type="search" wire:model.live.debounce.300ms="searchProject"
                            class="block w-full pl-10 pr-3 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-sm text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                            placeholder="Cari proyek berdasarkan nama atau prefix tiket..." />
                        @if ($searchProject !== '')
                            <button type="button" wire:click="$set('searchProject', '')"
                                class="absolute inset-y-0 right-0 my-auto mr-3 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                                <x-heroicon-m-x-mark class="w-5 h-5" />
                            </button>
                        @endif
                    </div>
                </div>

                @if ($projects->isEmpty())
                    <div class="flex flex-col items-center justify-center py-12 text-gray-500 dark:text-gray-400">
                        <x-heroicon-o-rectangle-group class="w-12 h-12 mb-4" />
                        <p class="text-sm font-medium">Belum ada proyek yang bisa diakses.</p>
                        <p class="text-xs">Hubungi administrator untuk mendapatkan akses ke proyek.</p>
                    </div>
                @elseif ($this->filteredProjects->isEmpty())
                    <div class="flex flex-col items-center justify-center py-12 text-gray-500 dark:text-gray-400">
                        <x-heroicon-o-magnifying-glass-circle class="w-12 h-12 mb-4" />
                        <p class="text-sm font-medium">Tidak ditemukan proyek dengan kata kunci tersebut.</p>
                        <p class="text-xs">Coba gunakan kata kunci lain.</p>
                    </div>
                @else
                    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                        @foreach ($this->filteredProjects as $project)
                            <button type="button" wire:click="selectProject({{ $project->id }})"
                                class="relative flex flex-col items-start gap-3 rounded-xl border border-gray-200 bg-white p-4 text-left shadow-sm transition hover:shadow-md dark:border-gray-700 dark:bg-gray-800"
                                style="border-left-width: 4px; border-left-color: {{ $project->color ?? '#6B7280' }};">
                                @if ($project->is_pinned)
                                    <span
                                        class="absolute right-3 top-3 inline-flex items-center gap-1 rounded-full bg-amber-100 px-2 py-1 text-xs font-semibold text-amber-700 dark:bg-amber-500/10 dark:text-amber-300">
                                        <x-heroicon-m-bookmark class="h-3 w-3" />
                                        Pinned
                                    </span>
                                @endif

                                @if ($project->ticket_prefix)
                                    <span
                                        class="inline-flex items-center rounded-md px-2 py-0.5 text-xs font-semibold text-white shadow-sm"
                                        style="background-color: {{ $project->color ?? '#6B7280' }};">
                                        {{ $project->ticket_prefix }}
                                    </span>
                                @endif

                                <span class="text-base font-semibold leading-tight text-gray-900 dark:text-white">
                                    {{ $project->name }}
                                </span>

                                <div class="flex flex-wrap items-center gap-3 text-xs text-gray-500 dark:text-gray-400">
                                    @if ($project->start_date)
                                        <div class="inline-flex items-center gap-1">
                                            <x-heroicon-o-calendar class="h-4 w-4" />
                                            <span>{{ $project->start_date?->format('d M Y') ?? '-' }}</span>
                                        </div>
                                    @endif
                                    <div class="inline-flex items-center gap-1">
                                        <x-heroicon-o-users class="h-4 w-4" />
                                        <span>{{ $project->members?->count() ?? 0 }} anggota</span>
                                    </div>
                                </div>
                            </button>
                        @endforeach
                    </div>
                @endif
            </x-filament::section>
        </div>
    @else
        <div class="flex h-[calc(100vh-10rem)] flex-col space-y-4">
            <div class="flex-shrink-0">
                <x-filament::section>
                    <x-slot name="heading">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <span>{{ $selectedProject->name }}</span>
                                @if ($selectedProject->ticket_prefix)
                                    <span
                                        class="inline-flex items-center rounded-md px-2 py-0.5 text-xs font-semibold text-white shadow-sm"
                                        style="background-color: {{ $selectedProject->color ?? '#6B7280' }};">
                                        {{ $selectedProject->ticket_prefix }}
                                    </span>
                                @endif
                            </div>
                            <button type="button" wire:click="$set('selectedProjectId', null)"
                                class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 shadow-sm transition hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700">
                                <x-heroicon-m-arrow-left class="h-4 w-4" />
                                Kembali
                            </button>
                        </div>
                    </x-slot>
                    @if ($selectedProject->description)
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            {{ $selectedProject->description }}
                        </p>
                    @endif
                </x-filament::section>
            </div>

            <div
                class="flex-1 overflow-hidden rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800">
                <div class="relative h-full overflow-x-auto overflow-y-auto" x-data="projectBoard({{ $this->canMoveTickets() ? 'true' : 'false' }}, @js($this))" wire:ignore.self>
                    <div class="inline-flex min-w-full gap-4 p-4">
                        @forelse ($ticketStatuses as $status)
                            <div class="flex h-[calc(100vh-18rem)] w-[320px] flex-none flex-col rounded-xl border border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-900"
                                data-status-id="{{ $status->id }}" x-data="{ sortOrder: '{{ $sortOrders[$status->id] ?? 'date_created_newest' }}' }">
                                <div class="flex items-center justify-between rounded-t-xl px-4 py-3 text-white"
                                    style="background-color: {{ $status->color ?? '#2563EB' }};">
                                    <div class="flex items-center gap-2 font-medium">
                                        <span>{{ $status->name }}</span>
                                        <span class="text-xs opacity-80">{{ $status->tickets->count() }}</span>
                                    </div>

                                    <x-filament::dropdown>
                                        <x-slot name="trigger">
                                            <button type="button"
                                                class="rounded-md bg-black/20 p-1 text-white transition hover:bg-black/30">
                                                <x-heroicon-m-ellipsis-vertical class="h-4 w-4" />
                                            </button>
                                        </x-slot>

                                        <x-filament::dropdown.list class="min-w-52">
                                            <x-filament::dropdown.list.item tag="button"
                                                wire:click="setSortOrder({{ $status->id }}, 'date_created_newest')">
                                                Tanggal dibuat (terbaru)
                                            </x-filament::dropdown.list.item>
                                            <x-filament::dropdown.list.item tag="button"
                                                wire:click="setSortOrder({{ $status->id }}, 'date_created_oldest')">
                                                Tanggal dibuat (terlama)
                                            </x-filament::dropdown.list.item>
                                            <x-filament::dropdown.list.item tag="button"
                                                wire:click="setSortOrder({{ $status->id }}, 'card_name_alphabetical')">
                                                Judul (A-Z)
                                            </x-filament::dropdown.list.item>
                                            <x-filament::dropdown.list.item tag="button"
                                                wire:click="setSortOrder({{ $status->id }}, 'due_date')">
                                                Tanggal jatuh tempo
                                            </x-filament::dropdown.list.item>
                                            <x-filament::dropdown.list.item tag="button"
                                                wire:click="setSortOrder({{ $status->id }}, 'priority')">
                                                Prioritas
                                            </x-filament::dropdown.list.item>
                                        </x-filament::dropdown.list>
                                    </x-filament::dropdown>
                                </div>

                                <div class="status-column flex-1 space-y-3 overflow-y-auto px-3 py-3"
                                    style="scrollbar-width: thin; scrollbar-color: rgb(209 213 219) rgb(243 244 246);"
                                    data-status-id="{{ $status->id }}"
                                    x-on:dragover.prevent="handleDragOver($event, {{ $status->id }})"
                                    x-on:dragleave="handleDragLeave($event, {{ $status->id }})"
                                    x-on:drop.prevent="handleDrop($event, {{ $status->id }})">
                                    @forelse ($status->tickets as $ticket)
                                        <div class="ticket-card cursor-move rounded-lg border border-gray-200 bg-white p-3 shadow-sm transition hover:shadow dark:border-gray-700 dark:bg-gray-800"
                                            data-ticket-id="{{ $ticket->id }}" draggable="true"
                                            x-on:dragstart="handleDragStart($event, {{ $ticket->id }})"
                                            x-on:dragend="handleDragEnd($event)">
                                            <div
                                                class="mb-2 flex items-center justify-between text-xs text-gray-500 dark:text-gray-400">
                                                <span class="font-mono">{{ $ticket->uuid }}</span>
                                                <div class="flex items-center gap-1">
                                                    @if ($ticket->priority)
                                                        <span
                                                            class="rounded-md px-2 py-0.5 text-[11px] font-semibold text-white"
                                                            style="background-color: {{ $ticket->priority->color }};">
                                                            {{ $ticket->priority->name }}
                                                        </span>
                                                    @endif
                                                    @if ($ticket->due_date)
                                                        <span
                                                            class="rounded-md px-2 py-0.5 text-[11px] font-medium {{ $ticket->due_date->isPast() ? 'bg-red-100 text-red-700 dark:bg-red-500/10 dark:text-red-300' : 'bg-blue-100 text-blue-700 dark:bg-blue-500/10 dark:text-blue-300' }}">
                                                            {{ $ticket->due_date->format('d M') }}
                                                        </span>
                                                    @endif
                                                </div>
                                            </div>

                                            <p class="mb-2 text-sm font-semibold text-gray-900 dark:text-white">
                                                {{ $ticket->name }}
                                            </p>

                                            @if ($ticket->description)
                                                <p class="mb-3 line-clamp-2 text-xs text-gray-500 dark:text-gray-400">
                                                    {{ \Illuminate\Support\Str::limit(strip_tags($ticket->description), 120) }}
                                                </p>
                                            @endif

                                            <div class="flex items-center justify-between">
                                                <div class="flex flex-wrap gap-1">
                                                    @forelse ($ticket->assignees as $assignee)
                                                        <span
                                                            class="inline-flex items-center gap-1 rounded-full bg-primary-100 px-2 py-1 text-[11px] font-medium text-primary-700 dark:bg-primary-500/10 dark:text-primary-300">
                                                            <span
                                                                class="grid h-4 w-4 place-items-center rounded-full bg-primary-500 text-[10px] font-semibold text-white">
                                                                {{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($assignee->name, 0, 1)) }}
                                                            </span>
                                                            {{ \Illuminate\Support\Str::limit($assignee->name, 8) }}
                                                        </span>
                                                    @empty
                                                        <span
                                                            class="inline-flex items-center gap-1 rounded-full bg-gray-100 px-2 py-1 text-[11px] font-medium text-gray-500 dark:bg-gray-700 dark:text-gray-300">
                                                            <x-heroicon-m-user class="h-3.5 w-3.5" />
                                                            Tidak ada
                                                        </span>
                                                    @endforelse
                                                </div>

                                                <div class="flex items-center gap-2">
                                                    @if ($this->canMoveTickets())
                                                        <x-filament::icon-button icon="heroicon-m-pencil-square"
                                                            color="gray" label="Edit"
                                                            wire:click="editTicket({{ $ticket->id }})" />
                                                    @endif
                                                    <x-filament::icon-button icon="heroicon-m-eye" color="gray"
                                                        label="Detail"
                                                        wire:click="showTicketDetails({{ $ticket->id }})" />
                                                </div>
                                            </div>
                                        </div>
                                    @empty
                                        <div
                                            class="flex h-24 items-center justify-center rounded-lg border border-dashed border-gray-300 text-sm text-gray-500 dark:border-gray-700 dark:text-gray-400">
                                            Belum ada ticket
                                        </div>
                                    @endforelse
                                </div>
                            </div>
                        @empty
                            <div
                                class="flex h-40 w-full items-center justify-center text-sm text-gray-500 dark:text-gray-400">
                                Tidak ada kolom status yang dapat ditampilkan.
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    @endif

    @push('scripts')
        <script>
            function projectBoard(canMove, livewireComponent) {
                return {
                    draggedTicketId: null,
                    draggedElement: null,
                    $wire: livewireComponent,

                    init() {
                        // No need for complex setup, Alpine handles it
                    },

                    handleDragStart(event, ticketId) {
                        if (!canMove) {
                            event.preventDefault();
                            return;
                        }

                        this.draggedTicketId = ticketId;
                        this.draggedElement = event.target;

                        if (event.dataTransfer) {
                            event.dataTransfer.effectAllowed = 'move';
                            event.dataTransfer.setData('text/plain', ticketId.toString());
                        }

                        event.target.classList.add('opacity-50');
                    },

                    handleDragEnd(event) {
                        if (this.draggedElement) {
                            this.draggedElement.classList.remove('opacity-50');
                        }
                        this.draggedTicketId = null;
                        this.draggedElement = null;
                    },

                    handleDragOver(event, statusId) {
                        if (!canMove || !this.draggedTicketId) {
                            return;
                        }

                        event.preventDefault();
                        event.stopPropagation();

                        const column = event.currentTarget;
                        column.classList.add('bg-primary-50', 'dark:bg-primary-950');
                    },

                    handleDragLeave(event, statusId) {
                        const column = event.currentTarget;
                        if (!column.contains(event.relatedTarget)) {
                            column.classList.remove('bg-primary-50', 'dark:bg-primary-950');
                        }
                    },

                    handleDrop(event, statusId) {
                        if (!canMove || !this.draggedTicketId) {
                            return;
                        }

                        event.preventDefault();
                        event.stopPropagation();

                        const column = event.currentTarget;
                        column.classList.remove('bg-primary-50', 'dark:bg-primary-950');

                        const ticketId = this.draggedTicketId;
                        const newStatusId = statusId;

                        if (ticketId && newStatusId && ticketId !== newStatusId) {
                            // Use $wire which is passed from Livewire component
                            if (this.$wire) {
                                this.$wire.call('moveTicket', Number(ticketId), Number(newStatusId));
                            }
                        }

                        this.draggedTicketId = null;
                        this.draggedElement = null;
                    },
                };
            }
        </script>
    @endpush
</x-filament-panels::page>
