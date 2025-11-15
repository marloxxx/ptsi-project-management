<?php

declare(strict_types=1);

namespace App\Infrastructure\Repositories;

use App\Domain\Repositories\TicketCommentRepositoryInterface;
use App\Models\TicketComment;
use Illuminate\Database\Eloquent\Collection;

class TicketCommentRepository implements TicketCommentRepositoryInterface
{
    /**
     * @param  array<int, string>  $relations
     */
    public function find(int $id, array $relations = []): ?TicketComment
    {
        return TicketComment::with($relations)->find($id);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): TicketComment
    {
        return TicketComment::create($data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(TicketComment $comment, array $data): TicketComment
    {
        $comment->fill($data);
        $comment->save();

        return $comment->fresh();
    }

    public function delete(TicketComment $comment): bool
    {
        return (bool) $comment->delete();
    }

    /**
     * @return Collection<int, TicketComment>
     */
    public function forTicket(int $ticketId): Collection
    {
        return TicketComment::where('ticket_id', $ticketId)
            ->orderBy('created_at')
            ->get();
    }
}
