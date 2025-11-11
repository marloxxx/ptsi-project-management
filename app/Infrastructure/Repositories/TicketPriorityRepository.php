<?php

declare(strict_types=1);

namespace App\Infrastructure\Repositories;

use App\Domain\Repositories\TicketPriorityRepositoryInterface;
use App\Models\TicketPriority;
use Illuminate\Database\Eloquent\Collection;

class TicketPriorityRepository implements TicketPriorityRepositoryInterface
{
    public function all(): Collection
    {
        return TicketPriority::orderBy('sort_order')->get();
    }

    public function find(int $id): ?TicketPriority
    {
        return TicketPriority::find($id);
    }

    public function create(array $data): TicketPriority
    {
        return TicketPriority::create($data);
    }

    public function update(TicketPriority $priority, array $data): TicketPriority
    {
        $priority->update($data);

        return $priority->fresh();
    }

    public function delete(TicketPriority $priority): bool
    {
        return (bool) $priority->delete();
    }
}

