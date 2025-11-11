<?php

declare(strict_types=1);

namespace App\Infrastructure\Repositories;

use App\Domain\Repositories\TicketStatusRepositoryInterface;
use App\Models\Project;
use App\Models\TicketStatus;
use Illuminate\Database\Eloquent\Collection;

class TicketStatusRepository implements TicketStatusRepositoryInterface
{
    public function forProject(Project $project): Collection
    {
        return $project->ticketStatuses()
            ->orderBy('sort_order')
            ->get();
    }

    public function find(int $id): ?TicketStatus
    {
        return TicketStatus::find($id);
    }

    public function create(Project $project, array $data): TicketStatus
    {
        return $project->ticketStatuses()->create($data);
    }

    public function update(TicketStatus $status, array $data): TicketStatus
    {
        $status->update($data);

        return $status->fresh();
    }

    public function delete(TicketStatus $status): bool
    {
        return (bool) $status->delete();
    }

    public function reorder(Project $project, array $orderedIds): void
    {
        foreach ($orderedIds as $index => $statusId) {
            $project->ticketStatuses()
                ->whereKey($statusId)
                ->update(['sort_order' => $index]);
        }
    }
}

