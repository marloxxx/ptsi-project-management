<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? config('app.name') }}</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600;plus-jakarta-sans:400,500,600&display=swap"
        rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @livewireStyles

    <style>
        :root {
            color-scheme: light;
        }

        /* Tailwind handles most styles; keep minimal customizations if needed */
    </style>
</head>

<body class="min-h-screen bg-gradient-to-b from-slate-50 to-slate-100 text-slate-900">
    <div class="flex min-h-screen flex-col">
        <header class="border-b border-slate-200 bg-white/80 backdrop-blur">
            <div class="mx-auto w-full max-w-6xl px-4 sm:px-6 lg:px-8">
                <nav aria-label="{{ __('External portal navigation') }}" class="flex h-16 items-center justify-between">
                    <div class="flex items-center gap-3">
                        <img src="{{ asset('images/si-logo-horizontal.png') }}" alt="PTSI" class="h-8 w-auto">
                        <span
                            class="hidden sm:inline-block text-sm font-medium text-slate-500">{{ config('app.name') }}</span>
                    </div>
                    <div class="text-xs font-medium text-slate-500">
                        {{ now()->format('d M Y, H:i') }}
                    </div>
                </nav>
            </div>
        </header>

        <main class="flex-1 py-10">
            <div class="mx-auto w-full max-w-5xl px-4 sm:px-6 lg:px-8">
                {{ $slot }}
            </div>
        </main>

        <footer class="border-t border-slate-200 bg-white/80 py-4 text-center text-sm text-slate-500 backdrop-blur">
            <div class="mx-auto w-full max-w-6xl px-4 sm:px-6 lg:px-8">
                &copy; {{ now()->format('Y') }} {{ config('app.name') }}. {{ __('All rights reserved.') }}
            </div>
        </footer>
    </div>

    @livewireScripts
    @stack('scripts')
</body>

</html>
