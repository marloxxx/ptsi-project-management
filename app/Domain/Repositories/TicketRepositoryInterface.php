<?php

declare(strict_types=1);

namespace App\Domain\Repositories;

use App\Models\Ticket;
use Illuminate\Database\Eloquent\Collection;

interface TicketRepositoryInterface
{
    public function find(int $id, array $relations = []): ?Ticket;

    public function create(array $data): Ticket;

    public function update(Ticket $ticket, array $data): Ticket;

    public function delete(Ticket $ticket): bool;

    public function syncAssignees(Ticket $ticket, array $userIds): void;

    public function attachAssignees(Ticket $ticket, array $userIds): void;

    public function detachAssignees(Ticket $ticket, array $userIds): void;

    public function forProject(int $projectId, array $relations = []): Collection;
}

