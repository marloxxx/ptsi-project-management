<?php

declare(strict_types=1);

namespace App\Infrastructure\Repositories;

use App\Domain\Repositories\TicketStatusRepositoryInterface;
use App\Models\Project;
use App\Models\TicketStatus;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Arr;

class TicketStatusRepository implements TicketStatusRepositoryInterface
{
    /**
     * @return Collection<int, TicketStatus>
     */
    public function forProject(Project $project): Collection
    {
        return $project->ticketStatuses()
            ->orderBy('sort_order')
            ->get();
    }

    /**
     * @param  array<string, mixed>  $options
     * @return Collection<int, TicketStatus>
     */
    public function boardColumns(Project $project, array $options = []): Collection
    {
        /** @var array<int, string> $ticketRelations */
        $ticketRelations = Arr::get($options, 'ticket_relations', [
            'assignees:id,name',
            'priority:id,name,color',
            'creator:id,name',
            'epic:id,name',
        ]);

        /** @var array<int, int> $assigneeIds */
        $assigneeIds = array_values(array_filter(
            array_map(
                static fn ($value): int => (int) $value,
                Arr::get($options, 'assignee_ids', [])
            ),
            static fn (int $id): bool => $id > 0
        ));

        return $project->ticketStatuses()
            ->with([
                'tickets' => function ($query) use ($ticketRelations, $assigneeIds): void {
                    $query->with($ticketRelations)
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

                    if ($assigneeIds !== []) {
                        $query->whereHas('assignees', function ($assigneeQuery) use ($assigneeIds): void {
                            $assigneeQuery->whereIn('users.id', $assigneeIds);
                        });
                    }
                },
            ])
            ->orderBy('sort_order')
            ->get([
                'id',
                'project_id',
                'name',
                'color',
                'is_completed',
                'sort_order',
            ]);
    }

    public function find(int $id): ?TicketStatus
    {
        return TicketStatus::find($id);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(Project $project, array $data): TicketStatus
    {
        /** @var TicketStatus $status */
        $status = $project->ticketStatuses()->create($data);

        return $status;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(TicketStatus $status, array $data): TicketStatus
    {
        $status->update($data);

        return $status->fresh();
    }

    public function delete(TicketStatus $status): bool
    {
        return (bool) $status->delete();
    }

    /**
     * @param  array<int, int>  $orderedIds
     */
    public function reorder(Project $project, array $orderedIds): void
    {
        foreach ($orderedIds as $index => $statusId) {
            $project->ticketStatuses()
                ->whereKey($statusId)
                ->update(['sort_order' => $index]);
        }
    }
}
