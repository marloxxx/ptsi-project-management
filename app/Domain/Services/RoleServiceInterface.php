<?php

declare(strict_types=1);

namespace App\Domain\Services;

use Illuminate\Database\Eloquent\Collection;
use Spatie\Permission\Models\Role;

interface RoleServiceInterface
{
    /**
     * Get all roles.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, \Spatie\Permission\Models\Role>
     */
    public function all(): Collection;

    /**
     * Find role by ID.
     */
    public function find(int|string $id): ?Role;

    /**
     * Create new role.
     *
     * @param  array<string, mixed>  $data
     * @param  array<int, string>|null  $permissions
     */
    public function create(array $data, ?array $permissions = null): Role;

    /**
     * Update existing role.
     *
     * @param  array<string, mixed>  $data
     * @param  array<int, string>|null  $permissions
     */
    public function update(int $id, array $data, ?array $permissions = null): bool;

    /**
     * Delete role.
     */
    public function delete(int $id): bool;

    /**
     * Sync permissions to role.
     *
     * @param  array<int, string>  $permissions
     */
    public function syncPermissions(int|string $roleId, array $permissions): bool;
}
