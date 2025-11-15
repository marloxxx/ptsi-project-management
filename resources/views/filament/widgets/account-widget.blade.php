@php
    $user = filament()->auth()->user();
@endphp

<x-filament-widgets::widget class="fi-account-widget">
    <x-filament::section>
        <div class="flex items-center gap-4">
            <x-filament-panels::avatar.user size="lg" :user="$user" loading="lazy" />

            <div class="flex-1">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
                    Selamat Datang, {{ filament()->getUserName($user) }}!
                </h2>

                @if ($user->unit)
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                        <x-filament::icon icon="heroicon-o-building-office" class="w-4 h-4 inline mr-1" />
                        {{ $user->unit->name }}
                    </p>
                @endif

                @if ($user->position)
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                        <x-filament::icon icon="heroicon-o-briefcase" class="w-4 h-4 inline mr-1" />
                        {{ $user->position }}
                    </p>
                @endif
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
