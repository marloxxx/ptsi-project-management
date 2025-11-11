<?php

declare(strict_types=1);

namespace App\Domain\Services;

use App\Models\Ticket;
use App\Models\TicketComment;

interface TicketServiceInterface
{
    public function create(array $data, array $assigneeIds = []): Ticket;

    public function update(int $ticketId, array $data, ?array $assigneeIds = null): Ticket;

    public function delete(int $ticketId): bool;

    public function changeStatus(int $ticketId, int $statusId, ?string $note = null): Ticket;

    public function assignUsers(int $ticketId, array $userIds): Ticket;

    public function addComment(int $ticketId, array $data): TicketComment;

    public function deleteComment(int $commentId): bool;
}

