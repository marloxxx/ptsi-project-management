<?php

declare(strict_types=1);

namespace App\Domain\Repositories;

use App\Models\Epic;
use Illuminate\Database\Eloquent\Collection;

interface EpicRepositoryInterface
{
    /**
     * @param  array<int, string>  $relations
     */
    public function find(int $id, array $relations = []): ?Epic;

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Epic;

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Epic $epic, array $data): Epic;

    public function delete(Epic $epic): bool;

    /**
     * @return Collection<int, Epic>
     */
    public function forProject(int $projectId): Collection;
}
