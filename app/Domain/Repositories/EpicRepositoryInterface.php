<?php

declare(strict_types=1);

namespace App\Domain\Repositories;

use App\Models\Epic;
use Illuminate\Database\Eloquent\Collection;

interface EpicRepositoryInterface
{
    public function find(int $id, array $relations = []): ?Epic;

    public function create(array $data): Epic;

    public function update(Epic $epic, array $data): Epic;

    public function delete(Epic $epic): bool;

    public function forProject(int $projectId): Collection;
}
