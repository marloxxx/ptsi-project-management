<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

/**
 * Domain Service Provider
 *
 * Register all domain service bindings and repository implementations here.
 * This provider follows the Interface-First pattern for clean architecture.
 *
 * Example:
 * $this->app->bind(UserRepositoryInterface::class, UserRepository::class);
 * $this->app->bind(UserServiceInterface::class, UserService::class);
 */
class DomainServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Register Repository bindings
        $this->app->bind(
            \App\Domain\Repositories\RoleRepositoryInterface::class,
            \App\Infrastructure\Repositories\RoleRepository::class
        );

        $this->app->bind(
            \App\Domain\Repositories\UserRepositoryInterface::class,
            \App\Infrastructure\Repositories\UserRepository::class
        );

        $this->app->bind(
            \App\Domain\Repositories\UnitRepositoryInterface::class,
            \App\Infrastructure\Repositories\UnitRepository::class
        );

        // Register Service bindings
        $this->app->bind(
            \App\Domain\Services\RoleServiceInterface::class,
            \App\Application\Services\RoleService::class
        );

        $this->app->bind(
            \App\Domain\Services\UserServiceInterface::class,
            \App\Application\Services\UserService::class
        );

        $this->app->bind(
            \App\Domain\Services\UnitServiceInterface::class,
            \App\Application\Services\UnitService::class
        );

        $this->app->bind(
            \App\Domain\Services\ExternalUserSyncServiceInterface::class,
            \App\Infrastructure\Services\Integrations\SiPortalExternalUserSyncService::class
        );
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
