<x-filament-panels::page>
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-900">
            <div class="flex items-center justify-between text-sm text-gray-500 dark:text-gray-400">
                <span>Total Proyek Terjadwal</span>
                <x-heroicon-o-rectangle-stack class="h-4 w-4" />
            </div>
            <p class="mt-2 text-2xl font-semibold text-gray-900 dark:text-white">{{ $counts['all'] ?? 0 }}</p>
        </div>
        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-900">
            <div class="flex items-center justify-between text-sm text-red-500 dark:text-red-300">
                <span>Proyek Terlambat</span>
                <x-heroicon-o-exclamation-triangle class="h-4 w-4" />
            </div>
            <p class="mt-2 text-2xl font-semibold text-red-600 dark:text-red-400">{{ $counts['overdue'] ?? 0 }}</p>
            <p class="text-xs text-gray-500 dark:text-gray-400">Akhir melewati hari ini</p>
        </div>
        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-900">
            <div class="flex items-center justify-between text-sm text-amber-500 dark:text-amber-300">
                <span>Mendekati Deadline</span>
                <x-heroicon-o-clock class="h-4 w-4" />
            </div>
            <p class="mt-2 text-2xl font-semibold text-amber-500 dark:text-amber-300">
                {{ $counts['approaching_deadline'] ?? 0 }}</p>
            <p class="text-xs text-gray-500 dark:text-gray-400">&lt; 7 hari menuju selesai</p>
        </div>
        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-900">
            <div class="flex items-center justify-between text-sm text-emerald-500 dark:text-emerald-300">
                <span>Hampir Selesai</span>
                <x-heroicon-o-chart-bar class="h-4 w-4" />
            </div>
            <p class="mt-2 text-2xl font-semibold text-emerald-500 dark:text-emerald-300">
                {{ $counts['nearly_complete'] ?? 0 }}</p>
            <p class="text-xs text-gray-500 dark:text-gray-400">Progress &ge; 80%</p>
        </div>
    </div>

    <x-filament::section>
        <x-slot name="heading">
            Garis Waktu Proyek
        </x-slot>

        @if (empty($tasks))
            <div class="flex h-56 flex-col items-center justify-center gap-3 text-gray-500 dark:text-gray-400">
                <x-heroicon-o-calendar class="h-12 w-12" />
                <p class="text-sm font-medium">Belum ada proyek dengan tanggal mulai & selesai.</p>
                <p class="text-xs">Pastikan setiap proyek memiliki informasi jadwal yang lengkap.</p>
            </div>
        @else
            <div class="space-y-4">
                @foreach ($tasks as $task)
                    @php
                        $progressPercent = (int) round(($task['progress'] ?? 0) * 100);
                        $startDate = \Carbon\Carbon::parse($task['start_date']);
                        $endDate = \Carbon\Carbon::parse($task['end_date']);
                        $status = \Illuminate\Support\Str::slug($task['status'] ?? 'In Progress', '_');
                        $badgeClasses =
                            $this->getStatusBadges()[$status] ??
                            'bg-blue-100 text-blue-700 dark:bg-blue-500/10 dark:text-blue-300';
                    @endphp
                    <div
                        class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm transition dark:border-gray-700 dark:bg-gray-900">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <h3 class="text-base font-semibold leading-tight text-gray-900 dark:text-white">
                                    {{ $task['text'] }}
                                </h3>
                                <div
                                    class="mt-2 flex flex-wrap items-center gap-2 text-xs text-gray-500 dark:text-gray-400">
                                    <span
                                        class="{{ $badgeClasses }} inline-flex items-center gap-1 rounded-full px-2.5 py-1 font-medium">
                                        <span class="h-2 w-2 rounded-full"
                                            style="background-color: {{ $task['color'] ?? '#3B82F6' }};"></span>
                                        {{ $task['status'] }}
                                    </span>
                                    <span class="inline-flex items-center gap-1">
                                        <x-heroicon-m-calendar class="h-4 w-4" />
                                        {{ $startDate->translatedFormat('d M Y') }} –
                                        {{ $endDate->translatedFormat('d M Y') }}
                                    </span>
                                    <span class="inline-flex items-center gap-1">
                                        <x-heroicon-m-clock class="h-4 w-4" />
                                        {{ $task['duration'] }} hari
                                    </span>
                                </div>
                            </div>
                            <span class="text-sm font-semibold text-gray-700 dark:text-gray-300">
                                {{ $progressPercent }}%
                            </span>
                        </div>

                        <div class="mt-4">
                            <div class="h-2 rounded-full bg-gray-200 dark:bg-gray-700">
                                <div class="h-2 rounded-full bg-primary-500 transition-all"
                                    style="width: {{ $progressPercent }}%; background-color: {{ $task['color'] ?? '#3B82F6' }};">
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </x-filament::section>
</x-filament-panels::page>
