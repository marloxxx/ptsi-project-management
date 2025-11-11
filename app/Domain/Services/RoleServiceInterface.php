<?php

declare(strict_types=1);

namespace App\Domain\Services;

use Illuminate\Database\Eloquent\Collection;
use Spatie\Permission\Models\Role;

interface RoleServiceInterface
{
    /**
     * Get all roles.
     */
    public function all(): Collection;

    /**
     * Find role by ID.
     */
    public function find(int $id): ?Role;

    /**
     * Create new role.
     */
    public function create(array $data, ?array $permissions = null): Role;

    /**
     * Update existing role.
     */
    public function update(int $id, array $data, ?array $permissions = null): bool;

    /**
     * Delete role.
     */
    public function delete(int $id): bool;

    /**
     * Sync permissions to role.
     */
    public function syncPermissions(int $roleId, array $permissions): bool;
}
