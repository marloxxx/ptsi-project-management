<?php

declare(strict_types=1);

namespace App\Domain\Repositories;

use App\Models\Project;
use App\Models\TicketStatus;
use Illuminate\Database\Eloquent\Collection;

interface TicketStatusRepositoryInterface
{
    /**
     * @return Collection<int, TicketStatus>
     */
    public function forProject(Project $project): Collection;

    /**
     * Retrieve ticket statuses with eager-loaded tickets for board display.
     *
     * @param  array<string, mixed>  $options
     *                                         - assignee_ids: array<int, int>
     *                                         - ticket_relations: array<int, string>
     * @return Collection<int, TicketStatus>
     */
    public function boardColumns(Project $project, array $options = []): Collection;

    public function find(int $id): ?TicketStatus;

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(Project $project, array $data): TicketStatus;

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(TicketStatus $status, array $data): TicketStatus;

    public function delete(TicketStatus $status): bool;

    /**
     * @param  array<int, int>  $orderedIds
     */
    public function reorder(Project $project, array $orderedIds): void;
}
