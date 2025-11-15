<?php

declare(strict_types=1);

namespace App\Infrastructure\Repositories;

use App\Domain\Repositories\EpicRepositoryInterface;
use App\Models\Epic;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Arr;

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

    /**
     * @param  array<string, mixed>  $options
     */
    public function forProjects(array $projectIds, array $options = []): Collection
    {
        if ($projectIds === []) {
            return Epic::query()->whereRaw('1 = 0')->get();
        }

        /** @var list<string> $relations */
        $relations = Arr::get($options, 'with', []);
        $search = trim((string) Arr::get($options, 'search', ''));
        $orderBy = Arr::get($options, 'order_by', 'start_date') ?: 'start_date';
        $orderDirection = Arr::get($options, 'order_direction', 'asc') === 'desc' ? 'desc' : 'asc';

        $query = Epic::query()
            ->whereIn('project_id', $projectIds);

        if ($relations !== []) {
            $query->with($relations);
        }

        if ($search !== '') {
            $query->where(function ($innerQuery) use ($search): void {
                $innerQuery->where('name', 'like', '%'.$search.'%')
                    ->orWhere('description', 'like', '%'.$search.'%');
            });
        }

        $query->orderBy($orderBy, $orderDirection)
            ->orderBy('name');

        return $query->get();
    }
}
