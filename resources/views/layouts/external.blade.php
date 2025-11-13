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

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@picocss/pico@2/css/pico.min.css"
        integrity="sha384-q3G6zBFCYLHGNO6iV9RbQwTecKkRxp1inWAPaAgVJ9wHFmp32SExGvtapXHQWZ0O" crossorigin="anonymous" />

    @livewireStyles

    <style>
        :root {
            color-scheme: light;
        }

        body {
            font-family: 'Inter', 'Plus Jakarta Sans', system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background-color: #f8fafc;
            color: #0f172a;
            min-height: 100vh;
        }

        .app-shell {
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        .app-header,
        .app-footer {
            background-color: #ffffff;
            border-color: #e2e8f0;
        }

        .card {
            border-radius: 0.75rem;
            box-shadow: 0 10px 40px -20px rgba(15, 23, 42, 0.25);
        }

        @media (min-width: 1024px) {
            .content-container {
                max-width: 1100px;
            }
        }
    </style>
</head>

<body>
    <div class="app-shell">
        <header class="app-header border-bottom">
            <div class="container-fluid py-3 content-container">
                <nav aria-label="{{ __('External portal navigation') }}"
                    class="d-flex justify-content-between align-items-center">
                    <strong>{{ config('app.name') }}</strong>
                    <small class="text-secondary">
                        {{ now()->format('d M Y, H:i') }}
                    </small>
                </nav>
            </div>
        </header>

        <main class="flex-grow-1 py-5">
            <div class="container content-container">
                {{ $slot }}
            </div>
        </main>

        <footer class="app-footer border-top py-3">
            <div class="container content-container text-center text-secondary">
                <small>&copy; {{ now()->format('Y') }} {{ config('app.name') }}.
                    {{ __('All rights reserved.') }}</small>
            </div>
        </footer>
    </div>

    @livewireScripts
    @stack('scripts')
</body>

</html>
