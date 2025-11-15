<?php

declare(strict_types=1);

namespace App\Domain\Services;

use Illuminate\Support\Collection;

interface EpicOverviewServiceInterface
{
    /**
     * @return Collection<int, \App\Models\Project>
     */
    public function listProjects(int $userId): Collection;

    /**
     * @param  array<string, mixed>  $filters
     * @return Collection<int, \App\Models\Epic>
     */
    public function listEpics(int $userId, ?int $projectId = null, array $filters = []): Collection;
}
