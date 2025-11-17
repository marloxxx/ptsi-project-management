<x-filament-panels::page>
    @if (!$selectedProject)
        <x-filament::section>
            <x-slot name="heading">
                Pilih Proyek untuk Melihat Timeline
            </x-slot>
            <x-slot name="description">
                Pilih proyek yang ingin dilihat timeline-nya. Timeline akan dikelompokkan berdasarkan tanggal mulai.
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
                            @if ($project->pinned_at)
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
                                @if ($project->start_date && $project->end_date)
                                    <div class="inline-flex items-center gap-1">
                                        <x-heroicon-o-calendar class="h-4 w-4" />
                                        <span>{{ $project->start_date?->format('d M Y') ?? '-' }} –
                                            {{ $project->end_date?->format('d M Y') ?? '-' }}</span>
                                    </div>
                                @endif
                                <div class="inline-flex items-center gap-1">
                                    <x-heroicon-o-users class="h-4 w-4" />
                                    <span>{{ $project->members_count ?? 0 }} anggota</span>
                                </div>
                            </div>
                        </button>
                    @endforeach
                </div>
            @endif
        </x-filament::section>
    @else
        <div class="space-y-6">
            {{-- Project Header with Back Button --}}
            <x-filament::section>
                <x-slot name="heading">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <x-heroicon-o-calendar-days class="h-5 w-5 text-primary-500" />
                            <span>{{ $selectedProject->name }}</span>
                            @if ($selectedProject->ticket_prefix)
                                <span
                                    class="inline-flex items-center rounded-md px-2 py-0.5 text-xs font-semibold text-white shadow-sm"
                                    style="background-color: {{ $selectedProject->color ?? '#6B7280' }};">
                                    {{ $selectedProject->ticket_prefix }}
                                </span>
                            @endif
                            <span class="text-sm font-normal text-gray-500 dark:text-gray-400">
                                (ID: {{ $selectedProject->id }})
                            </span>
                        </div>
                        <button type="button" wire:click="resetProjectSelection"
                            class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 shadow-sm transition hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700">
                            <x-heroicon-m-arrow-left class="h-4 w-4" />
                            Kembali ke Daftar Proyek
                        </button>
                    </div>
                </x-slot>
                <x-slot name="description">
                    Calendar Timeline untuk proyek {{ $selectedProject->name }} - dikelompokkan berdasarkan tanggal
                    mulai
                </x-slot>
            </x-filament::section>

            {{-- Calendar View --}}
            @if (empty($tasksByDate))
                <x-filament::section>
                    <div class="flex h-56 flex-col items-center justify-center gap-3 text-gray-500 dark:text-gray-400">
                        <x-heroicon-o-calendar class="h-12 w-12" />
                        <p class="text-sm font-medium">Proyek ini belum memiliki tanggal mulai & selesai.</p>
                        <p class="text-xs">Pastikan proyek memiliki informasi jadwal yang lengkap.</p>
                    </div>
                </x-filament::section>
            @else
                <x-filament::section>
                    <x-slot name="heading">
                        <div class="flex items-center justify-between">
                            <span>Calendar Timeline</span>
                            <div class="flex items-center gap-2">
                                <button type="button" wire:click="previousMonth"
                                    class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white p-2 text-gray-700 shadow-sm transition hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700">
                                    <x-heroicon-m-chevron-left class="h-5 w-5" />
                                </button>
                                <button type="button" wire:click="goToToday"
                                    class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 shadow-sm transition hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700">
                                    Hari Ini
                                </button>
                                <span
                                    class="min-w-[160px] text-center text-lg font-semibold text-gray-900 dark:text-white">
                                    {{ $this->currentMonthName }}
                                </span>
                                <button type="button" wire:click="nextMonth"
                                    class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white p-2 text-gray-700 shadow-sm transition hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700">
                                    <x-heroicon-m-chevron-right class="h-5 w-5" />
                                </button>
                            </div>
                        </div>
                    </x-slot>
                    <x-slot name="description">
                        Calendar timeline untuk proyek {{ $selectedProject->name }} - klik tanggal untuk melihat detail
                    </x-slot>

                    <div class="rounded-xl border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
                        {{-- Weekday Headers --}}
                        <div class="grid grid-cols-7 border-b border-gray-200 dark:border-gray-700">
                            @foreach ($this->weekDays as $dayName)
                                <div
                                    class="border-r border-gray-200 p-3 text-center text-sm font-semibold text-gray-700 last:border-r-0 dark:border-gray-700 dark:text-gray-300">
                                    {{ $dayName }}
                                </div>
                            @endforeach
                        </div>

                        {{-- Calendar Days Grid --}}
                        <div class="grid grid-cols-7 divide-x divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach ($this->calendarDays as $day)
                                <div
                                    class="min-h-[120px] border-gray-200 p-2 {{ !$day['isCurrentMonth'] ? 'bg-gray-50 dark:bg-gray-800/50' : ($day['isToday'] ? 'bg-primary-50 dark:bg-primary-900/10' : 'bg-white dark:bg-gray-900') }} {{ $day['isToday'] ? 'border-2 border-primary-500' : 'border-0' }}">
                                    {{-- Date Number --}}
                                    <div
                                        class="mb-2 flex items-center justify-between {{ !$day['isCurrentMonth'] ? 'text-gray-400 dark:text-gray-600' : ($day['isToday'] ? 'text-primary-700 dark:text-primary-300' : ($day['isPast'] ? 'text-gray-500 dark:text-gray-400' : 'text-gray-900 dark:text-white')) }}">
                                        <span
                                            class="text-sm font-semibold {{ $day['isToday'] ? 'inline-flex h-6 w-6 items-center justify-center rounded-full bg-primary-500 text-white' : '' }}">
                                            {{ $day['date']->format('d') }}
                                        </span>
                                        @if ($day['hasTasks'])
                                            <span
                                                class="inline-flex h-2 w-2 rounded-full {{ $day['isToday'] ? 'bg-white' : 'bg-primary-500' }}"></span>
                                        @endif
                                    </div>

                                    {{-- Tasks for this day --}}
                                    @if ($day['hasTasks'])
                                        <div class="space-y-1">
                                            @foreach ($day['tasks'] as $task)
                                                @php
                                                    $status = \Illuminate\Support\Str::slug(
                                                        $task['status'] ?? 'In Progress',
                                                        '_',
                                                    );
                                                    $badgeClasses =
                                                        $this->getStatusBadges()[$status] ??
                                                        'bg-blue-100 text-blue-700 dark:bg-blue-500/10 dark:text-blue-300';
                                                    $taskColor = $task['color'] ?? '#3B82F6';
                                                @endphp
                                                <div class="group relative">
                                                    <div class="cursor-pointer rounded bg-gray-50 px-2 py-1 text-xs transition hover:bg-gray-100 hover:shadow-md dark:bg-gray-800 dark:hover:bg-gray-700"
                                                        style="border-left: 3px solid {{ $taskColor }};">
                                                        <div class="truncate font-medium text-gray-900 dark:text-white"
                                                            title="{{ $task['text'] }}">
                                                            {{ $task['text'] }}
                                                        </div>
                                                        <div
                                                            class="mt-0.5 flex items-center gap-1 text-xs text-gray-500 dark:text-gray-400">
                                                            <span class="truncate">{{ $task['status'] }}</span>
                                                            <span>•</span>
                                                            <span
                                                                class="font-medium">{{ (int) round(($task['progress'] ?? 0) * 100) }}%</span>
                                                        </div>
                                                    </div>

                                                    {{-- Tooltip on hover --}}
                                                    <div
                                                        class="invisible absolute left-0 top-full z-50 mt-1 w-72 rounded-lg border border-gray-200 bg-white p-3 shadow-xl group-hover:visible dark:border-gray-700 dark:bg-gray-800">
                                                        <div class="space-y-2">
                                                            <h5 class="font-semibold text-gray-900 dark:text-white">
                                                                {{ $task['text'] }}
                                                            </h5>
                                                            <div
                                                                class="space-y-1 text-xs text-gray-500 dark:text-gray-400">
                                                                <div>
                                                                    <span class="font-medium">Project ID:</span>
                                                                    <span class="font-mono">{{ $task['id'] }}</span>
                                                                </div>
                                                                <div>
                                                                    <span class="font-medium">Status:</span>
                                                                    <span
                                                                        class="{{ $badgeClasses }} ml-1 rounded-full px-1.5 py-0.5">
                                                                        {{ $task['status'] }}
                                                                    </span>
                                                                </div>
                                                                <div>
                                                                    <span class="font-medium">Durasi:</span>
                                                                    {{ $task['duration'] }} hari
                                                                </div>
                                                                @php
                                                                    $startDate = \Carbon\Carbon::parse(
                                                                        $task['start_date'],
                                                                    );
                                                                    $endDate = \Carbon\Carbon::parse($task['end_date']);
                                                                @endphp
                                                                <div>
                                                                    <span class="font-medium">Mulai:</span>
                                                                    {{ $startDate->translatedFormat('d M Y') }}
                                                                </div>
                                                                <div>
                                                                    <span class="font-medium">Selesai:</span>
                                                                    {{ $endDate->translatedFormat('d M Y') }}
                                                                </div>
                                                                <div class="pt-1">
                                                                    <div
                                                                        class="h-1.5 rounded-full bg-gray-200 dark:bg-gray-700">
                                                                        <div class="h-1.5 rounded-full transition-all"
                                                                            style="width: {{ (int) round(($task['progress'] ?? 0) * 100) }}%; background-color: {{ $taskColor }};">
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                </x-filament::section>
            @endif
        </div>
    @endif
</x-filament-panels::page>
