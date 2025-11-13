<?php

use App\Filament\Pages\Auth\Login as FilamentLogin;
use App\Livewire\External\Dashboard as ExternalDashboard;
use App\Livewire\External\Login as ExternalLogin;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Route;

Route::name('filament.')
    ->group(function (): void {
        Route::name('admin.')
            ->group(function (): void {
                Route::name('auth.')
                    ->group(function (): void {
                        Route::get('/login', FilamentLogin::class)
                            ->middleware(Filament::getPanel('admin')->getMiddleware())
                            ->name('login');
                    });
            });
    });

if ($filamentLoginRoute = Route::getRoutes()->getByName('filament.admin.auth.login')) {
    Route::getRoutes()->add(
        tap(clone $filamentLoginRoute, static function (\Illuminate\Routing\Route $route): void {
            $route->name('login');
        }),
    );
}

Route::prefix('external')
    ->name('external.')
    ->group(function (): void {
        Route::get('{token}', ExternalLogin::class)
            ->name('login');

        Route::get('{token}/dashboard', ExternalDashboard::class)
            ->name('dashboard');
    });
