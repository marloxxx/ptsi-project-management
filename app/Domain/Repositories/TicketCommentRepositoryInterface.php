<?php

declare(strict_types=1);

namespace App\Domain\Repositories;

use App\Models\TicketComment;
use Illuminate\Database\Eloquent\Collection;

interface TicketCommentRepositoryInterface
{
    /**
     * @param  array<int, string>  $relations
     */
    public function find(int $id, array $relations = []): ?TicketComment;

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): TicketComment;

    public function delete(TicketComment $comment): bool;

    /**
     * @return Collection<int, TicketComment>
     */
    public function forTicket(int $ticketId): Collection;
}
