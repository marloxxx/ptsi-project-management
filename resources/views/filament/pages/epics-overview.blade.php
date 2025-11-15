@php
    use App\Filament\Resources\Tickets\TicketResource;

    $selectedProject = $this->getSelectedProject();
@endphp

<x-filament-panels::page>
    @if (!$selectedProject)
        <x-filament::section>
            <x-slot name="heading">
                Pilih Proyek
            </x-slot>

            <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
                Tentukan proyek terlebih dahulu untuk melihat daftar epics yang terkait.
            </p>

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
                    <x-heroicon-o-rectangle-stack class="w-12 h-12 mb-3" />
                    <p class="text-sm font-medium">Belum ada proyek yang bisa diakses.</p>
                    <p class="text-xs">Hubungi administrator untuk menambahkan Anda sebagai anggota proyek.</p>
                </div>
            @elseif ($this->filteredProjects->isEmpty())
                <div class="flex flex-col items-center justify-center py-12 text-gray-500 dark:text-gray-400">
                    <x-heroicon-o-magnifying-glass-circle class="w-12 h-12 mb-3" />
                    <p class="text-sm font-medium">Tidak ditemukan proyek dengan kata kunci tersebut.</p>
                    <p class="text-xs">Coba gunakan kata kunci lain.</p>
                </div>
            @else
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                    @foreach ($this->filteredProjects as $project)
                        <button type="button" wire:key="project-card-{{ $project->id }}"
                            wire:click="selectProject({{ $project->id }})"
                            class="relative flex flex-col items-start gap-3 rounded-xl border border-gray-200 bg-white p-4 text-left shadow-sm transition hover:shadow-md dark:border-gray-700 dark:bg-gray-800"
                            style="border-left-width:4px;border-left-color:{{ $project->color ?? '#6B7280' }};">
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
    @else
        <div class="mb-6 flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <p class="text-sm text-gray-500 dark:text-gray-400">Sedang melihat epics untuk:</p>
                <div
                    class="mt-1 inline-flex items-center gap-3 rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-900 shadow-sm dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                    @if ($selectedProject->ticket_prefix)
                        <span class="rounded-md px-2 py-0.5 text-xs font-semibold text-white"
                            style="background-color: {{ $selectedProject->color ?? '#6B7280' }};">
                            {{ $selectedProject->ticket_prefix }}
                        </span>
                    @endif
                    <span>{{ $selectedProject->name }}</span>
                </div>
            </div>

            <div class="flex flex-wrap gap-2">
                <x-filament::button color="gray" icon="heroicon-m-arrow-uturn-left"
                    wire:click="resetProjectSelection">
                    Ganti Proyek
                </x-filament::button>

                <div class="relative">
                    <x-heroicon-m-magnifying-glass
                        class="w-4 h-4 absolute inset-y-0 left-0 my-auto ml-3 text-gray-400" />
                    <input type="search" wire:model.live.debounce.400ms="epicSearch"
                        class="block w-full rounded-lg border border-gray-200 bg-white py-2 pl-9 pr-3 text-sm text-gray-900 placeholder-gray-400 focus:border-primary-500 focus:ring-2 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white dark:placeholder-gray-500"
                        placeholder="Cari nama atau deskripsi epic..." />
                </div>
            </div>
        </div>

        @if ($epics->isEmpty())
            <div
                class="flex flex-col items-center justify-center gap-4 rounded-xl border border-dashed border-gray-300 bg-white py-16 text-gray-500 shadow-sm dark:border-gray-700 dark:bg-gray-900 dark:text-gray-400">
                <x-heroicon-o-flag class="w-16 h-16" />
                <div class="text-center">
                    <p class="text-base font-semibold">Belum ada epic pada proyek ini</p>
                    <p class="text-sm">Buat epic baru dari halaman detail proyek untuk mulai mengelompokkan tiket.</p>
                </div>
            </div>
        @else
            <x-filament::section>
                <x-slot name="heading">
                    Daftar Epics
                </x-slot>

                <div class="space-y-4">
                    @foreach ($epics as $epic)
                        @php
                            $totalTickets = $epic->tickets->count();
                            $completedTickets = $epic->tickets
                                ->filter(fn($ticket) => (bool) ($ticket->status?->is_completed ?? false))
                                ->count();
                            $activeTickets = $epic->tickets
                                ->filter(fn($ticket) => !($ticket->status?->is_completed ?? false) && $ticket->status)
                                ->count();
                            $backlogTickets = max($totalTickets - $completedTickets - $activeTickets, 0);
                            $overdueTickets = $epic->tickets
                                ->filter(
                                    fn($ticket) => $ticket->due_date &&
                                        $ticket->due_date->isPast() &&
                                        !($ticket->status?->is_completed ?? false),
                                )
                                ->count();
                            $progressPercentage =
                                $totalTickets > 0 ? (int) round(($completedTickets / $totalTickets) * 100) : 0;
                        @endphp

                        <div wire:key="epic-card-{{ $epic->id }}"
                            class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                            <button type="button" wire:click="toggleEpic({{ $epic->id }})"
                                class="group flex w-full items-stretch justify-between border-b border-gray-100 bg-gradient-to-r from-gray-50 to-white px-4 py-3 text-left transition hover:from-gray-100 hover:to-white dark:border-gray-700 dark:from-gray-900 dark:to-gray-800"
                                aria-expanded="{{ $this->isExpanded($epic->id) ? 'true' : 'false' }}">
                                <div class="flex flex-1 flex-col gap-2">
                                    <div class="flex items-start justify-between gap-4">
                                        <div>
                                            <div class="flex items-center gap-3">
                                                <p class="text-base font-semibold text-gray-900 dark:text-white">
                                                    {{ $epic->name }}
                                                </p>
                                                @if ($overdueTickets > 0)
                                                    <span
                                                        class="inline-flex items-center gap-1 rounded-full bg-red-100 px-2 py-0.5 text-xs font-semibold text-red-700 dark:bg-red-500/10 dark:text-red-300">
                                                        <x-heroicon-m-exclamation-circle class="h-3.5 w-3.5" />
                                                        {{ $overdueTickets }} overdue
                                                    </span>
                                                @endif
                                            </div>
                                            <div
                                                class="mt-1 flex flex-wrap items-center gap-3 text-xs text-gray-500 dark:text-gray-400">
                                                <span class="inline-flex items-center gap-1">
                                                    <x-heroicon-m-calendar class="h-4 w-4" />
                                                    <span>{{ $epic->start_date?->format('d M Y') ?? '—' }} —
                                                        {{ $epic->end_date?->format('d M Y') ?? '—' }}</span>
                                                </span>
                                                <span class="inline-flex items-center gap-1">
                                                    <x-heroicon-m-clipboard-document-list class="h-4 w-4" />
                                                    <span>{{ $totalTickets }} tiket</span>
                                                </span>
                                            </div>
                                        </div>

                                        <div
                                            class="flex items-center gap-4 rounded-lg border border-transparent px-3 py-2 transition group-hover:border-gray-200 dark:group-hover:border-gray-700">
                                            <div class="hidden w-40 lg:block">
                                                <div
                                                    class="mb-1 flex items-center justify-between text-xs text-gray-500 dark:text-gray-400">
                                                    <span>Progress</span>
                                                    <span>{{ $progressPercentage }}%</span>
                                                </div>
                                                <div class="h-2 w-full rounded-full bg-gray-200 dark:bg-gray-700">
                                                    <div class="h-full rounded-full bg-gradient-to-r from-primary-500 to-primary-400 transition-all"
                                                        style="width: {{ $progressPercentage }}%;"></div>
                                                </div>
                                            </div>

                                            <div
                                                class="grid grid-cols-3 divide-x divide-gray-200 rounded-lg border border-gray-200 bg-white text-center text-xs font-semibold text-gray-600 dark:divide-gray-700 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">
                                                <div class="px-3 py-1.5">
                                                    <p class="text-lg text-gray-900 dark:text-white">
                                                        {{ $activeTickets }}</p>
                                                    <p class="text-[11px] uppercase tracking-wide">In Progress</p>
                                                </div>
                                                <div class="px-3 py-1.5">
                                                    <p class="text-lg text-gray-900 dark:text-white">
                                                        {{ $backlogTickets }}</p>
                                                    <p class="text-[11px] uppercase tracking-wide">Todo</p>
                                                </div>
                                                <div class="px-3 py-1.5">
                                                    <p class="text-lg text-emerald-600 dark:text-emerald-400">
                                                        {{ $completedTickets }}</p>
                                                    <p class="text-[11px] uppercase tracking-wide">Done</p>
                                                </div>
                                            </div>

                                            <span
                                                class="inline-flex h-9 w-9 items-center justify-center rounded-full border border-gray-200 bg-white text-gray-500 shadow-sm transition group-hover:border-primary-300 group-hover:text-primary-600 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 dark:group-hover:border-primary-500/50 dark:group-hover:text-primary-300">
                                                <x-heroicon-m-chevron-down
                                                    class="h-5 w-5 transform transition duration-200 {{ $this->isExpanded($epic->id) ? 'rotate-180' : '' }}" />
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </button>

                            @if ($this->isExpanded($epic->id))
                                <div class="space-y-4 px-4 py-4">
                                    @if ($epic->description)
                                        <div
                                            class="rounded-lg border border-gray-200 bg-gray-50 p-3 text-sm text-gray-700 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">
                                            {!! $epic->description !!}
                                        </div>
                                    @endif

                                    <div class="flex flex-col gap-2 lg:flex-row lg:items-center lg:justify-between">
                                        <div
                                            class="flex items-center gap-3 text-sm font-semibold text-gray-700 dark:text-gray-200">
                                            <span class="inline-flex items-center gap-2">
                                                <x-heroicon-m-flag class="h-4 w-4 text-primary-500" />
                                                Tiket dalam epic ini
                                            </span>
                                            <span
                                                class="inline-flex items-center gap-1 rounded-full bg-gray-100 px-2 py-0.5 text-xs font-semibold text-gray-600 dark:bg-gray-700 dark:text-gray-300">
                                                {{ $totalTickets }} total tiket
                                            </span>
                                        </div>
                                        <a href="{{ TicketResource::getUrl('create', ['project_id' => $selectedProject->id, 'epic_id' => $epic->id]) }}"
                                            class="inline-flex items-center gap-2 rounded-lg border border-primary-200 bg-primary-50 px-3 py-1.5 text-sm font-semibold text-primary-700 transition hover:bg-primary-100 dark:border-primary-500/40 dark:bg-primary-500/10 dark:text-primary-300"
                                            target="_blank">
                                            <x-heroicon-m-plus class="h-4 w-4" />
                                            Tambah Tiket
                                        </a>
                                    </div>

                                    @if ($epic->tickets->isEmpty())
                                        <div
                                            class="rounded-lg border border-dashed border-gray-300 py-10 text-center text-sm text-gray-500 dark:border-gray-700 dark:text-gray-400">
                                            Belum ada tiket yang dikaitkan.
                                        </div>
                                    @else
                                        <div
                                            class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
                                            <table
                                                class="min-w-full divide-y divide-gray-200 text-sm dark:divide-gray-700">
                                                <thead
                                                    class="bg-gray-50 text-xs uppercase text-gray-500 dark:bg-gray-900 dark:text-gray-400">
                                                    <tr>
                                                        <th class="px-3 py-2 text-left font-medium">ID</th>
                                                        <th class="px-3 py-2 text-left font-medium">Judul</th>
                                                        <th class="px-3 py-2 text-left font-medium">Status</th>
                                                        <th
                                                            class="px-3 py-2 text-left font-medium hidden md:table-cell">
                                                            Penanggung jawab</th>
                                                        <th
                                                            class="px-3 py-2 text-left font-medium hidden lg:table-cell">
                                                            Jatuh tempo</th>
                                                        <th class="px-3 py-2 text-left font-medium">Aksi</th>
                                                    </tr>
                                                </thead>
                                                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                                    @foreach ($epic->tickets as $ticket)
                                                        <tr
                                                            class="bg-white text-gray-900 dark:bg-gray-800 dark:text-gray-100">
                                                            <td class="px-3 py-2 font-mono text-xs">
                                                                {{ $ticket->uuid }}</td>
                                                            <td class="px-3 py-2">
                                                                <p class="font-medium">{{ $ticket->name }}</p>
                                                                @if ($ticket->priority)
                                                                    <span
                                                                        class="mt-1 inline-flex items-center rounded-full px-2 py-0.5 text-[11px] font-semibold text-white"
                                                                        style="background-color: {{ $ticket->priority->color }};">
                                                                        {{ $ticket->priority->name }}
                                                                    </span>
                                                                @endif
                                                            </td>
                                                            <td class="px-3 py-2">
                                                                <span
                                                                    class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-semibold text-white"
                                                                    style="background-color: {{ $ticket->status?->color ?? '#6B7280' }};">
                                                                    <x-heroicon-m-arrow-path class="h-3.5 w-3.5" />
                                                                    {{ $ticket->status->name ?? '—' }}
                                                                </span>
                                                            </td>
                                                            <td class="px-3 py-2 hidden md:table-cell">
                                                                @if ($ticket->assignees->isEmpty())
                                                                    <span
                                                                        class="text-xs text-gray-500 dark:text-gray-400">Belum
                                                                        ditentukan</span>
                                                                @else
                                                                    <div class="flex flex-wrap gap-1">
                                                                        @foreach ($ticket->assignees->take(2) as $assignee)
                                                                            <span
                                                                                class="inline-flex items-center rounded-full bg-primary-100 px-2 py-0.5 text-[11px] font-medium text-primary-800 dark:bg-primary-500/10 dark:text-primary-300">
                                                                                <x-heroicon-m-user
                                                                                    class="mr-1 h-3.5 w-3.5" />
                                                                                {{ \Illuminate\Support\Str::limit($assignee->name, 14) }}
                                                                            </span>
                                                                        @endforeach
                                                                        @if ($ticket->assignees->count() > 2)
                                                                            <span
                                                                                class="inline-flex items-center rounded-full bg-gray-200 px-2 py-0.5 text-[11px] font-medium text-gray-600 dark:bg-gray-700 dark:text-gray-200">
                                                                                +{{ $ticket->assignees->count() - 2 }}
                                                                            </span>
                                                                        @endif
                                                                    </div>
                                                                @endif
                                                            </td>
                                                            @php
                                                                $isOverdue =
                                                                    $ticket->due_date &&
                                                                    $ticket->due_date->isPast() &&
                                                                    !($ticket->status?->is_completed ?? false);
                                                            @endphp
                                                            <td
                                                                class="px-3 py-2 text-xs hidden lg:table-cell {{ $isOverdue ? 'text-red-600 dark:text-red-300' : 'text-gray-600 dark:text-gray-300' }}">
                                                                @if ($ticket->due_date)
                                                                    <span
                                                                        class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 {{ $isOverdue ? 'bg-red-100 text-red-700 dark:bg-red-500/10 dark:text-red-300' : 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300' }}">
                                                                        <x-heroicon-m-clock class="h-3.5 w-3.5" />
                                                                        {{ $ticket->due_date->format('d M Y') }}
                                                                    </span>
                                                                @else
                                                                    —
                                                                @endif
                                                            </td>
                                                            <td class="px-3 py-2">
                                                                <a href="{{ TicketResource::getUrl('view', ['record' => $ticket->id]) }}"
                                                                    target="_blank"
                                                                    class="text-xs font-semibold text-primary-600 hover:text-primary-800 dark:text-primary-400 dark:hover:text-primary-300">
                                                                    Detail
                                                                </a>
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    @endif
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </x-filament::section>
        @endif
    @endif
</x-filament-panels::page>
