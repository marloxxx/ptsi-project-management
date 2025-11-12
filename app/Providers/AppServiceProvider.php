<?php

namespace App\Providers;

use Filament\Support\Facades\FilamentView;
use Filament\View\PanelsRenderHook;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if ($this->app->runningUnitTests()) {
            return;
        }

        // Add custom CSS after Filament styles using render hook
        FilamentView::registerRenderHook(
            PanelsRenderHook::STYLES_AFTER,
            fn(): string => view('filament.custom-styles')->render()
        );
    }
}
