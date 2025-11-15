<?php

declare(strict_types=1);

namespace App\Application\Services;

use App\Domain\Repositories\EpicRepositoryInterface;
use App\Domain\Repositories\ProjectRepositoryInterface;
use App\Domain\Repositories\UserRepositoryInterface;
use App\Domain\Services\EpicOverviewServiceInterface;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

class EpicOverviewService implements EpicOverviewServiceInterface
{
    public function __construct(
        private readonly ProjectRepositoryInterface $projectRepository,
        private readonly UserRepositoryInterface $userRepository,
        private readonly EpicRepositoryInterface $epicRepository,
    ) {}

    public function listProjects(int $userId): Collection
    {
        $user = $this->userRepository->find($userId);

        if (! $user instanceof User) {
            return collect();
        }

        $includeAll = $user->hasRole('super_admin');

        return $this->projectRepository->accessibleForUser($userId, [
            'include_all' => $includeAll,
            'with' => [
                'members:id,name',
            ],
        ]);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function listEpics(int $userId, ?int $projectId = null, array $filters = []): Collection
    {
        $projects = $this->listProjects($userId);

        if ($projects->isEmpty()) {
            return collect();
        }

        $projectIds = $projects
            ->pluck('id')
            ->map(static fn ($id): int => (int) $id)
            ->all();

        if ($projectId !== null) {
            if (! in_array($projectId, $projectIds, true)) {
                throw new AuthorizationException('Anda tidak memiliki akses ke proyek ini.');
            }

            $projectIds = [$projectId];
        }

        /** @var list<string>|array<string, mixed> $relations */
        $relations = Arr::get($filters, 'with', [
            'project:id,name,ticket_prefix,color,start_date,end_date',
            'tickets' => static function ($query): void {
                $query->with([
                    'status:id,name,color,is_completed',
                    'priority:id,name,color',
                    'assignees:id,name',
                ])
                    ->select([
                        'id',
                        'project_id',
                        'ticket_status_id',
                        'priority_id',
                        'epic_id',
                        'created_by',
                        'uuid',
                        'name',
                        'description',
                        'start_date',
                        'due_date',
                        'created_at',
                        'updated_at',
                    ])
                    ->orderByDesc('created_at');
            },
        ]);

        return $this->epicRepository->forProjects($projectIds, [
            'with' => $relations,
            'search' => Arr::get($filters, 'search'),
            'order_by' => Arr::get($filters, 'order_by', 'start_date'),
            'order_direction' => Arr::get($filters, 'order_direction', 'asc'),
        ]);
    }
}
