<?php

declare(strict_types=1);

namespace App\Domain\Repositories;

use App\Models\TicketComment;
use Illuminate\Database\Eloquent\Collection;

interface TicketCommentRepositoryInterface
{
    public function find(int $id, array $relations = []): ?TicketComment;

    public function create(array $data): TicketComment;

    public function delete(TicketComment $comment): bool;

    public function forTicket(int $ticketId): Collection;
}
