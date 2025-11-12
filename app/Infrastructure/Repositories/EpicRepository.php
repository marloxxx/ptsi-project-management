<?php

declare(strict_types=1);

namespace App\Infrastructure\Repositories;

use App\Domain\Repositories\EpicRepositoryInterface;
use App\Models\Epic;
use Illuminate\Database\Eloquent\Collection;

class EpicRepository implements EpicRepositoryInterface
{
    /**
     * @param  array<int, string>  $relations
     */
    public function find(int $id, array $relations = []): ?Epic
    {
        return Epic::with($relations)->find($id);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Epic
    {
        return Epic::create($data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Epic $epic, array $data): Epic
    {
        $epic->update($data);

        return $epic->fresh();
    }

    public function delete(Epic $epic): bool
    {
        return (bool) $epic->delete();
    }

    /**
     * @return Collection<int, Epic>
     */
    public function forProject(int $projectId): Collection
    {
        return Epic::where('project_id', $projectId)
            ->orderBy('sort_order')
            ->get();
    }
}
