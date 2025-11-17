<?php

declare(strict_types=1);

namespace App\Application\Services;

use App\Domain\Repositories\ProjectRepositoryInterface;
use App\Domain\Repositories\TicketStatusRepositoryInterface;
use App\Domain\Repositories\UserRepositoryInterface;
use App\Domain\Services\TicketBoardServiceInterface;
use App\Models\Project;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use RuntimeException;

class TicketBoardService implements TicketBoardServiceInterface
{
    public function __construct(
        private readonly ProjectRepositoryInterface $projectRepository,
        private readonly TicketStatusRepositoryInterface $ticketStatusRepository,
        private readonly UserRepositoryInterface $userRepository,
    ) {}

    public function listAccessibleProjects(int $userId): Collection
    {
        $user = $this->userRepository->find($userId);

        if (! $user instanceof User) {
            return collect();
        }

        $includeAll = $user->hasRole('super_admin');

        return $this->projectRepository->accessibleForUser(
            $user->getKey(),
            [
                'include_all' => $includeAll,
                'with' => [
                    'members:id,name',
                ],
            ]
        );
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function getBoardContext(int $projectId, int $userId, array $filters = []): array
    {
        $user = $this->userRepository->find($userId);

        if (! $user instanceof User) {
            throw new AuthorizationException('User not found.');
        }

        $project = $this->projectRepository->find($projectId, ['members:id,name']);

        if (! $project instanceof Project) {
            throw new RuntimeException('Project not found.');
        }

        if (! $user->hasRole('super_admin') && ! $project->members->contains('id', $user->getKey())) {
            throw new AuthorizationException('You do not have access to this project.');
        }

        /** @var array<int, int> $assigneeIds */
        $assigneeIds = array_values(array_filter(
            array_map(
                static fn ($value): int => (int) $value,
                Arr::get($filters, 'assignee_ids', [])
            ),
            static fn (int $id): bool => $id > 0
        ));

        /** @var int|null $sprintId */
        $sprintId = Arr::get($filters, 'sprint_id');

        $statuses = $this->ticketStatusRepository->boardColumns($project, [
            'assignee_ids' => $assigneeIds,
            'sprint_id' => $sprintId,
        ]);

        $members = $project->members->sortBy('name')->values();

        return [
            'project' => $project,
            'statuses' => $statuses,
            'members' => $members,
        ];
    }

    public function getTimelineSnapshot(int $userId): array
    {
        $user = $this->userRepository->find($userId);

        if (! $user instanceof User) {
            return [
                'counts' => [
                    'all' => 0,
                    'overdue' => 0,
                    'approaching_deadline' => 0,
                    'nearly_complete' => 0,
                ],
                'gantt' => [
                    'data' => [],
                    'links' => [],
                ],
            ];
        }

        $includeAll = $user->hasRole('super_admin');

        $projects = $this->projectRepository->accessibleForUser(
            $user->getKey(),
            [
                'include_all' => $includeAll,
                'require_schedule' => true,
            ]
        );

        $now = Carbon::now();

        $counts = [
            'all' => $projects->count(),
            'overdue' => 0,
            'approaching_deadline' => 0,
            'nearly_complete' => 0,
        ];

        $tasks = $projects->map(function (Project $project) use ($now, &$counts): array {
            $startDate = $project->start_date instanceof Carbon
                ? $project->start_date->copy()
                : Carbon::parse($project->start_date);
            $endDate = $project->end_date instanceof Carbon
                ? $project->end_date->copy()
                : Carbon::parse($project->end_date);

            $totalDays = max(1, $startDate->diffInDays($endDate) + 1);
            $elapsedDays = $startDate->isFuture()
                ? 0
                : min($totalDays, $startDate->diffInDays($now) + 1);

            $progress = $totalDays > 0 ? min(1, $elapsedDays / $totalDays) : 0;

            $status = 'In Progress';
            $color = '#3B82F6';

            if ($now->greaterThan($endDate)) {
                $status = 'Overdue';
                $color = '#EF4444';
                $counts['overdue']++;
            } elseif ($progress >= 0.8) {
                $status = 'Nearly Complete';
                $color = '#16A34A';
                $counts['nearly_complete']++;
            } elseif ($now->diffInDays($endDate) <= 7) {
                $status = 'Approaching Deadline';
                $color = '#F59E0B';
                $counts['approaching_deadline']++;
            }

            return [
                'id' => $project->getKey(),
                'text' => $project->name,
                'start_date' => $startDate->format('d-m-Y H:i'),
                'end_date' => $endDate->format('d-m-Y H:i'),
                'duration' => $totalDays,
                'progress' => $progress,
                'status' => $status,
                'color' => $color,
                'is_overdue' => $status === 'Overdue',
            ];
        })->values()->all();

        return [
            'counts' => $counts,
            'gantt' => [
                'data' => $tasks,
                'links' => [],
            ],
        ];
    }
}
