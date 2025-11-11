@props(['title'])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="filament js-focus-visible min-h-full antialiased dark"
    data-theme="dark">

<head>
    {{ \Filament\Support\Facades\FilamentAsset::renderStyles() }}
    {{ \Filament\Support\Facades\FilamentAsset::renderScripts() }}
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ filled($title ?? null) ? "{$title} - " : '' }}{{ config('app.name') }}</title>
</head>

<body class="min-h-screen bg-gray-50 dark:bg-gray-900">
    <div class="min-h-screen grid lg:grid-cols-2 items-center p-4">
        {{-- KIRI: Panel informasi/brand --}}
        <div class="hidden lg:flex items-center justify-center p-6">
            <div class="relative max-w-xl w-full">
                <div
                    class="absolute inset-0 rounded-3xl blur-2xl opacity-30 bg-gradient-to-br from-rose-300 via-blue-300 to-teal-200">
                </div>
                <div class="relative bg-white/90 dark:bg-slate-800/90 rounded-3xl shadow-xl p-8 backdrop-blur">
                    {{-- Logo/Brand --}}
                    <div class="mb-6 text-center">
                        <img src="{{ asset('images/si-logo-horizontal.png') }}" alt="HCML 360" class="h-16 mx-auto">
                    </div>

                    {{-- Headline & Copy --}}
                    <div class="space-y-3 text-center">
                        <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Human Capital Maturity Assessment
                        </h2>
                        <p class="text-gray-600 dark:text-gray-300">
                            Platform komprehensif untuk mengukur & meningkatkan kematangan human capital organisasi.
                        </p>
                    </div>

                    {{-- Grid fitur/area assessment --}}
                    <div class="grid grid-cols-2 gap-4 mt-6">
                        @php
                            $areas = [
                                [
                                    'name' => 'HR Strategy',
                                    'icon' => '🎯',
                                    'cls' => 'bg-rose-50 text-rose-600 dark:bg-rose-900/30 dark:text-rose-300',
                                ],
                                [
                                    'name' => 'Learning & Development',
                                    'icon' => '📚',
                                    'cls' => 'bg-blue-50 text-blue-600 dark:bg-blue-900/30 dark:text-blue-300',
                                ],
                                [
                                    'name' => 'Performance',
                                    'icon' => '📊',
                                    'cls' =>
                                        'bg-emerald-50 text-emerald-600 dark:bg-emerald-900/30 dark:text-emerald-300',
                                ],
                                [
                                    'name' => 'Talent',
                                    'icon' => '👥',
                                    'cls' => 'bg-amber-50 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300',
                                ],
                            ];
                        @endphp
                        @foreach ($areas as $a)
                            <div class="p-4 rounded-xl text-center {{ $a['cls'] }}">
                                <div class="text-2xl mb-1">{{ $a['icon'] }}</div>
                                <div class="text-sm font-medium">{{ $a['name'] }}</div>
                            </div>
                        @endforeach
                    </div>

                    {{-- Partner logos --}}
                    <div class="border-t border-gray-200 dark:border-white/10 mt-8 pt-6">
                        <p class="text-center text-sm text-gray-500 dark:text-gray-400 mb-4">Trusted by leading
                            organizations</p>
                        <div class="flex justify-center items-center gap-6 opacity-80">
                            <img src="{{ asset('images/si-logo.png') }}" alt="Surveyor Indonesia" class="h-10">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- KANAN: Slot form login Filament --}}
        <div class="w-full max-w-md mx-auto lg:mx-0">
            <div class="mb-8 lg:hidden text-center">
                <img src="{{ asset('images/si-logo-horizontal.png') }}" alt="HCML 360" class="h-12 mx-auto">
            </div>

            {{-- Filament akan merender konten halaman login di sini --}}
            {{ $slot }}
        </div>
    </div>
</body>

</html>
