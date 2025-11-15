<?php

declare(strict_types=1);

namespace App\Infrastructure\Repositories;

use App\Domain\Repositories\UnitRepositoryInterface;
use App\Models\Unit;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class UnitRepository implements UnitRepositoryInterface
{
    public function __construct(
        private Unit $model
    ) {}

    /**
     * @return Collection<int, Unit>
     */
    public function all(?string $status = null): Collection
    {
        return $this->model
            ->query()
            ->status($status)
            ->orderBy('name')
            ->get();
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, Unit>
     */
    public function paginate(int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        /** @var \Illuminate\Contracts\Pagination\LengthAwarePaginator<int, Unit> $paginator */
        $paginator = $this->model
            ->query()
            ->status($filters['status'] ?? null)
            ->when($filters['search'] ?? null, function (Builder $query, string $search): void {
                $query->where(function (Builder $builder) use ($search): void {
                    $builder
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%")
                        ->orWhere('sinav_unit_id', 'like', "%{$search}%");
                });
            })
            ->orderBy('name')
            ->paginate($perPage);

        return $paginator;
    }

    public function find(int $id): ?Unit
    {
        return $this->model->find($id);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Unit
    {
        return $this->model->create($data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Unit $unit, array $data): Unit
    {
        $unit->update($data);

        return $unit->refresh();
    }

    public function delete(Unit $unit): bool
    {
        return (bool) $unit->delete();
    }

    /**
     * @return Collection<int, Unit>
     */
    public function options(?string $status = 'active'): Collection
    {
        return $this->model
            ->query()
            ->status($status)
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    public function findByCode(string $code): ?Unit
    {
        return $this->model->where('code', $code)->first();
    }

    public function findBySinavId(string $sinavUnitId): ?Unit
    {
        return $this->model->where('sinav_unit_id', $sinavUnitId)->first();
    }
}
