<?php

declare(strict_types=1);

namespace App\Application\Services;

use App\Domain\Repositories\UnitRepositoryInterface;
use App\Domain\Services\UnitServiceInterface;
use App\Models\Unit;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class UnitService implements UnitServiceInterface
{
    public function __construct(
        private UnitRepositoryInterface $units
    ) {}

    public function all(?string $status = null): Collection
    {
        return $this->units->all($status);
    }

    public function paginate(int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        return $this->units->paginate($perPage, $filters);
    }

    public function create(array $data): Unit
    {
        return DB::transaction(function () use ($data) {
            $unit = $this->units->create($this->preparePayload($data));

            activity()
                ->performedOn($unit)
                ->event('created')
                ->withProperties($unit->toArray())
                ->log('Unit created');

            return $unit;
        });
    }

    public function update(Unit $unit, array $data): Unit
    {
        return DB::transaction(function () use ($unit, $data) {
            $updatedUnit = $this->units->update($unit, $this->preparePayload($data, $unit));

            activity()
                ->performedOn($updatedUnit)
                ->event('updated')
                ->withProperties($updatedUnit->getChanges())
                ->log('Unit updated');

            return $updatedUnit;
        });
    }

    public function delete(Unit $unit): bool
    {
        return DB::transaction(function () use ($unit) {
            $deleted = $this->units->delete($unit);

            if ($deleted) {
                activity()
                    ->performedOn($unit)
                    ->event('deleted')
                    ->log('Unit deleted');
            }

            return $deleted;
        });
    }

    public function options(?string $status = 'active'): Collection
    {
        return $this->units->options($status);
    }

    /**
     * Prepare payload before persisting to repository.
     */
    private function preparePayload(array $data, ?Unit $unit = null): array
    {
        $payload = Arr::only($data, ['name', 'code', 'sinav_unit_id', 'status']);

        $payload['name'] = trim((string) ($payload['name'] ?? $unit?->name ?? ''));
        $payload['code'] = strtoupper(trim((string) ($payload['code'] ?? $unit?->code ?? '')));
        $payload['status'] = $payload['status'] ?? $unit?->status ?? 'active';
        $payload['sinav_unit_id'] = $payload['sinav_unit_id'] ?: null;

        return $payload;
    }
}
