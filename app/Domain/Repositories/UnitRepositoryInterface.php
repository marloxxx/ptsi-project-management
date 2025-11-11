<?php

declare(strict_types=1);

namespace App\Domain\Repositories;

use App\Models\Unit;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface UnitRepositoryInterface
{
    /**
     * Get all unit records.
     */
    public function all(?string $status = null): Collection;

    /**
     * Paginate units with optional filters.
     */
    public function paginate(int $perPage = 15, array $filters = []): LengthAwarePaginator;

    /**
     * Find unit by ID.
     */
    public function find(int $id): ?Unit;

    /**
     * Create new unit record.
     */
    public function create(array $data): Unit;

    /**
     * Update the given unit.
     */
    public function update(Unit $unit, array $data): Unit;

    /**
     * Delete the given unit.
     */
    public function delete(Unit $unit): bool;

    /**
     * Get units for select options.
     */
    public function options(?string $status = 'active'): Collection;

    /**
     * Find unit by code.
     */
    public function findByCode(string $code): ?Unit;

    /**
     * Find unit by SINAV identifier.
     */
    public function findBySinavId(string $sinavUnitId): ?Unit;
}
