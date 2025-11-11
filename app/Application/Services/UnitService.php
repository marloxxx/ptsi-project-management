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

    /**
     * @return Collection<int, Unit>
     */
    public function all(?string $status = null): Collection
    {
        return $this->units->all($status);
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, Unit>
     */
    public function paginate(int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        return $this->units->paginate($perPage, $filters);
    }

    /**
     * @param  array<string, mixed>  $data
     */
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

    /**
     * @param  array<string, mixed>  $data
     */
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

    /**
     * @return Collection<int, Unit>
     */
    public function options(?string $status = 'active'): Collection
    {
        return $this->units->options($status);
    }

    /**
     * Prepare payload before persisting to repository.
     *
     * @param  array<string, mixed>  $data
     * @return array{name: string, code: string, sinav_unit_id: string|null, status: string}
     */
    private function preparePayload(array $data, ?Unit $unit = null): array
    {
        $payload = Arr::only($data, ['name', 'code', 'sinav_unit_id', 'status']);

        $name = $payload['name'] ?? ($unit !== null ? $unit->name : '');
        $code = $payload['code'] ?? ($unit !== null ? $unit->code : '');
        $status = $payload['status'] ?? ($unit !== null ? $unit->status : 'active');
        $sinavUnitId = $payload['sinav_unit_id'] ?? ($unit !== null ? $unit->sinav_unit_id : null);

        return [
            'name' => trim((string) $name),
            'code' => strtoupper(trim((string) $code)),
            'status' => (string) $status,
            'sinav_unit_id' => $sinavUnitId !== null && $sinavUnitId !== '' ? (string) $sinavUnitId : null,
        ];
    }
}
