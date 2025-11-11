<?php

declare(strict_types=1);

namespace App\Domain\Services;

use App\Models\Unit;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface UnitServiceInterface
{
    /**
     * Get all units.
     */
    public function all(?string $status = null): Collection;

    /**
     * Paginate unit records.
     */
    public function paginate(int $perPage = 15, array $filters = []): LengthAwarePaginator;

    /**
     * Create a new unit.
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
     * Get active unit options for select components.
     */
    public function options(?string $status = 'active'): Collection;
}
