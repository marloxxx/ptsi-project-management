<?php

use App\Filament\Pages\Auth\Login as FilamentLogin;
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
