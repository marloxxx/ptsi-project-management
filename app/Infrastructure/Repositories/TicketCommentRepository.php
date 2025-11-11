<?php

declare(strict_types=1);

namespace App\Infrastructure\Repositories;

use App\Domain\Repositories\TicketCommentRepositoryInterface;
use App\Models\TicketComment;
use Illuminate\Database\Eloquent\Collection;

class TicketCommentRepository implements TicketCommentRepositoryInterface
{
    public function find(int $id, array $relations = []): ?TicketComment
    {
        return TicketComment::with($relations)->find($id);
    }

    public function create(array $data): TicketComment
    {
        return TicketComment::create($data);
    }

    public function delete(TicketComment $comment): bool
    {
        return (bool) $comment->delete();
    }

    public function forTicket(int $ticketId): Collection
    {
        return TicketComment::where('ticket_id', $ticketId)
            ->orderBy('created_at')
            ->get();
    }
}

