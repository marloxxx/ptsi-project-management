<?php

declare(strict_types=1);

namespace App\Domain\Repositories;

use App\Models\TicketHistory;
use Illuminate\Database\Eloquent\Collection;

interface TicketHistoryRepositoryInterface
{
    public function create(array $data): TicketHistory;

    public function forTicket(int $ticketId): Collection;
}
