<?php

declare(strict_types=1);

namespace App\Infrastructure\Repositories;

use App\Domain\Repositories\TicketHistoryRepositoryInterface;
use App\Models\TicketHistory;
use Illuminate\Database\Eloquent\Collection;

class TicketHistoryRepository implements TicketHistoryRepositoryInterface
{
    public function create(array $data): TicketHistory
    {
        return TicketHistory::create($data);
    }

    public function forTicket(int $ticketId): Collection
    {
        return TicketHistory::where('ticket_id', $ticketId)
            ->orderByDesc('created_at')
            ->get();
    }
}

