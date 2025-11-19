<?php

declare(strict_types=1);

namespace App\Domain\Services;

use App\Models\SavedFilter;
use Illuminate\Database\Eloquent\Collection;

interface SavedFilterServiceInterface
{
    /**
     * Get saved filters accessible to the current user.
     *
     * @return Collection<int, SavedFilter>
     */
    public function list(?int $projectId = null): Collection;

    /**
     * Create a new saved filter.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): SavedFilter;

    /**
     * Update an existing saved filter.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(int $filterId, array $data): SavedFilter;

    /**
     * Delete a saved filter.
     */
    public function delete(int $filterId): bool;

    /**
     * Apply a saved filter query to get filtered tickets.
     *
     * @return array<string, mixed> Filter criteria
     */
    public function getFilterQuery(int $filterId): array;
}
