<?php

declare(strict_types=1);

namespace App\Domain\Services;

use App\Models\ExternalAccessToken;
use App\Models\Project;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection;

interface ExternalPortalServiceInterface
{
    /**
     * Resolve the active external access token with its project.
     *
     * @return array{token: ExternalAccessToken, project: Project}
     *
     * @throws ModelNotFoundException
     */
    public function resolveContext(string $token): array;

    public function verifyPassword(ExternalAccessToken $token, string $password): bool;

    public function markAccessed(ExternalAccessToken $token): void;

    /**
     * @return array{
     *     total: int,
     *     completed: int,
     *     progress: float,
     *     overdue: int,
     *     new_this_week: int,
     *     completed_this_week: int
     * }
     */
    public function projectSummary(Project $project): array;

    /**
     * @return Collection<int, array{id: int, name: string, color: ?string, count: int}>
     */
    public function ticketsByStatus(Project $project): Collection;

    /**
     * @return Collection<int, array{id: int, name: string}>
     */
    public function ticketPriorities(): Collection;

    /**
     * @param  array{
     *     status_id?: int|null,
     *     priority_id?: int|null,
     *     search?: string|null,
     *     page_name?: string
     * }  $filters
     */
    public function paginatedTickets(Project $project, array $filters = []): LengthAwarePaginator;

    /**
     * @param  array{page_name?: string}  $filters
     */
    public function recentActivities(Project $project, array $filters = []): LengthAwarePaginator;
}
