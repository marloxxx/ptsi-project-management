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
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, \App\Models\Unit>
     */
    public function all(?string $status = null): Collection;

    /**
     * Paginate unit records.
     *
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, Unit>
     */
    public function paginate(int $perPage = 15, array $filters = []): LengthAwarePaginator;

    /**
     * Create a new unit.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Unit;

    /**
     * Update the given unit.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(Unit $unit, array $data): Unit;

    /**
     * Delete the given unit.
     */
    public function delete(Unit $unit): bool;

    /**
     * Get unit options for select components.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, \App\Models\Unit>
     */
    public function options(?string $status = 'active'): Collection;
}
