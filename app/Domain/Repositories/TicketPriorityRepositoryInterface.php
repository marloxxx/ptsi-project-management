<?php

declare(strict_types=1);

namespace App\Domain\Repositories;

use App\Models\TicketPriority;
use Illuminate\Database\Eloquent\Collection;

interface TicketPriorityRepositoryInterface
{
    /**
     * @return Collection<int, TicketPriority>
     */
    public function all(): Collection;

    public function find(int $id): ?TicketPriority;

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): TicketPriority;

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(TicketPriority $priority, array $data): TicketPriority;

    public function delete(TicketPriority $priority): bool;
}
