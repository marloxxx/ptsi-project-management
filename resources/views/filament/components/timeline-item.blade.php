@props(['record'])

@php
    $actor = $record->actor;
    $fromStatus = $record->fromStatus;
    $toStatus = $record->toStatus;
    $createdAt = $record->created_at;
    $note = $record->note;

    $fromStatusName = $fromStatus?->name ?? 'Initial';
    $toStatusName = $toStatus?->name ?? 'Unknown';
    $toStatusColor = ($toStatus !== null ? $toStatus->color : null) ?? '#3B82F6';
    $actorName = $actor?->name ?? 'System';
    $timeAgo = $createdAt->diffForHumans();
    $formattedDate = $createdAt->format('M d, Y');
    $formattedTime = $createdAt->format('H:i');

    // Helper function to check if color is light (white/very light)
    $isLightColor = function (string $color): bool {
        // Remove # if present
        $color = ltrim($color, '#');

        // Handle 3-digit hex
        if (strlen($color) === 3) {
            $color = $color[0] . $color[0] . $color[1] . $color[1] . $color[2] . $color[2];
        }

        // Convert to RGB
        $r = hexdec(substr($color, 0, 2));
        $g = hexdec(substr($color, 2, 2));
        $b = hexdec(substr($color, 4, 2));

        // Calculate relative luminance
        $luminance = (0.299 * $r + 0.587 * $g + 0.114 * $b) / 255;

        // Consider light if luminance > 0.7 (white/very light colors)
        return $luminance > 0.7;
    };

    // Determine icon based on transition type
    if ($fromStatus === null) {
        $icon = 'heroicon-o-plus-circle';
    } elseif ($toStatus?->is_completed ?? false) {
        $icon = 'heroicon-o-check-circle';
    } else {
        $icon = 'heroicon-o-arrow-right-circle';
    }

    // Determine text color based on background color
    $iconTextColor = $isLightColor($toStatusColor) ? 'text-gray-800 dark:text-gray-900' : 'text-white';
@endphp

<div class="relative flex gap-4 pb-8 border-l-2 border-gray-200 dark:border-gray-700 pl-6 ml-5">
    {{-- Timeline Icon & Line --}}
    <div class="flex flex-col items-center -ml-[38px]">
        <div class="flex items-center justify-center w-10 h-10 rounded-full border-2 z-10 shadow-sm {{ $iconTextColor }}"
            style="background-color: {{ $toStatusColor }}; border-color: {{ $toStatusColor }};">
            @svg($icon, 'w-5 h-5')
        </div>
        <div class="w-0.5 bg-gray-200 dark:bg-gray-700 mt-2 flex-1"></div>
    </div>

    {{-- Timeline Content --}}
    <div class="flex-1 min-w-0 pb-4">
        {{-- Status Transition --}}
        <div class="flex items-center flex-wrap gap-2 mb-2">
            <span
                class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300">
                {{ $fromStatusName }}
            </span>
            @svg('heroicon-o-arrow-right', 'w-4 h-4 text-gray-400')
            <span
                class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium {{ $isLightColor($toStatusColor) ? 'text-gray-800 dark:text-gray-900' : 'text-white' }}"
                style="background-color: {{ $toStatusColor }};">
                {{ $toStatusName }}
            </span>
            <span class="text-xs text-gray-500 dark:text-gray-400 ml-auto">
                {{ $timeAgo }}
            </span>
        </div>

        {{-- Actor & Date --}}
        <div class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400 mb-2">
            @svg('heroicon-o-user', 'w-4 h-4')
            <span class="font-medium">{{ $actorName }}</span>
            <span class="text-gray-400">•</span>
            <span>{{ $formattedDate }} at {{ $formattedTime }}</span>
        </div>

        {{-- Note --}}
        @if ($note)
            <div class="mt-2 text-sm text-gray-600 dark:text-gray-400 bg-gray-50 dark:bg-gray-800/50 rounded-lg p-3 border-l-2"
                style="border-color: {{ $toStatusColor }};">
                {{ $note }}
            </div>
        @endif
    </div>
</div>
