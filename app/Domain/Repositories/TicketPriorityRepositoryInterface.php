<?php

declare(strict_types=1);

namespace App\Domain\Repositories;

use App\Models\TicketPriority;
use Illuminate\Database\Eloquent\Collection;

interface TicketPriorityRepositoryInterface
{
    public function all(): Collection;

    public function find(int $id): ?TicketPriority;

    public function create(array $data): TicketPriority;

    public function update(TicketPriority $priority, array $data): TicketPriority;

    public function delete(TicketPriority $priority): bool;
}
