<?php

declare(strict_types=1);

namespace App\Domain\Repositories;

use App\Models\Project;
use App\Models\TicketStatus;
use Illuminate\Database\Eloquent\Collection;

interface TicketStatusRepositoryInterface
{
    public function forProject(Project $project): Collection;

    public function find(int $id): ?TicketStatus;

    public function create(Project $project, array $data): TicketStatus;

    public function update(TicketStatus $status, array $data): TicketStatus;

    public function delete(TicketStatus $status): bool;

    public function reorder(Project $project, array $orderedIds): void;
}

