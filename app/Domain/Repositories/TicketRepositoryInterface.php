<?php

declare(strict_types=1);

namespace App\Domain\Repositories;

use App\Models\Ticket;
use Illuminate\Database\Eloquent\Collection;

interface TicketRepositoryInterface
{
    /**
     * @param  array<int, string>  $relations
     */
    public function find(int $id, array $relations = []): ?Ticket;

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Ticket;

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Ticket $ticket, array $data): Ticket;

    public function delete(Ticket $ticket): bool;

    /**
     * @param  array<int, int>  $userIds
     */
    public function syncAssignees(Ticket $ticket, array $userIds): void;

    /**
     * @param  array<int, int>  $userIds
     */
    public function attachAssignees(Ticket $ticket, array $userIds): void;

    /**
     * @param  array<int, int>  $userIds
     */
    public function detachAssignees(Ticket $ticket, array $userIds): void;

    /**
     * @param  array<int, string>  $relations
     * @return Collection<int, Ticket>
     */
    public function forProject(int $projectId, array $relations = []): Collection;
}
