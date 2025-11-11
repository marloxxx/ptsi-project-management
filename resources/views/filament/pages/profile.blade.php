<x-filament-panels::page>
    @php
        $state = $this->form->getState();
        $avatarPath = $state['avatar'] ?? null;
        $avatarUrl = $avatarPath ? \Illuminate\Support\Facades\Storage::disk('public')->url($avatarPath) : null;
    $user = auth()->user();

    $fullName = $state['full_name'] ?? ($user->full_name ?? $user->name);
    $position = $state['position'] ?? ($user->position ?? '—');
    $status = $state['status'] ?? ($user->status ?? 'active');
    $statusLabel = match ($status) {
        'active' => 'Aktif',
        'inactive' => 'Nonaktif',
        default => ucfirst($status),
    };
    $statusClasses = match ($status) {
        'active' => 'bg-emerald-100 text-emerald-700 ring-emerald-500/30',
        'inactive' => 'bg-rose-100 text-rose-700 ring-rose-500/30',
        default => 'bg-slate-100 text-slate-700 ring-slate-500/30',
    };
    $summaryItems = [
        [
            'label' => 'Email',
            'value' => $state['email'] ?? $user->email,
            'icon' => 'heroicon-o-envelope',
        ],
        [
            'label' => 'Username',
            'value' => $state['username'] ?? ($user->username ?? '—'),
            'icon' => 'heroicon-o-at-symbol',
        ],
        [
            'label' => 'NIK',
            'value' => $state['nik'] ?? $user->nik ?? '—',
            'icon' => 'heroicon-o-identification',
        ],
        [
            'label' => 'Nomor Telepon',
            'value' => $state['phone'] ?? $user->phone ?? '—',
            'icon' => 'heroicon-o-phone',
        ],
        [
            'label' => 'Kota',
            'value' => $state['city'] ?? $user->city ?? '—',
            'icon' => 'heroicon-o-building-office-2',
        ],
        [
            'label' => 'Tanggal Lahir',
            'value' => isset($state['date_of_birth']) && $state['date_of_birth']
                ? \Illuminate\Support\Carbon::parse($state['date_of_birth'])
                    ->locale(app()->getLocale())
                    ->translatedFormat('d F Y')
                : optional($user->date_of_birth)?->locale(app()->getLocale())?->translatedFormat('d F Y') ?? '—',
            'icon' => 'heroicon-o-calendar-days',
        ],
    ];
    @endphp

    <div class="mb-6 overflow-hidden rounded-2xl bg-gradient-to-r from-[#0f2557] via-[#1e3a8a] to-[#3b82f6] p-6">
        <div class="flex flex-col gap-6 md:flex-row md:items-center md:justify-between">
            <div class="flex items-center gap-4">
                <div
                    class="size-20 shrink-0 overflow-hidden rounded-2xl border-4 border-white/20 bg-white/10 shadow-lg backdrop-blur">
                    @if ($avatarUrl)
                        <img src="{{ $avatarUrl }}" alt="Avatar" class="size-full object-cover" />
                    @else
                        <div class="grid size-full place-items-center">
                            <x-filament::icon icon="heroicon-o-user" class="size-10 text-white" />
                        </div>
                    @endif
                </div>

                <div class="space-y-2 text-white">
                    <div class="flex items-center gap-2">
                        <p class="text-xl font-semibold">{{ $fullName }}</p>
                        <span class="rounded-full px-3 py-1 text-xs font-medium ring-2 {{ $statusClasses }}">
                            {{ $statusLabel }}
                        </span>
                    </div>
                    <p class="text-sm text-white/80">
                        {{ $position }}
                    </p>
                </div>
            </div>

            <div class="grid w-full gap-3 text-white/90 md:max-w-xl md:grid-cols-3">
                @foreach ($summaryItems as $item)
                    <div class="flex items-center gap-3 rounded-xl bg-white/10 px-4 py-3 backdrop-blur">
                        <div class="flex size-10 items-center justify-center rounded-lg bg-white/20">
                            <x-filament::icon :icon="$item['icon']" class="size-5" />
                        </div>
                        <div class="leading-tight">
                            <p class="text-xs uppercase tracking-wide text-white/70">{{ $item['label'] }}</p>
                            <p class="text-sm font-semibold text-white">{{ $item['value'] }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    {{ $this->form }}

    <x-filament-actions::modals />
</x-filament-panels::page>
