<?php

declare(strict_types=1);

namespace App\Domain\Services;

use App\Models\Project;
use Illuminate\Support\Collection;

interface TicketBoardServiceInterface
{
    /**
     * Retrieve projects accessible to a given user for board/timeline usage.
     *
     * @return Collection<int, Project>
     */
    public function listAccessibleProjects(int $userId): Collection;

    /**
     * Resolve board context containing project, status columns, and member data.
     *
     * @param  array<string, mixed>  $filters
     *                                         - assignee_ids: array<int, int>
     * @return array{
     *     project: Project,
     *     statuses: Collection<int, \App\Models\TicketStatus>,
     *     members: Collection<int, \App\Models\User>
     * }
     */
    public function getBoardContext(int $projectId, int $userId, array $filters = []): array;

    /**
     * Build timeline snapshot data for Gantt-style visualization.
     *
     * @return array{
     *     counts: array<string, int>,
     *     gantt: array{data: array<int, array<string, mixed>>, links: array<int, mixed>}
     * }
     */
    public function getTimelineSnapshot(int $userId): array;
}
