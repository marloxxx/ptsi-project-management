<?php

declare(strict_types=1);

namespace App\Infrastructure\Repositories;

use App\Domain\Repositories\TicketHistoryRepositoryInterface;
use App\Models\TicketHistory;
use Illuminate\Database\Eloquent\Collection;

class TicketHistoryRepository implements TicketHistoryRepositoryInterface
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): TicketHistory
    {
        return TicketHistory::create($data);
    }

    /**
     * @return Collection<int, TicketHistory>
     */
    public function forTicket(int $ticketId): Collection
    {
        return TicketHistory::where('ticket_id', $ticketId)
            ->orderByDesc('created_at')
            ->get();
    }
}
