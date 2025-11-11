<?php

declare(strict_types=1);

namespace App\Infrastructure\Repositories;

use App\Domain\Repositories\TicketRepositoryInterface;
use App\Models\Ticket;
use Illuminate\Database\Eloquent\Collection;

class TicketRepository implements TicketRepositoryInterface
{
    public function find(int $id, array $relations = []): ?Ticket
    {
        return Ticket::with($relations)->find($id);
    }

    public function create(array $data): Ticket
    {
        return Ticket::create($data);
    }

    public function update(Ticket $ticket, array $data): Ticket
    {
        $ticket->update($data);

        return $ticket->fresh();
    }

    public function delete(Ticket $ticket): bool
    {
        return (bool) $ticket->delete();
    }

    public function syncAssignees(Ticket $ticket, array $userIds): void
    {
        $ticket->assignees()->sync($userIds);
    }

    public function attachAssignees(Ticket $ticket, array $userIds): void
    {
        $ticket->assignees()->syncWithoutDetaching($userIds);
    }

    public function detachAssignees(Ticket $ticket, array $userIds): void
    {
        $ticket->assignees()->detach($userIds);
    }

    public function forProject(int $projectId, array $relations = []): Collection
    {
        return Ticket::with($relations)
            ->where('project_id', $projectId)
            ->get();
    }
}

