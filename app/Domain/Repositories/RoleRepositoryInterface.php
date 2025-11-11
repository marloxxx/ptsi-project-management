<?php

declare(strict_types=1);

namespace App\Domain\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Spatie\Permission\Models\Role;

interface RoleRepositoryInterface
{
    /**
     * Get all Role records.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, \Spatie\Permission\Models\Role>
     */
    public function all(): Collection;

    /**
     * Find Role by ID or name.
     */
    public function find(int|string $id): ?Role;

    /**
     * Create new Role.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Role;

    /**
     * Update Role.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(int $id, array $data): bool;

    /**
     * Delete Role.
     */
    public function delete(int $id): bool;
}
