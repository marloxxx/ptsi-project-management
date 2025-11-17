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

        $this->app->bind(
            \App\Domain\Repositories\ProjectRepositoryInterface::class,
            \App\Infrastructure\Repositories\ProjectRepository::class
        );

        $this->app->bind(
            \App\Domain\Repositories\TicketRepositoryInterface::class,
            \App\Infrastructure\Repositories\TicketRepository::class
        );

        $this->app->bind(
            \App\Domain\Repositories\TicketStatusRepositoryInterface::class,
            \App\Infrastructure\Repositories\TicketStatusRepository::class
        );

        $this->app->bind(
            \App\Domain\Repositories\TicketPriorityRepositoryInterface::class,
            \App\Infrastructure\Repositories\TicketPriorityRepository::class
        );

        $this->app->bind(
            \App\Domain\Repositories\EpicRepositoryInterface::class,
            \App\Infrastructure\Repositories\EpicRepository::class
        );

        $this->app->bind(
            \App\Domain\Repositories\ProjectNoteRepositoryInterface::class,
            \App\Infrastructure\Repositories\ProjectNoteRepository::class
        );

        $this->app->bind(
            \App\Domain\Repositories\TicketCommentRepositoryInterface::class,
            \App\Infrastructure\Repositories\TicketCommentRepository::class
        );

        $this->app->bind(
            \App\Domain\Repositories\TicketHistoryRepositoryInterface::class,
            \App\Infrastructure\Repositories\TicketHistoryRepository::class
        );

        $this->app->bind(
            \App\Domain\Repositories\ExternalAccessTokenRepositoryInterface::class,
            \App\Infrastructure\Repositories\ExternalAccessTokenRepository::class
        );

        $this->app->bind(
            \App\Domain\Repositories\AnalyticsRepositoryInterface::class,
            \App\Infrastructure\Repositories\AnalyticsRepository::class
        );

        $this->app->bind(
            \App\Domain\Repositories\SprintRepositoryInterface::class,
            \App\Infrastructure\Repositories\SprintRepository::class
        );

        $this->app->bind(
            \App\Domain\Repositories\ProjectWorkflowRepositoryInterface::class,
            \App\Infrastructure\Repositories\ProjectWorkflowRepository::class
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

        $this->app->bind(
            \App\Domain\Services\ProjectServiceInterface::class,
            \App\Application\Services\ProjectService::class
        );

        $this->app->bind(
            \App\Domain\Services\TicketServiceInterface::class,
            \App\Application\Services\TicketService::class
        );

        $this->app->bind(
            \App\Domain\Services\TicketBoardServiceInterface::class,
            \App\Application\Services\TicketBoardService::class
        );

        $this->app->bind(
            \App\Domain\Services\EpicOverviewServiceInterface::class,
            \App\Application\Services\EpicOverviewService::class
        );

        $this->app->bind(
            \App\Domain\Services\PermissionCatalogServiceInterface::class,
            \App\Application\Services\PermissionCatalogService::class
        );

        $this->app->bind(
            \App\Domain\Services\AnalyticsServiceInterface::class,
            \App\Application\Services\AnalyticsService::class
        );

        $this->app->bind(
            \App\Domain\Services\ExternalPortalServiceInterface::class,
            \App\Application\Services\ExternalPortalService::class
        );

        $this->app->bind(
            \App\Domain\Services\SprintServiceInterface::class,
            \App\Application\Services\SprintService::class
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
