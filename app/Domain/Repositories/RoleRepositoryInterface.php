<?php

declare(strict_types=1);

namespace App\Domain\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Spatie\Permission\Models\Role;

interface RoleRepositoryInterface
{
    /**
     * Get all Role records.
     */
    public function all(): Collection;

    /**
     * Find Role by ID.
     */
    public function find(int $id): ?Role;

    /**
     * Create new Role.
     */
    public function create(array $data): Role;

    /**
     * Update Role.
     */
    public function update(int $id, array $data): bool;

    /**
     * Delete Role.
     */
    public function delete(int $id): bool;
}
