<?php

declare(strict_types=1);

namespace App\Domain\Services;

use App\Models\Ticket;
use App\Models\TicketComment;

interface TicketServiceInterface
{
    /**
     * @param  array<string, mixed>  $data
     * @param  array<int, int>  $assigneeIds
     */
    public function create(array $data, array $assigneeIds = []): Ticket;

    /**
     * @param  array<string, mixed>  $data
     * @param  array<int, int>|null  $assigneeIds
     */
    public function update(int $ticketId, array $data, ?array $assigneeIds = null): Ticket;

    public function delete(int $ticketId): bool;

    public function changeStatus(int $ticketId, int $statusId, ?string $note = null): Ticket;

    /**
     * @param  array<int, int>  $userIds
     */
    public function assignUsers(int $ticketId, array $userIds): Ticket;

    /**
     * @param  array<string, mixed>  $data
     */
    public function addComment(int $ticketId, array $data): TicketComment;

    public function deleteComment(int $commentId): bool;
}
