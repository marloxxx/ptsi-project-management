<?php

declare(strict_types=1);

namespace App\Domain\Repositories;

use App\Models\Ticket;
use App\Models\TicketCustomValue;
use Illuminate\Database\Eloquent\Collection;

interface TicketCustomValueRepositoryInterface
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): TicketCustomValue;

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(TicketCustomValue $customValue, array $data): TicketCustomValue;

    public function delete(TicketCustomValue $customValue): bool;

    public function find(int $id): ?TicketCustomValue;

    /**
     * @return Collection<int, TicketCustomValue>
     */
    public function forTicket(int $ticketId): Collection;

    public function findByTicketAndField(int $ticketId, int $customFieldId): ?TicketCustomValue;

    /**
     * @param  array<int, array<string, mixed>>  $values
     */
    public function syncForTicket(Ticket $ticket, array $values): void;
}
