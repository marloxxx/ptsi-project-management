<?php

declare(strict_types=1);

namespace App\Domain\Services;

interface GlobalSearchServiceInterface
{
    /**
     * Search across tickets and comments with permissions.
     *
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed> Results with 'tickets' and 'comments' collections
     */
    public function search(string $query, ?int $projectId = null, array $filters = []): array;
}
